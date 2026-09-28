<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Models\User;
use App\Modules\Pricing\Models\BillingDocument;
use App\Modules\Pricing\Models\CustomerInvoicePdfArtifact;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class CustomerInvoicePdfService
{
    public function __construct(private readonly CustomerInvoiceDraftService $invoices) {}

    public function download(string $publicId, User $actor): string
    {
        // Reuse the issuer / customer organization scope from the invoice read API.
        $invoice = $this->invoices->show($publicId);
        abort_unless(in_array($invoice['status'], ['approved', 'closed'], true)
            && $invoice['document_number'] !== null, 404);

        return DB::transaction(function () use ($publicId, $actor, $invoice): string {
            $document = BillingDocument::query()->where('public_id', $publicId)
                ->where('document_type', BillingDocument::TYPE_CUSTOMER_INVOICE)
                ->lockForUpdate()->firstOrFail();
            abort_unless(in_array($document->status, ['approved', 'closed'], true), 404);
            $identity = $document->commercialIdentity;
            abort_unless($identity !== null, 404);
            $snapshot = $identity->counterparty_snapshot;
            if (! is_array($snapshot)) {
                throw new RuntimeException('Issued invoice snapshot is unavailable.');
            }
            $snapshotHash = hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));
            $artifact = CustomerInvoicePdfArtifact::query()
                ->where('billing_document_id', $document->id)->first();
            if ($artifact instanceof CustomerInvoicePdfArtifact) {
                abort_unless($artifact->snapshot_sha256 === $snapshotHash
                    && (int) $artifact->commercial_identity_id === (int) $identity->id, 409);
                abort_unless(Storage::disk('local')->exists($artifact->storage_path), 409);
                $bytes = Storage::disk('local')->get($artifact->storage_path);
                abort_unless(is_string($bytes) && str_starts_with($bytes, '%PDF')
                    && hash_equals($artifact->pdf_sha256, hash('sha256', $bytes)), 409);

                return $bytes;
            }

            $options = new Options;
            $options->set('isRemoteEnabled', false);
            $options->set('isPhpEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');
            $pdf = new Dompdf($options);
            $pdf->loadHtml(view('mvp.customer-invoice-pdf', ['invoice' => $invoice])->render(), 'UTF-8');
            $pdf->setPaper('A4', 'portrait');
            $pdf->render();
            $bytes = $pdf->output();
            if (! str_starts_with($bytes, '%PDF')) {
                throw new RuntimeException('PDF rendering failed.');
            }
            $path = 'customer-invoices/'.$document->owner_organization_id.'/'.$publicId.'/invoice.pdf';
            if (! Storage::disk('local')->put($path, $bytes)) {
                throw new RuntimeException('Could not store the invoice PDF.');
            }
            CustomerInvoicePdfArtifact::query()->create([
                'owner_organization_id' => $document->owner_organization_id,
                'billing_document_id' => $document->id,
                'commercial_identity_id' => $identity->id,
                'snapshot_sha256' => $snapshotHash,
                'pdf_sha256' => hash('sha256', $bytes),
                'storage_path' => $path,
                'byte_size' => strlen($bytes),
                'generated_at' => now(),
                'generated_by_user_id' => $actor->id,
            ]);

            return $bytes;
        });
    }
}
