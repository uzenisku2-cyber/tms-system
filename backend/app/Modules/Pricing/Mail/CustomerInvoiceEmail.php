<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Mail;

use Illuminate\Mail\Mailable;

final class CustomerInvoiceEmail extends Mailable
{
    public function __construct(
        private readonly string $documentNumber,
        private readonly string $pdfBytes,
    ) {}

    public function build(): static
    {
        return $this->subject('Faktura '.$this->documentNumber)
            ->view('mvp.customer-invoice-email')
            ->with(['documentNumber' => $this->documentNumber])
            ->attachData($this->pdfBytes, 'faktura-'.$this->documentNumber.'.pdf', ['mime' => 'application/pdf']);
    }
}
