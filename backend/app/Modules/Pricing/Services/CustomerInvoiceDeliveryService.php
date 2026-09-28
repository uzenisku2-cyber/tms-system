<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\CustomerInvoiceDeliveryEvent;
use App\Modules\Pricing\Models\CustomerInvoicePdfArtifact;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class CustomerInvoiceDeliveryService
{
    public function __construct(
        private readonly OrganizationContext $organizations,
        private readonly CustomerInvoiceDraftService $invoices,
    ) {}

    /** @return array<string, mixed> */
    public function history(string $publicId): array
    {
        $invoice = $this->invoices->show($publicId);
        abort_unless(in_array($invoice['status'], ['approved', 'closed'], true), 404);
        $document = BillingDocument::query()->where('public_id', $publicId)
            ->where('document_type', BillingDocument::TYPE_CUSTOMER_INVOICE)->firstOrFail();
        $events = CustomerInvoiceDeliveryEvent::query()->where('billing_document_id', $document->id)
            ->orderBy('revision')->get();

        return [
            'current_revision' => $events->isEmpty() ? 0 : $events->last()->revision,
            'pdf_sha256' => CustomerInvoicePdfArtifact::query()->where('billing_document_id', $document->id)->value('pdf_sha256'),
            'events' => $events->map(fn (CustomerInvoiceDeliveryEvent $event): array => $this->present($event))->all(),
        ];
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function record(string $publicId, User $actor, array $data): array
    {
        $ownerId = $this->organizations->requireId();
        $owner = Organization::query()->findOrFail($ownerId);
        abort_unless($owner->type === Organization::TYPE_MASTER, 403);
        $deliveredAt = Carbon::parse($data['delivered_at'])->utc();
        $fingerprint = hash('sha256', json_encode([
            $data['expected_revision'], $data['pdf_sha256'], $data['method'], $data['recipient'],
            $deliveredAt->toIso8601String(), $data['evidence_reference'], $data['reason'],
        ], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($publicId, $actor, $data, $ownerId, $deliveredAt, $fingerprint): array {
            $document = BillingDocument::query()->where('public_id', $publicId)
                ->where('document_type', BillingDocument::TYPE_CUSTOMER_INVOICE)
                ->where('owner_organization_id', $ownerId)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($document->status, ['approved', 'closed'], true), 409);
            $identity = $document->commercialIdentity;
            abort_unless($identity !== null, 409);
            $existing = CustomerInvoiceDeliveryEvent::query()
                ->where('billing_document_id', $document->id)
                ->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing instanceof CustomerInvoiceDeliveryEvent) {
                if (! hash_equals($existing->command_fingerprint, $fingerprint)) {
                    $this->invalid('idempotency_key', 'This key was used for different delivery evidence.');
                }

                return $this->present($existing);
            }
            $current = (int) (CustomerInvoiceDeliveryEvent::query()
                ->where('billing_document_id', $document->id)->max('revision') ?? 0);
            abort_unless($current === (int) $data['expected_revision'], 409);
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
            $event = CustomerInvoiceDeliveryEvent::query()->create([
                'owner_organization_id' => $ownerId, 'billing_document_id' => $document->id,
                'customer_invoice_pdf_artifact_id' => $artifact->id,
                'revision' => $current + 1, 'idempotency_key' => $data['idempotency_key'],
                'command_fingerprint' => $fingerprint, 'pdf_sha256' => $artifact->pdf_sha256,
                'method' => $data['method'], 'recipient' => $data['recipient'],
                'delivered_at' => $deliveredAt, 'evidence_reference' => $data['evidence_reference'],
                'reason' => $data['reason'], 'actor_user_id' => $actor->id, 'recorded_at' => now(),
            ]);

            return $this->present($event);
        });
    }

    /** @return array<string, mixed> */
    private function present(CustomerInvoiceDeliveryEvent $event): array
    {
        return [
            'public_id' => $event->public_id, 'revision' => $event->revision,
            'pdf_sha256' => $event->pdf_sha256, 'method' => $event->method,
            'recipient' => $event->recipient,
            'delivered_at' => Carbon::parse((string) $event->getRawOriginal('delivered_at'))->toIso8601String(),
            'evidence_reference' => $event->evidence_reference, 'reason' => $event->reason,
            'actor_user_id' => $event->actor_user_id,
            'recorded_at' => Carbon::parse((string) $event->getRawOriginal('recorded_at'))->toIso8601String(),
        ];
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
