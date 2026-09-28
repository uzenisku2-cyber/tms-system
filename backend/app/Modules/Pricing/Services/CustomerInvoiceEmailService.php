<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Pricing\Jobs\SendCustomerInvoiceEmailJob;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\CustomerInvoiceEmailDispatch;
use App\Modules\Pricing\Models\CustomerInvoicePdfArtifact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class CustomerInvoiceEmailService
{
    public function __construct(
        private readonly OrganizationContext $organizations,
        private readonly CustomerInvoiceDraftService $invoices,
    ) {}

    /** @return array<string, mixed>|null */
    public function show(string $publicId): ?array
    {
        $this->issuerDocument($publicId);
        $document = BillingDocument::query()->where('public_id', $publicId)->firstOrFail();
        $dispatch = CustomerInvoiceEmailDispatch::query()->where('billing_document_id', $document->id)->first();

        return $dispatch instanceof CustomerInvoiceEmailDispatch ? $this->present($dispatch) : null;
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function request(string $publicId, User $actor, array $data): array
    {
        $this->issuerDocument($publicId);
        abort_unless(config('mail.default') === 'smtp'
            && filter_var(config('mail.from.address'), FILTER_VALIDATE_EMAIL)
            && ! str_ends_with((string) config('mail.from.address'), '@example.com'), 409);
        $fingerprint = hash('sha256', json_encode([
            $data['pdf_sha256'], mb_strtolower($data['recipient_email']), $data['reason'],
        ], JSON_THROW_ON_ERROR));
        $ownerId = $this->organizations->requireId();

        return DB::transaction(function () use ($publicId, $actor, $data, $ownerId, $fingerprint): array {
            $document = BillingDocument::query()->where('public_id', $publicId)
                ->where('document_type', BillingDocument::TYPE_CUSTOMER_INVOICE)
                ->where('owner_organization_id', $ownerId)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($document->status, ['approved', 'closed'], true), 409);
            $identity = $document->commercialIdentity;
            abort_unless($identity !== null, 409);
            $existing = CustomerInvoiceEmailDispatch::query()->where('billing_document_id', $document->id)->first();
            if ($existing instanceof CustomerInvoiceEmailDispatch) {
                if ($existing->idempotency_key !== $data['idempotency_key']
                    || ! hash_equals($existing->command_fingerprint, $fingerprint)) {
                    abort(409, 'An email dispatch is already recorded for this invoice. Review its status.');
                }

                return $this->present($existing);
            }
            $artifact = CustomerInvoicePdfArtifact::query()->where('billing_document_id', $document->id)->first();
            abort_unless($artifact instanceof CustomerInvoicePdfArtifact
                && (int) $artifact->commercial_identity_id === (int) $identity->id
                && hash_equals($artifact->pdf_sha256, $data['pdf_sha256']), 409);
            $snapshot = $identity->counterparty_snapshot;
            abort_unless(is_array($snapshot)
                && hash_equals($artifact->snapshot_sha256, hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR))), 409);
            abort_unless(Storage::disk('local')->exists($artifact->storage_path), 409);
            $bytes = Storage::disk('local')->get($artifact->storage_path);
            abort_unless(is_string($bytes) && str_starts_with($bytes, '%PDF')
                && hash_equals($artifact->pdf_sha256, hash('sha256', $bytes)), 409);
            $dispatch = CustomerInvoiceEmailDispatch::query()->create([
                'owner_organization_id' => $ownerId, 'billing_document_id' => $document->id,
                'customer_invoice_pdf_artifact_id' => $artifact->id, 'pdf_sha256' => $artifact->pdf_sha256,
                'idempotency_key' => $data['idempotency_key'], 'command_fingerprint' => $fingerprint,
                'recipient_email' => mb_strtolower($data['recipient_email']), 'reason' => $data['reason'],
                'status' => 'queued', 'requested_by_user_id' => $actor->id, 'queued_at' => now(),
            ]);
            SendCustomerInvoiceEmailJob::dispatch($dispatch->id)->afterCommit();

            return $this->present($dispatch);
        });
    }

    private function issuerDocument(string $publicId): void
    {
        $this->invoices->show($publicId);
        $ownerId = $this->organizations->requireId();
        abort_unless((int) BillingDocument::query()->where('public_id', $publicId)
            ->where('document_type', BillingDocument::TYPE_CUSTOMER_INVOICE)
            ->where('owner_organization_id', $ownerId)->count() === 1, 404);
        abort_unless(Organization::query()->whereKey($ownerId)->value('type') === Organization::TYPE_MASTER, 403);

    }

    /** @return array<string, mixed> */
    private function present(CustomerInvoiceEmailDispatch $dispatch): array
    {
        return [
            'public_id' => $dispatch->public_id, 'status' => $dispatch->status,
            'recipient_email' => $dispatch->recipient_email, 'pdf_sha256' => $dispatch->pdf_sha256,
            'reason' => $dispatch->reason,
            'queued_at' => $dispatch->getRawOriginal('queued_at'),
            'started_at' => $dispatch->getRawOriginal('started_at'),
            'accepted_at' => $dispatch->getRawOriginal('accepted_at'),
            'uncertain_at' => $dispatch->getRawOriginal('uncertain_at'),
            'failure_summary' => $dispatch->failure_summary,
        ];
    }
}
