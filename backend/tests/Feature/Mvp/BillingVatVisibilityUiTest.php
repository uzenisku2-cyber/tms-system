<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class BillingVatVisibilityUiTest extends TestCase
{
    public function test_finance_ui_loads_role_scoped_billing_overview(): void
    {
        $source = file_get_contents(resource_path('views/mvp/app.blade.php'));

        self::assertIsString($source);

        foreach ([
            'S033-02A ROLE-SCOPED BILLING, VAT AND MARGIN OVERVIEW',
            'data-billing-overview-root',
            'data-billing-overview-endpoint="/api/v1/billing-overview"',
            'data-billing-filter-form',
            'data-billing-period-from',
            'data-billing-period-until',
            'data-billing-document-type',
            'const loadBillingOverview = async (page = 1) => {',
            'const renderBillingOverview = (data) => {',
            'bindBillingOverview();',
            'loadBillingOverview();',
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }
    }

    public function test_billing_overview_exposes_status_and_page_controls(): void
    {
        $source = file_get_contents(resource_path('views/mvp/app.blade.php'));

        self::assertIsString($source);
        self::assertStringContainsString('data-billing-document-status', $source);
        self::assertStringContainsString('data-billing-pagination', $source);
        self::assertStringContainsString('data-billing-page', $source);
        self::assertStringContainsString("params.set('page', String(page))", $source);
    }

    public function test_company_view_distinguishes_net_vat_gross_costs_and_margin(): void
    {
        $source = file_get_contents(resource_path('views/mvp/app.blade.php'));

        self::assertIsString($source);

        foreach ([
            'Fakturace odběratelům bez DPH',
            'DPH na výstupu',
            'Externí dopravci – konečný náklad',
            'DPH se neuplatňuje',
            'Odměny řidičů',
            'Hrubá marže bez DPH',
            'summary.customer_billing',
            'summary.external_carrier_cost',
            'summary.driver_cost',
            'summary.gross_margin_net',
            'item.net_amount',
            'item.vat_amount',
            'item.gross_amount',
            'billingStatusLabel(item.status)',
            "approved: 'Schváleno'",
            'billingVatRate(item.vat_rate)',
            'minimumFractionDigits: 2',
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }

        self::assertStringContainsString(
            "'DPH se neuplatňuje',\n                    false",
            $source,
        );
        self::assertStringNotContainsString(
            "summary.external_carrier_cost,\n                    'DPH'",
            $source,
        );
    }

    public function test_own_view_renders_only_generic_amount_and_restricts_company_panels(): void
    {
        $source = file_get_contents(resource_path('views/mvp/app.blade.php'));

        self::assertIsString($source);

        foreach ([
            "data.visibility !== 'company'",
            "data.visibility === 'company'",
            'item.amount',
            'Vidíte pouze vlastní vyúčtování nebo odměnu.',
            'Údaje o DPH, firemních nákladech a marži API neposkytuje.',
            'Firemní marže a daňové údaje jsou dostupné pouze hlavnímu dopravci.',
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }

        self::assertStringContainsString(
            "const companyView = data.visibility === 'company';",
            $source,
        );
        self::assertStringContainsString(
            ': `<td>${billingMoney(item.amount, item.currency)}</td>`;',
            $source,
        );
    }

    public function test_billing_filters_follow_the_global_period_selector(): void
    {
        $source = file_get_contents(resource_path('views/mvp/app.blade.php'));

        self::assertIsString($source);

        foreach ([
            'const billingGlobalPeriodRange = () => {',
            "periodMode === 'all'",
            "periodMode === 'current_month'",
            "periodMode === 'current_year'",
            'const syncBillingFiltersWithGlobalPeriod = () => {',
            'syncBillingFiltersWithGlobalPeriod();',
            'periodFrom.value = range.from',
            'periodUntil.value = range.until',
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }
    }

    public function test_billing_renders_only_available_year_month_and_quarter_filters(): void
    {
        $source = file_get_contents(resource_path('views/mvp/app.blade.php'));

        self::assertIsString($source);

        foreach ([
            'Dostupná období fakturace',
            'data-billing-available-years',
            'data-billing-available-quarters',
            'data-billing-available-months',
            'data-billing-period-preset="all"',
            'data-billing-period-preset="custom"',
            'let selectedMonth = currentMonthValue();',
            'let selectedYear = currentYearValue();',
            'const billingPresetRange = (preset) => {',
            'const renderBillingPeriodNavigation = (data) => {',
            'data.available_periods?.years',
            'data.available_periods?.months',
            "preset.startsWith('year:')",
            "preset.startsWith('quarter:')",
            "preset.startsWith('month:')",
            'const applyBillingPeriodPreset = (preset) => {',
            'loadBillingOverview();',
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }
    }

    public function test_customer_invoice_workspace_is_company_scoped_and_has_separate_draft_and_issue_commands(): void
    {
        $source = file_get_contents(resource_path('views/mvp/app.blade.php'));

        self::assertIsString($source);
        foreach ([
            'data-customer-invoice-workspace',
            'data-customer-invoice-draft-form',
            'data-customer-invoice-issue-form',
            'data-customer-invoice-preview',
            "invoicePanel.hidden = data.visibility !== 'company'",
            "await api('/api/v1/customer-invoices'",
            '}/issue`',
            'renderInvoiceDetail(detail)',
            'bindCustomerInvoiceWorkspace();',
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }
    }

    public function test_invoice_workspace_loads_customers_and_calculations_and_opens_invoice_detail(): void
    {
        $source = file_get_contents(resource_path('views/mvp/app.blade.php'));

        self::assertIsString($source);
        foreach ([
            'data-invoice-customer-select',
            'data-invoice-calculation-choices',
            'data-invoice-load-more',
            'data-invoice-open=',
            'const loadInvoiceCustomers = async () => {',
            'const loadInvoiceCalculations = async (reset = false) => {',
            "['approved', 'closed'].includes(item.status)",
            'invoiceState.selectedIds',
            'renderInvoiceDetail(invoice)',
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }
    }
}
