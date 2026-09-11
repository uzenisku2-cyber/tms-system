<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class SupplierFuelInvoiceAdministrationUiTest extends TestCase
{
    public function test_supplier_fuel_invoice_administration_page_exposes_read_allocation_and_reversal_controls(): void
    {
        $response = $this->get('/settings/supplier-fuel-invoices');

        $response->assertOk()
            ->assertSee('Dodavatelské faktury PHM', false)
            ->assertSee('id="detailModal"', false)
            ->assertSee('id="createAllocation"', false)
            ->assertSee('data-reverse=', false)
            ->assertSee('/api/v1/supplier-fuel-invoices?', false)
            ->assertSee('/fuel-transaction-allocations', false)
            ->assertSee('/reverse', false)
            ->assertSee('expected_revision', false)
            ->assertSee('allocated_amount_minor', false)
            ->assertSee('unallocated_amount_minor', false)
            ->assertSee("headers.set('X-Organization-ID',org)", false)
            ->assertSee('Bankovní párování', false);
    }

    public function test_fuel_transaction_administration_links_to_supplier_fuel_invoices(): void
    {
        $this->get('/settings/fuel-transactions')
            ->assertOk()
            ->assertSee('/settings/supplier-fuel-invoices', false)
            ->assertSee('Dodavatelsk&#233; faktury PHM', false);
    }

    public function test_ui_does_not_mark_payment_or_execute_bank_matching(): void
    {
        $source = file_get_contents(resource_path('views/mvp/supplier-fuel-invoices.blade.php'));
        self::assertIsString($source);
        self::assertStringNotContainsString('/bank-matching', $source);
        self::assertStringNotContainsString('/payments', $source);
        self::assertStringNotContainsString('payment_marked:true', $source);
    }
}
