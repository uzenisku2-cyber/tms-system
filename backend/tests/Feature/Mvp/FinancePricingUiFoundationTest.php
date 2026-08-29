<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class FinancePricingUiFoundationTest extends TestCase
{
    public function test_finance_ui_exposes_customers_and_four_financial_areas(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'data-finance-panel="customers"',
            'data-finance-panel="price-lists"',
            'data-finance-panel="billing"',
            'data-finance-panel="comparison"',
            'data-finance-panel="profitability"',
        ] as $marker) {
            self::assertStringContainsString(
                $marker,
                $source,
            );
        }

        self::assertStringContainsString(
            'Odběratelé',
            $source,
        );

        $hardcodedCustomerBrand =
            "Z\u{00E1}silkovna";

        self::assertStringNotContainsString(
            $hardcodedCustomerBrand,
            $source,
        );

        self::assertStringContainsString(
            'data-customer-index-endpoint="/api/v1/customers"',
            $source,
        );
    }

    public function test_billing_price_list_form_is_customer_specific_and_uses_canonical_codes(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        self::assertStringContainsString(
            'data-provider-managed-price-list-endpoint="/api/v1/customers/{relationship}/price-lists"',
            $source,
        );

        self::assertStringContainsString(
            'billing-price-list-customer',
            $source,
        );

        self::assertStringContainsString(
            'billing-price-list-valid-from',
            $source,
        );

        self::assertStringContainsString(
            'billing-price-list-valid-until',
            $source,
        );

        foreach ([
            'delivered_parcels',
            'redirected_parcels',
            'undelivered_parcels',
            'actual_km',
        ] as $pricingCode) {
            self::assertStringContainsString(
                'data-pricing-code="'.$pricingCode.'"',
                $source,
            );
        }
    }

    public function test_profitability_copy_uses_gross_margin_not_net_profit(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        self::assertStringContainsString(
            'Hrubá marže Kč',
            $source,
        );

        self::assertStringContainsString(
            'Marže %',
            $source,
        );

        self::assertStringContainsString(
            'čistý zisk',
            $source,
        );
    }

    public function test_finance_ui_loads_customers_through_existing_api_helper(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'S021-03E READ-ONLY FINANCE CUSTOMER LOADER',
            'const loadFinanceCustomers = async () => {',
            'const loadFinanceCustomerDetail = async (',
            'renderFinanceCustomerDetail',
            'customerIndexEndpoint',
            "if (page === 'finance') {",
            'loadFinanceCustomers();',
            '[data-customer-list]',
            '[data-billing-price-list-customer]',
            '[data-customer-detail]',
            'select.dataset.financeDetailBound',
            "!== '1'",
        ] as $marker) {
            self::assertStringContainsString(
                $marker,
                $source,
            );
        }

        self::assertStringContainsString(
            'await api(',
            $source,
        );

        self::assertStringContainsString(
            '`/api/v1/customers/${encodeURIComponent(relationshipId)}`',
            $source,
        );

        self::assertStringContainsString(
            'data-billing-price-list-save',
            $source,
        );
    }

    public function test_finance_ui_can_create_customer_through_existing_api_helper(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'data-customer-create-form',
            'data-customer-registration-number',
            'data-customer-valid-from',
            'data-customer-create-submit',
            'data-customer-create-message',
            'S021-03M BROWSER CUSTOMER CREATION',
            'const bindFinanceCustomerCreate = () => {',
            "method: 'POST'",
            'relationship_valid_from:',
            'await loadFinanceCustomers();',
            'await loadFinanceCustomerDetail(',
            'bindFinanceCustomerCreate();',
        ] as $marker) {
            self::assertStringContainsString(
                $marker,
                $source,
            );
        }

        self::assertStringContainsString(
            "'/api/v1/customers'",
            $source,
        );

        self::assertStringContainsString(
            'await api(',
            $source,
        );

        self::assertStringContainsString(
            'Billing-price-list creation remains a separate workflow.',
            $source,
        );
    }

    public function test_finance_ui_creates_complete_billing_price_list_draft_atomically(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'data-provider-managed-price-list-endpoint="/api/v1/customers/{relationship}/price-lists"',
            'data-billing-price-list-save',
            'data-billing-price-list-message',
            'S021-03N ATOMIC BILLING DRAFT',
            'const bindFinanceBillingPriceListCreate = () => {',
            "method: 'POST'",
            'canonicalCodes = [',
            "'delivered_parcels'",
            "'redirected_parcels'",
            "'undelivered_parcels'",
            "'actual_km'",
            'change_reason:',
            'items,',
            'conditionalRules.length',
            'bindFinanceBillingPriceListCreate();',
        ] as $marker) {
            self::assertStringContainsString(
                $marker,
                $source,
            );
        }

        self::assertStringContainsString(
            'await loadFinanceCustomerDetail(',
            $source,
        );

        self::assertStringNotContainsString(
            'Browser zápis zatím není aktivován',
            $source,
        );

        self::assertIsInt(
            $billingFunctionStart = strpos(
                $source,
                'const bindFinanceBillingPriceListCreate = () => {',
            ),
        );

        self::assertIsInt(
            $billingFunctionEnd = strpos(
                $source,
                "\n            };",
                $billingFunctionStart,
            ),
        );

        $billingFunctionSource = substr(
            $source,
            $billingFunctionStart,
            $billingFunctionEnd
                + strlen("\n            };")
                - $billingFunctionStart,
        );

        self::assertStringNotContainsString(
            '/versions/1',
            $billingFunctionSource,
        );
    }

    public function test_billing_price_list_administration_is_primary_and_creation_is_separate(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'S024-02A PRICE-LIST ADMINISTRATION LAYOUT',
            'data-billing-price-list-admin',
            'data-billing-price-list-admin-list',
            'data-billing-price-list-admin-detail',
            'data-billing-price-list-create-open',
            'data-billing-price-list-create-card',
            'data-billing-price-list-create-close',
            'data-billing-price-list-reload',
            'data-billing-price-list-filter="all"',
            'data-billing-price-list-filter="current"',
            'data-billing-price-list-filter="draft"',
            'data-billing-price-list-filter="history"',
            'data-billing-price-list-count="all"',
            'data-billing-price-list-count="current"',
            'data-billing-price-list-count="draft"',
            'data-billing-price-list-count="history"',
            'Spr&#225;va faktura&#269;n&#237;ch cen&#237;k&#367;',
            'Zp&#283;t na p&#345;ehled',
        ] as $marker) {
            self::assertStringContainsString(
                $marker,
                $source,
            );
        }

        self::assertMatchesRegularExpression(
            '/data-billing-price-list-create-card\s+hidden/',
            $source,
        );
    }

    public function test_billing_price_list_administration_loads_real_records_and_versions(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'S024-03A BILLING PRICE-LIST ADMINISTRATION DATA',
            'let financeBillingPriceLists = [];',
            'const financeBillingPriceListCategory = (priceList) => {',
            'const renderFinanceBillingPriceListIndex = () => {',
            'const renderFinanceBillingPriceListDetail = (record) => {',
            'const loadFinanceBillingPriceLists = async (',
            'const bindFinanceBillingPriceListAdministration = () => {',
            'await loadFinanceBillingPriceLists(',
            'bindFinanceBillingPriceListAdministration();',
            '/api/v1/price-lists/${',
            '}/versions`',
            'data-billing-price-list-admin-list',
            'data-billing-price-list-admin-detail',
            'document.createElement',
            'financeBillingPriceListFilter',
        ] as $marker) {
            self::assertStringContainsString(
                $marker,
                $source,
            );
        }

        $start = strpos(
            $source,
            'S024-03A BILLING PRICE-LIST ADMINISTRATION DATA',
        );

        $end = strpos(
            $source,
            'const bindFinanceBillingPriceListCreate = () => {',
            $start,
        );

        self::assertIsInt($start);
        self::assertIsInt($end);

        $administrationSource = substr(
            $source,
            $start,
            $end - $start,
        );

        self::assertStringNotContainsString(
            '.innerHTML',
            $administrationSource,
        );
    }

    public function test_billing_draft_detail_is_localized_and_editable_atomically(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'S024-04B BILLING DRAFT EDITOR',
            'const financeBillingMoney = (',
            'const financeBillingUnitLabel = (unit) => ({',
            'const financeBillingEvaluationScopeLabel = (scope) => ({',
            'const financeBillingRuleFormula = (rule) => {',
            'const financeBillingBandLabel = (',
            'const hydrateFinanceConditionalRule = (',
            'const renderFinanceBillingPriceListEditor = (record) => {',
            "edit.textContent = 'Upravit koncept';",
            "save.textContent = 'Ulo\\u017eit zm\\u011bny konceptu';",
            "method: 'PUT'",
            'expected_lock_version:',
            'Number(current.lock_version)',
            'collectFinanceConditionalRules(',
            'await loadFinanceCustomers();',
            "'Revize'",
            "editor.dataset.billingPriceListEditor = '1';",
        ] as $marker) {
            self::assertStringContainsString(
                $marker,
                $source,
            );
        }

        self::assertSame(
            1,
            substr_count(
                $source,
                'S024-04B BILLING DRAFT EDITOR',
            ),
        );

        self::assertStringNotContainsString(
            'data.internal_organization_id',
            $source,
        );
    }

    public function test_billing_ui_administers_unlimited_conditional_surcharges(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'S023-04B UNLIMITED CONDITIONAL SURCHARGE UI',
            'data-conditional-rule-root',
            'data-conditional-rule-preset',
            'data-conditional-rule-add',
            'data-conditional-rule-list',
            'data-conditional-rule-code',
            'data-conditional-rule-name',
            'data-conditional-rule-metric-type',
            'data-conditional-rule-scope',
            'data-conditional-rule-reward-method',
            'data-conditional-numerator-source',
            'data-conditional-denominator-source',
            'data-conditional-band-add',
            'data-conditional-band-list',
            'data-conditional-band-adjustment',
            'const financeConditionalMetricSources = [',
            'const financeConditionalRulePresets = {',
            'const addFinanceConditionalRule = (',
            'const addFinanceConditionalBand = (',
            'const collectFinanceConditionalRules = (panel) => {',
            "addFinanceConditionalRule(panel, 'custom');",
            'value="monthly_price_list"',
            "'customer_rejected_parcels'",
            "'not_delivered_parcels'",
            "'processed_parcels'",
            'metric_numerator_sources: numeratorSources,',
            'metric_denominator_sources: denominatorSources,',
            'reward_quantity_source:',
            'reward_target_item_code:',
            'conditional_rules:',
            'Odmítnuto zákazníkem',
        ] as $marker) {
            self::assertStringContainsString(
                $marker,
                $source,
            );
        }

        self::assertStringNotContainsString(
            'conditionalRules.slice(',
            $source,
        );

        self::assertStringNotContainsString(
            'conditionalRules.length >',
            $source,
        );
    }

    public function test_finance_replacement_preserves_adjacent_renderers_and_template_initialization_order(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'const bank = () => `',
            'const imports = () => `',
            'const settings = () => `',
            'const templates = {',
        ] as $marker) {
            self::assertSame(
                1,
                substr_count($source, $marker),
                'Expected exactly one MVP renderer/template marker: '.$marker,
            );
        }

        $bankPosition = strpos($source, 'const bank = () => `');
        $importsPosition = strpos($source, 'const imports = () => `');
        $settingsPosition = strpos($source, 'const settings = () => `');
        $templatesPosition = strpos($source, 'const templates = {');

        self::assertIsInt($bankPosition);
        self::assertIsInt($importsPosition);
        self::assertIsInt($settingsPosition);
        self::assertIsInt($templatesPosition);

        self::assertTrue($bankPosition < $importsPosition);
        self::assertTrue($importsPosition < $settingsPosition);
        self::assertTrue($settingsPosition < $templatesPosition);

        self::assertStringContainsString(
            "Odb\u{011B}ratel\u{00E9}",
            $source,
        );

        self::assertStringContainsString(
            "Hrub\u{00E1} mar\u{017E}e K\u{010D}",
            $source,
        );
    }

    public function test_finance_ui_exposes_three_unified_price_list_administrations(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'price-list-tab-billing',
            'price-list-tab-drivers',
            'price-list-tab-external-carriers',
            'Fakturační ceníky',
            'Ceníky řidičů',
            'Ceníky externích dopravců',
            'data-unified-price-list-domain="billing"',
            'data-unified-price-list-domain="driver"',
            'data-unified-price-list-domain="external-carrier"',
            'data-external-carrier-index-endpoint="/api/v1/external-carriers"',
            'data-external-carrier-store-endpoint="/api/v1/external-carriers/{relationship}/price-lists"',
            'data-external-carrier-price-list-list',
            'data-external-carrier-price-list-detail',
            'const bindFinanceUnifiedPriceListAdministration = () => {',
            'bindFinanceUnifiedPriceListAdministration();',
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }

        self::assertSame(
            3,
            substr_count($source, 'class="drayvia-price-list-tab-input"'),
        );

        self::assertSame(
            3,
            substr_count(
                $source,
                'class="drayvia-price-list-panel drayvia-price-list-panel-',
            ),
        );

        self::assertSame(
            3,
            substr_count($source, 'data-unified-price-list-domain="'),
        );

        self::assertSame(
            12,
            substr_count($source, 'data-unified-price-list-filter="'),
        );
    }

    public function test_unified_price_list_overviews_load_filter_and_render_real_records(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'let financeDriverPriceLists = [];',
            'const financeUnifiedPriceListCategory = (priceList) => {',
            'const updateFinanceUnifiedPriceListCounts = (',
            'const renderFinanceDriverPriceListIndex = () => {',
            'const bindFinanceDriverPriceListAdministration = () => {',
            'let financeExternalCarrierPriceLists = [];',
            'const renderFinanceExternalCarrierPriceListDetail = (record) => {',
            'const renderFinanceExternalCarrierPriceListIndex = () => {',
            'const loadFinanceExternalCarrierPriceLists = async () => {',
            'const bindFinanceExternalCarrierPriceListAdministration = () => {',
            'root.dataset.unifiedPriceListFilter ||',
            'data-external-carrier-index-endpoint="/api/v1/external-carriers"',
            'bindFinanceDriverPriceListAdministration();',
            'bindFinanceExternalCarrierPriceListAdministration();',
            'loadFinanceExternalCarrierPriceLists();',
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }

        self::assertSame(
            4,
            substr_count(
                $source,
                'updateFinanceUnifiedPriceListCounts(',
            ),
        );
    }

    public function test_external_carrier_editor_reuses_complete_billing_draft_contract(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'let financeExternalCarrierRelationships = [];',
            'const ensureFinanceExternalCarrierPriceListCreateCard = () => {',
            'template.cloneNode(true)',
            'data-external-carrier-price-list-create-card',
            'const populateFinanceExternalCarrierPriceListSelect = () => {',
            'const bindFinanceExternalCarrierPriceListCreate = () => {',
            'collectFinanceConditionalRules(createCard)',
            'resetFinanceConditionalRules(createCard);',
            '.externalCarrierStoreEndpoint',
            "method: 'POST'",
            'conditional_rules:',
            'financeExternalCarrierRelationships = records;',
            'bindFinanceExternalCarrierPriceListCreate();',
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }

        self::assertSame(
            1,
            substr_count(
                $source,
                'const bindFinanceExternalCarrierPriceListCreate = () => {',
            ),
        );
    }

    public function test_driver_price_list_overview_renders_complete_version_detail(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'const ensureFinanceDriverPriceListDetail = () => {',
            'data-driver-price-list-detail',
            'const renderFinanceDriverPriceListDetail = (record) => {',
            'const loadFinanceDriverPriceListDetail = async (priceList) => {',
            '/versions`',
            'financeBillingDetailTable(',
            "'Podm\\u00edn\\u011bn\\u00e9 p\\u0159\\u00edplatky'",
            "'Historie verz\\u00ed'",
            'loadFinanceDriverPriceListDetail(priceList);',
            "detailButton.textContent = 'Otev\\u0159\\u00edt';",
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }

        self::assertSame(
            1,
            substr_count(
                $source,
                'const loadFinanceDriverPriceListDetail = async (priceList) => {',
            ),
        );
    }

    public function test_driver_active_or_expired_tariff_can_create_complete_draft_version(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'createVersion = false',
            'editor.dataset.driverPriceListEditorMode =',
            "createVersion ? 'create-version' : 'update'",
            "newVersion.textContent = 'Nov\\u00e1 verze';",
            'renderFinanceDriverPriceListEditor(',
            'record,',
            'true',
            "current?.status === 'active'",
            "current?.status === 'expired'",
            'expected_current_version:',
            'Number(current.version_number)',
            "method: createVersion ? 'POST' : 'PUT'",
            'items: updatedItems',
            'conditional_rules:',
            'conditionalRules',
            "? 'Koncept nov\\u00e9 verze byl vytvo\\u0159en.'",
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }

        self::assertSame(
            1,
            substr_count(
                $source,
                "newVersion.textContent = 'Nov\\u00e1 verze';",
            ),
        );
    }

    public function test_driver_lifecycle_actions_are_explicit_and_revision_guarded(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'const runFinanceDriverPriceListLifecycle = async (',
            "approve.textContent = 'Schv\\u00e1lit';",
            "activate.textContent = 'Aktivovat';",
            "expire.textContent = 'Ukon\\u010dit platnost';",
            "requiredStatus: 'draft'",
            "requiredStatus: 'approved'",
            "requiredStatus: 'active'",
            "path: 'approve'",
            "path: 'activate'",
            "path: 'expire'",
            'expected_lock_version: lockVersion',
            'payload.valid_until = validUntil;',
            'if (!window.confirm(contract.question)) {',
            "method: 'POST'",
            '${contract.path}`',
            'await loadFinanceDriverPriceLists();',
            'await loadFinanceDriverPriceListDetail(',
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }

        self::assertStringNotContainsString(
            '/versions/1/approve',
            $source,
        );
        self::assertStringNotContainsString(
            '/versions/1/activate',
            $source,
        );
    }

    public function test_driver_draft_detail_is_editable_atomically(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'const renderFinanceDriverPriceListEditor = (',
            "edit.textContent = 'Upravit koncept';",
            'renderFinanceDriverPriceListEditor(record);',
            "editor.dataset.driverPriceListEditor = '1';",
            'hydrateFinanceConditionalRule(editor, rule);',
            'collectFinanceConditionalRules(editor)',
            "method: 'PUT'",
            'expected_lock_version:',
            'Number(current.lock_version)',
            '/api/v1/driver-price-lists/${',
            'await loadFinanceDriverPriceLists();',
            'const updatedRecord =',
            'await loadFinanceDriverPriceListDetail(',
            "save.textContent = 'Ulo\\u017eit zm\\u011bny konceptu';",
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }

        self::assertSame(
            1,
            substr_count(
                $source,
                'const renderFinanceDriverPriceListEditor = (',
            ),
        );
    }

    public function test_external_carrier_ui_matches_complete_billing_lifecycle(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'const renderFinanceExternalCarrierPriceListEditor = (',
            'createVersion = false',
            "editor.dataset.externalCarrierPriceListEditor = '1';",
            "createVersion ? 'create-version' : 'update'",
            'hydrateFinanceConditionalRule(editor, rule);',
            'collectFinanceConditionalRules(editor)',
            '/api/v1/external-carriers/${',
            '}/price-lists/${',
            'expected_current_version:',
            'expected_lock_version:',
            "method: createVersion ? 'POST' : 'PUT'",
            'const runFinanceExternalCarrierPriceListLifecycle = async (',
            "path: 'approve'",
            "path: 'activate'",
            "path: 'expire'",
            "approve.textContent = 'Schv\\u00e1lit';",
            "activate.textContent = 'Aktivovat';",
            "expire.textContent = 'Ukon\\u010dit platnost';",
            "newVersion.textContent = 'Nov\\u00e1 verze';",
            'ratesTitle.textContent =',
            'rulesTitle.textContent =',
            'versionsTitle.textContent =',
            'financeBillingPriceListPeriod(current),',
            'metric_numerator_sources',
            'metric_denominator_sources',
            'await loadFinanceExternalCarrierPriceLists();',
        ] as $marker) {
            self::assertStringContainsString($marker, $source);
        }

        self::assertSame(
            1,
            substr_count(
                $source,
                'const renderFinanceExternalCarrierPriceListEditor = (',
            ),
        );
        self::assertSame(
            1,
            substr_count(
                $source,
                'const runFinanceExternalCarrierPriceListLifecycle = async (',
            ),
        );
        self::assertSame(
            1,
            substr_count(
                $source,
                'const renderFinanceExternalCarrierPriceListDetail = (record) => {',
            ),
        );
    }

    public function test_driver_price_list_web_ui_creates_complete_draft_without_implicit_activation(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach ([
            'S022-MVP-01 DRIVER PRICE LIST WEB UI',
            'data-driver-price-list-root',
            'data-driver-price-list-assignment',
            'data-driver-price-list-name',
            'data-driver-price-list-valid-from',
            'data-driver-price-list-valid-until',
            'data-driver-price-list-rate="delivered_parcels"',
            'data-driver-price-list-rate="redirected_parcels"',
            'data-driver-price-list-rate="undelivered_parcels"',
            'data-driver-price-list-rate="actual_km"',
            'data-driver-price-list-save',
            'data-driver-price-list-message',
            'data-driver-price-list-list',
            'const loadFinanceDriverAssignments = async () => {',
            'const loadFinanceDriverPriceLists = async () => {',
            'const ensureFinanceDriverPriceListCreateCard = () => {',
            'const populateFinanceDriverPriceListSelect = () => {',
            'const bindFinanceDriverPriceListCreate = () => {',
            "'/api/v1/driver-price-lists'",
            'collectFinanceConditionalRules(createCard)',
            'resetFinanceConditionalRules(createCard);',
            'conditional_rules:',
            'data-driver-price-list-create-open',
            'data-driver-price-list-create-card',
            'bindFinanceDriverPriceListCreate();',
            'loadFinanceDriverAssignments();',
            'loadFinanceDriverPriceLists();',
        ] as $marker) {
            self::assertStringContainsString(
                $marker,
                $source,
            );
        }

        self::assertStringNotContainsString(
            '/versions/1/approve',
            $source,
        );

        self::assertStringNotContainsString(
            '/versions/1/activate',
            $source,
        );

        self::assertSame(
            4,
            substr_count(
                $source,
                'data-driver-price-list-rate="',
            ),
        );
    }
}
