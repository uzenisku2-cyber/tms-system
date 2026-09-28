<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Modules\Pricing\Services\CustomerInvoiceDraftService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class CustomerInvoiceDocumentController
{
    public function show(Request $request, string $customerInvoice, CustomerInvoiceDraftService $invoices): View
    {
        $invoice = $invoices->show($customerInvoice);
        abort_unless(in_array($invoice['status'], ['approved', 'closed'], true)
            && $invoice['document_number'] !== null, 404);

        return view('mvp.customer-invoice-document', ['invoice' => $invoice]);
    }
}
