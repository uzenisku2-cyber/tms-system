<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Jobs;

use App\Modules\Pricing\Mail\CustomerInvoiceEmail;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\CustomerInvoiceDeliveryEvent;
use App\Modules\Pricing\Models\CustomerInvoiceEmailDispatch;
use App\Modules\Pricing\Models\CustomerInvoicePdfArtifact;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

final class SendCustomerInvoiceEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public readonly int $dispatchId) {}

    public function handle(): void
    {
        $dispatch = DB::transaction(function (): ?CustomerInvoiceEmailDispatch {
            $record = CustomerInvoiceEmailDispatch::query()->whereKey($this->dispatchId)->lockForUpdate()->first();
            if (! $record instanceof CustomerInvoiceEmailDispatch || $record->status !== 'queued') {
                return null;
            }
            $record->forceFill(['status' => 'sending', 'started_at' => now()])->save();

            return $record;
        });
        if (! $dispatch instanceof CustomerInvoiceEmailDispatch) {
            return;
        }

        try {
            if (config('mail.default') !== 'smtp'
                || ! filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)
                || str_ends_with((string) config('mail.from.address'), '@example.com')) {
                throw new RuntimeException('SMTP sender is not configured.');
            }
            $document = BillingDocument::query()->findOrFail($dispatch->billing_document_id);
            $identity = $document->commercialIdentity;
            $artifact = CustomerInvoicePdfArtifact::query()->findOrFail($dispatch->customer_invoice_pdf_artifact_id);
            if (! in_array($document->status, ['approved', 'closed'], true)
                || $identity === null || (int) $identity->id !== (int) $artifact->commercial_identity_id
                || ! hash_equals($dispatch->pdf_sha256, $artifact->pdf_sha256)) {
                throw new RuntimeException('Invoice identity or PDF changed.');
            }
            $snapshot = $identity->counterparty_snapshot;
            if (! is_array($snapshot)
                || ! hash_equals($artifact->snapshot_sha256, hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR)))) {
                throw new RuntimeException('Invoice snapshot changed.');
            }
            $bytes = Storage::disk('local')->get($artifact->storage_path);
            if (! is_string($bytes) || ! str_starts_with($bytes, '%PDF')
                || ! hash_equals($dispatch->pdf_sha256, hash('sha256', $bytes))) {
                throw new RuntimeException('Invoice PDF integrity check failed.');
            }
            Mail::to($dispatch->recipient_email)->send(new CustomerInvoiceEmail($identity->document_number, $bytes));

            DB::transaction(function () use ($dispatch): void {
                $document = BillingDocument::query()->whereKey($dispatch->billing_document_id)->lockForUpdate()->firstOrFail();
                $record = CustomerInvoiceEmailDispatch::query()->whereKey($dispatch->id)->lockForUpdate()->firstOrFail();
                if ($record->status !== 'sending') {
                    return;
                }
                $acceptedAt = now();
                $record->forceFill(['status' => 'accepted', 'accepted_at' => $acceptedAt])->save();
                $revision = (int) (CustomerInvoiceDeliveryEvent::query()
                    ->where('billing_document_id', $document->id)->max('revision') ?? 0) + 1;
                $evidence = 'email-dispatch:'.$record->public_id;
                $reason = 'SMTP transport accepted the message; recipient receipt is not confirmed. '.$record->reason;
                CustomerInvoiceDeliveryEvent::query()->create([
                    'owner_organization_id' => $record->owner_organization_id,
                    'billing_document_id' => $document->id,
                    'customer_invoice_pdf_artifact_id' => $record->customer_invoice_pdf_artifact_id,
                    'revision' => $revision, 'idempotency_key' => $record->idempotency_key,
                    'command_fingerprint' => hash('sha256', $evidence), 'pdf_sha256' => $record->pdf_sha256,
                    'method' => 'email', 'recipient' => $record->recipient_email,
                    'delivered_at' => $acceptedAt, 'evidence_reference' => $evidence,
                    'reason' => $reason, 'actor_user_id' => $record->requested_by_user_id,
                    'recorded_at' => $acceptedAt,
                ]);
            });
        } catch (Throwable $exception) {
            DB::transaction(function (): void {
                $record = CustomerInvoiceEmailDispatch::query()->whereKey($this->dispatchId)->lockForUpdate()->first();
                if ($record instanceof CustomerInvoiceEmailDispatch && $record->status === 'sending') {
                    $record->forceFill([
                        'status' => 'uncertain', 'uncertain_at' => now(),
                        'failure_summary' => 'Transport acceptance was not confirmed. Review before any further action.',
                    ])->save();
                }
            });
            report($exception);
        }
    }

    public function failed(?Throwable $exception): void
    {
        CustomerInvoiceEmailDispatch::query()->whereKey($this->dispatchId)
            ->whereIn('status', ['queued', 'sending'])
            ->update([
                'status' => 'uncertain', 'uncertain_at' => now(),
                'failure_summary' => 'Queue processing did not confirm SMTP acceptance. Review before any further action.',
            ]);
    }
}
