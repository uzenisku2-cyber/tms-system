<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Core\Organizations\OrganizationContext;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\PriceListConditionalRule;
use App\Modules\Pricing\Services\ConditionalPricingEngine;
use App\Modules\Pricing\Services\ConditionalPricingRuleEvaluator;
use App\Modules\Pricing\Services\ConditionalPricingScopeAggregator;
use App\Modules\Pricing\Services\FinancialCalculationCurrentSourceResolver;
use App\Modules\Pricing\Services\FinancialConditionalAdjustmentApplicationService;
use App\Modules\Pricing\Services\FinancialConditionalAdjustmentPersistenceService;
use App\Modules\Pricing\Services\FinancialConditionalScopeSourceResolver;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

final class FinancialConditionalAdjustmentApplicationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        self::assertSame(
            'sqlite',
            DB::connection()->getDriverName(),
            'This test must never use persistent PostgreSQL.',
        );

        $this->organizationContext()->clear();
        Schema::dropAllTables();
        $this->createSchema();
        $this->createFoundation();
        $this->organizationContext()->set(2);
    }

    protected function tearDown(): void
    {
        $this->organizationContext()->clear();
        Schema::dropAllTables();

        parent::tearDown();
    }

    public function test_initial_application_path_resolves_complete_scope_and_persists_ordered_sources(): void
    {
        $firstReport = $this->createReport(
            '2026-08-01',
        );

        $secondReport = $this->createReport(
            '2026-08-10',
        );

        $firstCalculation = $this->createCalculation(
            dailyReportId: $firstReport,
            serviceDate: '2026-08-01',
            loaded: 100,
            redirected: 20,
        );

        $secondCalculation = $this->createCalculation(
            dailyReportId: $secondReport,
            serviceDate: '2026-08-10',
            loaded: 50,
            redirected: 10,
        );

        $adjustment = $this->service()
            ->createInitialMonthlyDriverAdjustment(
                conditionalRuleId: 300,
                performedByDriverId: 10,
                calendarMonth: '2026-08',
                calculatedByUserId: 900,
                calculatedAt: CarbonImmutable::parse(
                    '2026-09-01 08:00:00',
                ),
            );

        self::assertSame(
            300,
            (int) $adjustment->getAttribute(
                'price_list_conditional_rule_id',
            ),
        );

        self::assertSame(
            10,
            (int) $adjustment->getAttribute(
                'performed_by_driver_id',
            ),
        );

        self::assertSame(
            '2026-08-01',
            $adjustment->getAttribute(
                'period_start',
            )->format('Y-m-d'),
        );

        self::assertSame(
            '2026-08-31',
            $adjustment->getAttribute(
                'period_end',
            )->format('Y-m-d'),
        );

        self::assertSame(
            1,
            (int) $adjustment->getAttribute(
                'calculation_version',
            ),
        );

        self::assertSame(
            [
                $firstCalculation,
                $secondCalculation,
            ],
            DB::table(
                'financial_conditional_adjustment_sources',
            )
                ->where(
                    'financial_conditional_adjustment_id',
                    $adjustment->getKey(),
                )
                ->orderBy('source_position')
                ->pluck('financial_calculation_id')
                ->map(
                    static fn (mixed $value): int => (int) $value,
                )
                ->all(),
        );

        self::assertSame(
            1,
            DB::table(
                'financial_conditional_adjustments',
            )->count(),
        );
    }

    public function test_high_level_application_api_does_not_accept_arbitrary_source_ids(): void
    {
        $initial = new ReflectionMethod(
            FinancialConditionalAdjustmentApplicationService::class,
            'createInitialMonthlyDriverAdjustment',
        );

        self::assertSame(
            [
                'conditionalRuleId',
                'performedByDriverId',
                'calendarMonth',
                'calculatedByUserId',
                'calculatedAt',
            ],
            array_map(
                static fn ($parameter): string => $parameter->getName(),
                $initial->getParameters(),
            ),
        );

        $recalculation = new ReflectionMethod(
            FinancialConditionalAdjustmentApplicationService::class,
            'recalculateMonthlyDriverAdjustment',
        );

        self::assertSame(
            [
                'supersedesAdjustmentId',
                'calculatedByUserId',
                'calculatedAt',
            ],
            array_map(
                static fn ($parameter): string => $parameter->getName(),
                $recalculation->getParameters(),
            ),
        );
    }

    public function test_recalculation_re_resolves_current_route_sources_and_preserves_old_membership(): void
    {
        $firstReport = $this->createReport(
            '2026-08-01',
        );

        $secondReport = $this->createReport(
            '2026-08-10',
        );

        $firstCalculation = $this->createCalculation(
            dailyReportId: $firstReport,
            serviceDate: '2026-08-01',
            loaded: 100,
            redirected: 20,
        );

        $secondCalculationV1 = $this->createCalculation(
            dailyReportId: $secondReport,
            serviceDate: '2026-08-10',
            loaded: 50,
            redirected: 10,
        );

        $v1 = $this->service()
            ->createInitialMonthlyDriverAdjustment(
                conditionalRuleId: 300,
                performedByDriverId: 10,
                calendarMonth: '2026-08',
                calculatedByUserId: 900,
                calculatedAt: CarbonImmutable::parse(
                    '2026-09-01 08:00:00',
                ),
            );

        DB::table('daily_reports')
            ->where('id', $secondReport)
            ->update([
                'current_version' => 2,
            ]);

        $secondCalculationV2 = $this->createCalculation(
            dailyReportId: $secondReport,
            serviceDate: '2026-08-10',
            loaded: 50,
            redirected: 15,
            dailyReportVersion: 2,
            calculationVersion: 2,
            supersedesCalculationId: $secondCalculationV1,
        );

        $v2 = $this->service()
            ->recalculateMonthlyDriverAdjustment(
                supersedesAdjustmentId: (int) $v1->getKey(),
                calculatedByUserId: 900,
                calculatedAt: CarbonImmutable::parse(
                    '2026-09-02 08:00:00',
                ),
            );

        self::assertSame(
            2,
            (int) $v2->getAttribute(
                'calculation_version',
            ),
        );

        self::assertSame(
            (int) $v1->getKey(),
            (int) $v2->getAttribute(
                'supersedes_adjustment_id',
            ),
        );

        self::assertSame(
            [
                $firstCalculation,
                $secondCalculationV1,
            ],
            $this->adjustmentSourceIds(
                (int) $v1->getKey(),
            ),
        );

        self::assertSame(
            [
                $firstCalculation,
                $secondCalculationV2,
            ],
            $this->adjustmentSourceIds(
                (int) $v2->getKey(),
            ),
        );

        self::assertSame(
            2,
            DB::table(
                'financial_conditional_adjustments',
            )->count(),
        );
    }

    public function test_recalculation_rejects_resolved_period_drift_atomically(): void
    {
        $firstReport = $this->createReport(
            '2026-08-01',
        );

        $secondReport = $this->createReport(
            '2026-08-10',
        );

        $this->createCalculation(
            dailyReportId: $firstReport,
            serviceDate: '2026-08-01',
            loaded: 100,
            redirected: 20,
        );

        $this->createCalculation(
            dailyReportId: $secondReport,
            serviceDate: '2026-08-10',
            loaded: 50,
            redirected: 10,
        );

        $v1 = $this->service()
            ->createInitialMonthlyDriverAdjustment(
                conditionalRuleId: 300,
                performedByDriverId: 10,
                calendarMonth: '2026-08',
                calculatedByUserId: 900,
                calculatedAt: CarbonImmutable::parse(
                    '2026-09-01 08:00:00',
                ),
            );

        DB::table('price_list_versions')
            ->where('id', 200)
            ->update([
                'valid_from' => '2026-08-05',
            ]);

        try {
            $this->service()
                ->recalculateMonthlyDriverAdjustment(
                    supersedesAdjustmentId: (int) $v1->getKey(),
                    calculatedByUserId: 900,
                    calculatedAt: CarbonImmutable::parse(
                        '2026-09-02 08:00:00',
                    ),
                );

            self::fail(
                'Expected resolved-period drift to be rejected.',
            );
        } catch (DomainException $exception) {
            self::assertSame(
                (
                    'The resolved conditional scope period changed '
                    .'before persistence completed.'
                ),
                $exception->getMessage(),
            );
        }

        self::assertSame(
            1,
            DB::table(
                'financial_conditional_adjustments',
            )->count(),
        );

        self::assertSame(
            2,
            DB::table(
                'financial_conditional_adjustment_sources',
            )->count(),
        );
    }

    public function test_initial_per_route_application_path_persists_exact_route_source(): void
    {
        DB::table('price_list_conditional_rules')
            ->where('id', 300)
            ->update([
                'evaluation_scope' => PriceListConditionalRule::EVALUATION_SCOPE_PER_ROUTE,
            ]);

        $report = $this->createReport(
            '2026-08-15',
        );

        $calculation = $this->createCalculation(
            dailyReportId: $report,
            serviceDate: '2026-08-15',
            loaded: 100,
            redirected: 20,
        );

        $adjustment = $this->service()
            ->createInitialPerRouteAdjustment(
                conditionalRuleId: 300,
                dailyReportId: $report,
                calculatedByUserId: 900,
                calculatedAt: CarbonImmutable::parse(
                    '2026-08-16 08:00:00',
                ),
            );

        self::assertSame(
            PriceListConditionalRule::EVALUATION_SCOPE_PER_ROUTE,
            $adjustment->getAttribute('evaluation_scope'),
        );

        self::assertSame(
            '2026-08-15',
            $adjustment->getAttribute(
                'period_start',
            )->format('Y-m-d'),
        );

        self::assertSame(
            '2026-08-15',
            $adjustment->getAttribute(
                'period_end',
            )->format('Y-m-d'),
        );

        self::assertSame(
            [$calculation],
            $this->adjustmentSourceIds(
                (int) $adjustment->getKey(),
            ),
        );

        self::assertSame(
            1,
            (int) $adjustment->getAttribute(
                'calculation_version',
            ),
        );
    }

    public function test_per_route_recalculation_re_resolves_current_route_source_and_preserves_old_membership(): void
    {
        DB::table('price_list_conditional_rules')
            ->where('id', 300)
            ->update([
                'evaluation_scope' => PriceListConditionalRule::EVALUATION_SCOPE_PER_ROUTE,
            ]);

        $report = $this->createReport(
            '2026-08-15',
        );

        $calculationV1 = $this->createCalculation(
            dailyReportId: $report,
            serviceDate: '2026-08-15',
            loaded: 100,
            redirected: 20,
        );

        $v1 = $this->service()
            ->createInitialPerRouteAdjustment(
                conditionalRuleId: 300,
                dailyReportId: $report,
                calculatedByUserId: 900,
                calculatedAt: CarbonImmutable::parse(
                    '2026-08-16 08:00:00',
                ),
            );

        DB::table('daily_reports')
            ->where('id', $report)
            ->update([
                'current_version' => 2,
            ]);

        $calculationV2 = $this->createCalculation(
            dailyReportId: $report,
            serviceDate: '2026-08-15',
            loaded: 100,
            redirected: 25,
            dailyReportVersion: 2,
            calculationVersion: 2,
            supersedesCalculationId: $calculationV1,
        );

        $v2 = $this->service()
            ->recalculatePerRouteAdjustment(
                supersedesAdjustmentId: (int) $v1->getKey(),
                calculatedByUserId: 900,
                calculatedAt: CarbonImmutable::parse(
                    '2026-08-17 08:00:00',
                ),
            );

        self::assertSame(
            [$calculationV1],
            $this->adjustmentSourceIds(
                (int) $v1->getKey(),
            ),
        );

        self::assertSame(
            [$calculationV2],
            $this->adjustmentSourceIds(
                (int) $v2->getKey(),
            ),
        );

        self::assertSame(
            2,
            (int) $v2->getAttribute(
                'calculation_version',
            ),
        );

        self::assertSame(
            (int) $v1->getKey(),
            (int) $v2->getAttribute(
                'supersedes_adjustment_id',
            ),
        );

        self::assertSame(
            '2026-08-15',
            $v2->getAttribute(
                'period_start',
            )->format('Y-m-d'),
        );

        self::assertSame(
            '2026-08-15',
            $v2->getAttribute(
                'period_end',
            )->format('Y-m-d'),
        );
    }

    public function test_per_route_application_api_does_not_accept_arbitrary_source_ids(): void
    {
        $initial = new ReflectionMethod(
            FinancialConditionalAdjustmentApplicationService::class,
            'createInitialPerRouteAdjustment',
        );

        self::assertSame(
            [
                'conditionalRuleId',
                'dailyReportId',
                'calculatedByUserId',
                'calculatedAt',
            ],
            array_map(
                static fn ($parameter): string => $parameter->getName(),
                $initial->getParameters(),
            ),
        );

        $recalculation = new ReflectionMethod(
            FinancialConditionalAdjustmentApplicationService::class,
            'recalculatePerRouteAdjustment',
        );

        self::assertSame(
            [
                'supersedesAdjustmentId',
                'calculatedByUserId',
                'calculatedAt',
            ],
            array_map(
                static fn ($parameter): string => $parameter->getName(),
                $recalculation->getParameters(),
            ),
        );
    }

    /** @return list<int> */
    private function adjustmentSourceIds(
        int $adjustmentId,
    ): array {
        return DB::table(
            'financial_conditional_adjustment_sources',
        )
            ->where(
                'financial_conditional_adjustment_id',
                $adjustmentId,
            )
            ->orderBy('source_position')
            ->pluck('financial_calculation_id')
            ->map(
                static fn (mixed $value): int => (int) $value,
            )
            ->all();
    }

    private function service(): FinancialConditionalAdjustmentApplicationService
    {
        return new FinancialConditionalAdjustmentApplicationService(
            $this->organizationContext(),
            new FinancialConditionalScopeSourceResolver(
                $this->organizationContext(),
                new FinancialCalculationCurrentSourceResolver,
            ),
            new FinancialConditionalAdjustmentPersistenceService(
                $this->organizationContext(),
                new ConditionalPricingEngine(
                    new ConditionalPricingScopeAggregator,
                    new ConditionalPricingRuleEvaluator,
                ),
            ),
        );
    }

    private function organizationContext(): OrganizationContext
    {
        return app(OrganizationContext::class);
    }

    private function createFoundation(): void
    {
        DB::table('organization_relationships')->insert([
            'id' => 20,
            'source_organization_id' => 1,
            'target_organization_id' => 2,
            'relationship_type' => 'subcontracting',
            'status' => 'active',
            'valid_from' => '2025-06-01 00:00:00',
            'valid_until' => null,
        ]);

        DB::table('price_lists')->insert([
            'id' => 100,
            'organization_relationship_id' => 20,
            'owner_organization_id' => 1,
            'customer_organization_id' => 1,
            'provider_organization_id' => 2,
            'currency' => 'CZK',
            'status' => 'active',
            'current_version' => 1,
        ]);

        DB::table('price_list_versions')->insert([
            'id' => 200,
            'price_list_id' => 100,
            'version_number' => 1,
            'status' => 'active',
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'organization_relationship_id' => 20,
        ]);

        DB::table(
            'price_list_conditional_rules',
        )->insert([
            'id' => 300,
            'price_list_version_id' => 200,
            'code' => 'monthly-redirect-share',
            'name' => 'Monthly redirect share',
            'description' => null,
            'metric_type' => PriceListConditionalRule::METRIC_TYPE_RATIO_PERCENTAGE,
            'metric_numerator_source' => PriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
            'metric_denominator_source' => PriceListConditionalRule::SOURCE_LOADED_PARCELS,
            'evaluation_scope' => PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_DRIVER,
            'reward_method' => PriceListConditionalRule::REWARD_METHOD_AMOUNT_PER_UNIT,
            'reward_quantity_source' => PriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
            'reward_target_item_code' => null,
            'rounding_scale' => 2,
            'rounding_method' => 'half_up',
            'position' => 1,
            'created_at' => '2026-08-13 00:00:00',
        ]);

        DB::table(
            'price_list_conditional_bands',
        )->insert([
            'id' => 400,
            'price_list_conditional_rule_id' => 300,
            'minimum_value' => '0.0000',
            'maximum_value' => '100.0000',
            'minimum_inclusive' => true,
            'maximum_inclusive' => true,
            'adjustment_value' => '1.5000',
            'position' => 1,
            'created_at' => '2026-08-13 00:00:00',
        ]);

        DB::table(
            'driver_organization_assignments',
        )->insert([
            'driver_id' => 10,
            'organization_id' => 2,
            'valid_from' => '2025-06-01',
            'valid_until' => null,
        ]);
    }

    private function createReport(
        string $serviceDate,
        int $currentVersion = 1,
    ): int {
        return DB::table('daily_reports')
            ->insertGetId([
                'organization_id' => 1,
                'performed_by_driver_id' => 10,
                'service_date' => $serviceDate,
                'status' => DailyReport::STATUS_APPROVED,
                'current_version' => $currentVersion,
                'deleted_at' => null,
            ]);
    }

    private function createCalculation(
        int $dailyReportId,
        string $serviceDate,
        int $loaded,
        int $redirected,
        int $dailyReportVersion = 1,
        int $calculationVersion = 1,
        ?int $supersedesCalculationId = null,
    ): int {
        $snapshot = [
            'daily_report_id' => $dailyReportId,
            'daily_report_version' => $dailyReportVersion,
            'public_id' => (string) Str::uuid(),
            'organization_id' => 1,
            'trip_id' => null,
            'performed_by_driver_id' => 10,
            'vehicle_id' => null,
            'route_number' => (string) $dailyReportId,
            'route_number_normalized' => (string) $dailyReportId,
            'service_date' => $serviceDate,
            'status' => DailyReport::STATUS_APPROVED,
            'loaded_parcels' => $loaded,
            'delivered_parcels' => $loaded - $redirected,
            'redirected_parcels' => $redirected,
            'undelivered_parcels' => 0,
            'customer_rejected_parcels' => 0,
            'not_delivered_parcels' => 0,
            'processed_parcels' => $loaded,
            'planned_km' => '0.00',
            'actual_km' => '0.00',
            'actual_km_source' => null,
            'approved_at' => $serviceDate.' 20:00:00',
            'approved_by_user_id' => 900,
            'closed_at' => null,
            'captured_at' => $serviceDate.' 20:05:00',
        ];

        return DB::table(
            'financial_calculations',
        )->insertGetId([
            'public_id' => (string) Str::uuid(),
            'organization_id' => 2,
            'organization_relationship_id' => 20,
            'price_list_id' => 100,
            'price_list_version_id' => 200,
            'daily_report_id' => $dailyReportId,
            'daily_report_version' => $dailyReportVersion,
            'calculation_version' => $calculationVersion,
            'status' => FinancialCalculation::STATUS_APPROVED,
            'currency' => 'CZK',
            'input_snapshot' => json_encode(
                $snapshot,
                JSON_THROW_ON_ERROR,
            ),
            'subtotal_amount' => '0.00',
            'total_amount' => '0.00',
            'supersedes_calculation_id' => $supersedesCalculationId,
            'created_at' => '2026-08-13 00:00:00',
            'updated_at' => '2026-08-13 00:00:00',
        ]);
    }

    private function createSchema(): void
    {
        Schema::create(
            'organization_relationships',
            static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger(
                    'source_organization_id',
                );
                $table->unsignedBigInteger(
                    'target_organization_id',
                );
                $table->string('relationship_type');
                $table->string('status');
                $table->timestamp('valid_from')->nullable();
                $table->timestamp('valid_until')->nullable();
            },
        );

        Schema::create(
            'price_lists',
            static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger(
                    'organization_relationship_id',
                );
                $table->unsignedBigInteger(
                    'owner_organization_id',
                );
                $table->unsignedBigInteger(
                    'customer_organization_id',
                );
                $table->unsignedBigInteger(
                    'provider_organization_id',
                );
                $table->char('currency', 3);
                $table->string('status');
                $table->unsignedInteger('current_version');
            },
        );

        Schema::create(
            'price_list_versions',
            static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger(
                    'price_list_id',
                );
                $table->unsignedInteger(
                    'version_number',
                );
                $table->string('status');
                $table->date('valid_from')->nullable();
                $table->date('valid_until')->nullable();
                $table->unsignedBigInteger(
                    'organization_relationship_id',
                );
            },
        );

        Schema::create(
            'price_list_conditional_rules',
            static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger(
                    'price_list_version_id',
                );
                $table->string('code');
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('metric_type');
                $table->string(
                    'metric_numerator_source',
                );
                $table->string(
                    'metric_denominator_source',
                )->nullable();
                $table->string('evaluation_scope');
                $table->string('reward_method');
                $table->string(
                    'reward_quantity_source',
                )->nullable();
                $table->string(
                    'reward_target_item_code',
                )->nullable();
                $table->unsignedSmallInteger(
                    'rounding_scale',
                )->default(2);
                $table->string(
                    'rounding_method',
                )->default('half_up');
                $table->unsignedSmallInteger(
                    'position',
                );
                $table->timestamp(
                    'created_at',
                )->nullable();
            },
        );

        Schema::create(
            'price_list_conditional_rule_metric_components',
            static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('price_list_conditional_rule_id');
                $table->string('component_role', 32);
                $table->string('metric_source', 64);
                $table->unsignedSmallInteger('position');
                $table->timestamp('created_at')->nullable();
            },
        );

        Schema::create(
            'price_list_conditional_rule_reward_components',
            static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('price_list_conditional_rule_id');
                $table->string('metric_source', 64);
                $table->unsignedSmallInteger('position');
                $table->timestamp('created_at')->nullable();
            },
        );

        Schema::create(
            'price_list_conditional_bands',
            static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger(
                    'price_list_conditional_rule_id',
                );
                $table->decimal(
                    'minimum_value',
                    18,
                    4,
                )->nullable();
                $table->decimal(
                    'maximum_value',
                    18,
                    4,
                )->nullable();
                $table->boolean(
                    'minimum_inclusive',
                )->default(true);
                $table->boolean(
                    'maximum_inclusive',
                )->default(false);
                $table->decimal(
                    'adjustment_value',
                    16,
                    4,
                );
                $table->unsignedSmallInteger(
                    'position',
                );
                $table->timestamp(
                    'created_at',
                )->nullable();
            },
        );

        Schema::create(
            'driver_organization_assignments',
            static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('driver_id');
                $table->unsignedBigInteger(
                    'organization_id',
                );
                $table->date('valid_from');
                $table->date(
                    'valid_until',
                )->nullable();
            },
        );

        Schema::create(
            'daily_reports',
            static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger(
                    'organization_id',
                );
                $table->unsignedBigInteger(
                    'performed_by_driver_id',
                );
                $table->date('service_date');
                $table->string('status');
                $table->unsignedInteger(
                    'current_version',
                );
                $table->timestamp(
                    'deleted_at',
                )->nullable();
            },
        );

        Schema::create(
            'financial_calculations',
            static function (Blueprint $table): void {
                $table->id();
                $table->string('public_id');
                $table->unsignedBigInteger(
                    'organization_id',
                );
                $table->unsignedBigInteger(
                    'organization_relationship_id',
                );
                $table->unsignedBigInteger(
                    'price_list_id',
                );
                $table->unsignedBigInteger(
                    'price_list_version_id',
                );
                $table->unsignedBigInteger(
                    'daily_report_id',
                );
                $table->unsignedInteger(
                    'daily_report_version',
                );
                $table->unsignedInteger(
                    'calculation_version',
                );
                $table->string('status');
                $table->char('currency', 3);
                $table->json('input_snapshot');
                $table->decimal(
                    'subtotal_amount',
                    16,
                    2,
                );
                $table->decimal(
                    'total_amount',
                    16,
                    2,
                );
                $table->unsignedBigInteger(
                    'supersedes_calculation_id',
                )->nullable();
                $table->timestamp(
                    'created_at',
                )->nullable();
                $table->timestamp(
                    'updated_at',
                )->nullable();
            },
        );

        Schema::create(
            'financial_calculation_lines',
            static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger(
                    'financial_calculation_id',
                );
                $table->string('pricing_code');
                $table->decimal(
                    'line_amount',
                    16,
                    2,
                );
                $table->unsignedSmallInteger(
                    'position',
                );
            },
        );

        Schema::create(
            'financial_conditional_adjustments',
            static function (Blueprint $table): void {
                $table->id();
                $table->string('public_id')->unique();
                $table->unsignedBigInteger(
                    'organization_id',
                );
                $table->unsignedBigInteger(
                    'organization_relationship_id',
                );
                $table->unsignedBigInteger(
                    'price_list_id',
                );
                $table->unsignedBigInteger(
                    'price_list_version_id',
                );
                $table->unsignedBigInteger(
                    'price_list_conditional_rule_id',
                );
                $table->unsignedBigInteger(
                    'price_list_conditional_band_id',
                )->nullable();
                $table->unsignedBigInteger(
                    'performed_by_driver_id',
                );
                $table->string('evaluation_scope');
                $table->date('period_start');
                $table->date('period_end');
                $table->unsignedInteger(
                    'calculation_version',
                )->default(1);
                $table->char('currency', 3);
                $table->string('metric_type');
                $table->string(
                    'metric_numerator_source',
                );
                $table->decimal(
                    'metric_numerator_value',
                    18,
                    6,
                );
                $table->string(
                    'metric_denominator_source',
                )->nullable();
                $table->decimal(
                    'metric_denominator_value',
                    18,
                    6,
                )->nullable();
                $table->decimal(
                    'metric_value',
                    18,
                    6,
                );
                $table->string('reward_method');
                $table->string(
                    'reward_quantity_source',
                )->nullable();
                $table->decimal(
                    'reward_quantity_value',
                    18,
                    6,
                )->nullable();
                $table->string(
                    'reward_target_item_code',
                )->nullable();
                $table->decimal(
                    'reward_target_item_amount',
                    16,
                    2,
                )->nullable();
                $table->decimal(
                    'adjustment_value',
                    16,
                    4,
                )->nullable();
                $table->decimal(
                    'conditional_amount',
                    16,
                    2,
                );
                $table->json(
                    'evaluation_snapshot',
                );
                $table->unsignedBigInteger(
                    'calculated_by_user_id',
                );
                $table->timestamp(
                    'calculated_at',
                );
                $table->unsignedBigInteger(
                    'supersedes_adjustment_id',
                )->nullable();
                $table->timestamp(
                    'created_at',
                )->nullable();

                $table->unique(
                    [
                        'price_list_conditional_rule_id',
                        'performed_by_driver_id',
                        'period_start',
                        'period_end',
                        'calculation_version',
                    ],
                    'test_application_scope_unique',
                );

                $table->unique(
                    'supersedes_adjustment_id',
                    'test_application_supersedes_unique',
                );
            },
        );

        Schema::create(
            'financial_conditional_adjustment_sources',
            static function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger(
                    'financial_conditional_adjustment_id',
                );
                $table->unsignedBigInteger(
                    'financial_calculation_id',
                );
                $table->unsignedSmallInteger(
                    'source_position',
                );
                $table->timestamp(
                    'created_at',
                )->nullable();

                $table->unique(
                    [
                        'financial_conditional_adjustment_id',
                        'financial_calculation_id',
                    ],
                    'test_application_source_unique',
                );

                $table->unique(
                    [
                        'financial_conditional_adjustment_id',
                        'source_position',
                    ],
                    'test_application_position_unique',
                );
            },
        );
    }
}
