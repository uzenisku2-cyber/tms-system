<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Core\Organizations\OrganizationContext;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Models\PriceListConditionalRule;
use App\Modules\Pricing\Services\FinancialCalculationCurrentSourceResolver;
use App\Modules\Pricing\Services\FinancialConditionalScopeSourceResolver;
use DomainException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

final class FinancialConditionalScopeSourceResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        self::assertSame(
            'sqlite',
            DB::connection()->getDriverName(),
            'This test must never use persistent PostgreSQL.',
        );

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

    public function test_monthly_scope_returns_all_expected_current_sources_in_route_order(): void
    {
        $reportA = $this->createReport(
            serviceDate: '2026-08-02',
        );

        $reportB = $this->createReport(
            serviceDate: '2026-08-01',
        );

        $reportC = $this->createReport(
            serviceDate: '2026-08-02',
        );

        $calculationA = $this->createCalculation(
            dailyReportId: $reportA,
        );

        $calculationB = $this->createCalculation(
            dailyReportId: $reportB,
        );

        $calculationC = $this->createCalculation(
            dailyReportId: $reportC,
        );

        $resolved = $this->resolver()
            ->resolveMonthlyDriverSources(
                conditionalRuleId: 300,
                performedByDriverId: 10,
                calendarMonth: '2026-08',
            );

        self::assertSame(2, $resolved['organization_id']);
        self::assertSame(
            20,
            $resolved['organization_relationship_id'],
        );
        self::assertSame(100, $resolved['price_list_id']);
        self::assertSame(
            200,
            $resolved['price_list_version_id'],
        );
        self::assertSame(
            10,
            $resolved['performed_by_driver_id'],
        );
        self::assertSame(
            '2026-08-01',
            $resolved['period_start'],
        );
        self::assertSame(
            '2026-08-31',
            $resolved['period_end'],
        );
        self::assertSame(
            [
                $reportB,
                $reportA,
                $reportC,
            ],
            $resolved['daily_report_ids'],
        );
        self::assertSame(
            [
                $calculationB,
                $calculationA,
                $calculationC,
            ],
            $resolved['financial_calculation_ids'],
        );
    }

    public function test_mid_month_price_list_version_start_clips_expected_routes(): void
    {
        DB::table('price_list_versions')
            ->where('id', 200)
            ->update([
                'valid_from' => '2026-08-11',
            ]);

        $outside = $this->createReport(
            serviceDate: '2026-08-10',
        );

        $inside = $this->createReport(
            serviceDate: '2026-08-11',
        );

        $insideCalculation = $this->createCalculation(
            dailyReportId: $inside,
        );

        $resolved = $this->resolver()
            ->resolveMonthlyDriverSources(
                conditionalRuleId: 300,
                performedByDriverId: 10,
                calendarMonth: '2026-08',
            );

        self::assertSame(
            '2026-08-11',
            $resolved['period_start'],
        );

        self::assertSame(
            '2026-08-31',
            $resolved['period_end'],
        );

        self::assertSame(
            [$inside],
            $resolved['daily_report_ids'],
        );

        self::assertSame(
            [$insideCalculation],
            $resolved['financial_calculation_ids'],
        );

        self::assertNotContains(
            $outside,
            $resolved['daily_report_ids'],
        );
    }

    public function test_missing_current_financial_source_blocks_entire_scope(): void
    {
        $reportA = $this->createReport(
            serviceDate: '2026-08-01',
        );

        $this->createReport(
            serviceDate: '2026-08-02',
        );

        $this->createCalculation(
            dailyReportId: $reportA,
        );

        $this->expectException(
            DomainException::class,
        );

        $this->resolver()
            ->resolveMonthlyDriverSources(
                conditionalRuleId: 300,
                performedByDriverId: 10,
                calendarMonth: '2026-08',
            );
    }

    public function test_non_final_current_financial_source_blocks_entire_scope(): void
    {
        $report = $this->createReport(
            serviceDate: '2026-08-01',
        );

        $this->createCalculation(
            dailyReportId: $report,
            status: FinancialCalculation::STATUS_CALCULATED,
        );

        $this->expectException(
            DomainException::class,
        );

        $this->resolver()
            ->resolveMonthlyDriverSources(
                conditionalRuleId: 300,
                performedByDriverId: 10,
                calendarMonth: '2026-08',
            );
    }

    public function test_stale_financial_source_against_current_report_version_is_blocked(): void
    {
        $report = $this->createReport(
            serviceDate: '2026-08-01',
            currentVersion: 2,
        );

        $this->createCalculation(
            dailyReportId: $report,
            dailyReportVersion: 1,
        );

        $this->expectException(
            DomainException::class,
        );

        $this->resolver()
            ->resolveMonthlyDriverSources(
                conditionalRuleId: 300,
                performedByDriverId: 10,
                calendarMonth: '2026-08',
            );
    }

    public function test_ambiguous_effective_driver_assignment_blocks_scope(): void
    {
        $report = $this->createReport(
            serviceDate: '2026-08-01',
        );

        $this->createCalculation(
            dailyReportId: $report,
        );

        DB::table(
            'driver_organization_assignments',
        )->insert([
            'driver_id' => 10,
            'organization_id' => 2,
            'valid_from' => '2026-07-01',
            'valid_until' => null,
        ]);

        $this->expectException(
            DomainException::class,
        );

        $this->resolver()
            ->resolveMonthlyDriverSources(
                conditionalRuleId: 300,
                performedByDriverId: 10,
                calendarMonth: '2026-08',
            );
    }

    public function test_other_provider_segment_is_deterministically_outside_relationship_scope(): void
    {
        DB::table(
            'driver_organization_assignments',
        )
            ->where('driver_id', 10)
            ->update([
                'valid_until' => '2026-08-10',
            ]);

        DB::table(
            'driver_organization_assignments',
        )->insert([
            'driver_id' => 10,
            'organization_id' => 3,
            'valid_from' => '2026-08-11',
            'valid_until' => null,
        ]);

        $included = $this->createReport(
            serviceDate: '2026-08-10',
        );

        $excluded = $this->createReport(
            serviceDate: '2026-08-11',
        );

        $includedCalculation = $this->createCalculation(
            dailyReportId: $included,
        );

        $resolved = $this->resolver()
            ->resolveMonthlyDriverSources(
                conditionalRuleId: 300,
                performedByDriverId: 10,
                calendarMonth: '2026-08',
            );

        self::assertSame(
            [$included],
            $resolved['daily_report_ids'],
        );

        self::assertSame(
            [$includedCalculation],
            $resolved['financial_calculation_ids'],
        );

        self::assertNotContains(
            $excluded,
            $resolved['daily_report_ids'],
        );
    }

    public function test_commercial_relationship_must_cover_stored_scope_period(): void
    {
        DB::table('organization_relationships')
            ->where('id', 20)
            ->update([
                'valid_from' => '2026-08-05 00:00:00',
            ]);

        $report = $this->createReport(
            serviceDate: '2026-08-05',
        );

        $this->createCalculation(
            dailyReportId: $report,
        );

        $this->expectException(
            DomainException::class,
        );

        $this->resolver()
            ->resolveMonthlyDriverSources(
                conditionalRuleId: 300,
                performedByDriverId: 10,
                calendarMonth: '2026-08',
            );
    }

    public function test_provider_organization_context_is_enforced(): void
    {
        $report = $this->createReport(
            serviceDate: '2026-08-01',
        );

        $this->createCalculation(
            dailyReportId: $report,
        );

        $this->organizationContext()->set(3);

        $this->expectException(
            DomainException::class,
        );

        $this->resolver()
            ->resolveMonthlyDriverSources(
                conditionalRuleId: 300,
                performedByDriverId: 10,
                calendarMonth: '2026-08',
            );
    }

    public function test_latest_approved_successor_is_selected(): void
    {
        $report = $this->createReport(
            serviceDate: '2026-08-01',
            currentVersion: 2,
        );

        $v1 = $this->createCalculation(
            dailyReportId: $report,
            dailyReportVersion: 1,
            calculationVersion: 1,
        );

        $v2 = $this->createCalculation(
            dailyReportId: $report,
            dailyReportVersion: 2,
            calculationVersion: 2,
            supersedesCalculationId: $v1,
        );

        $resolved = $this->resolver()
            ->resolveMonthlyDriverSources(
                conditionalRuleId: 300,
                performedByDriverId: 10,
                calendarMonth: '2026-08',
            );

        self::assertSame(
            [$v2],
            $resolved['financial_calculation_ids'],
        );
    }

    public function test_cancelled_current_successor_blocks_scope_without_fallback(): void
    {
        $report = $this->createReport(
            serviceDate: '2026-08-01',
            currentVersion: 2,
        );

        $v1 = $this->createCalculation(
            dailyReportId: $report,
            dailyReportVersion: 1,
            calculationVersion: 1,
        );

        $this->createCalculation(
            dailyReportId: $report,
            dailyReportVersion: 2,
            calculationVersion: 2,
            status: FinancialCalculation::STATUS_CANCELLED,
            supersedesCalculationId: $v1,
        );

        $this->expectException(
            DomainException::class,
        );

        $this->resolver()
            ->resolveMonthlyDriverSources(
                conditionalRuleId: 300,
                performedByDriverId: 10,
                calendarMonth: '2026-08',
            );
    }

    public function test_non_monthly_rule_is_rejected(): void
    {
        DB::table('price_list_conditional_rules')
            ->where('id', 300)
            ->update([
                'evaluation_scope' => PriceListConditionalRule::EVALUATION_SCOPE_PER_ROUTE,
            ]);

        $this->expectException(
            DomainException::class,
        );

        $this->resolver()
            ->resolveMonthlyDriverSources(
                conditionalRuleId: 300,
                performedByDriverId: 10,
                calendarMonth: '2026-08',
            );
    }

    public function test_no_provider_affiliated_routes_is_rejected(): void
    {
        DB::table(
            'driver_organization_assignments',
        )
            ->where('driver_id', 10)
            ->update([
                'organization_id' => 3,
            ]);

        $this->createReport(
            serviceDate: '2026-08-01',
        );

        $this->expectException(
            DomainException::class,
        );

        $this->resolver()
            ->resolveMonthlyDriverSources(
                conditionalRuleId: 300,
                performedByDriverId: 10,
                calendarMonth: '2026-08',
            );
    }

    public function test_per_route_scope_returns_exact_current_route_source(): void
    {
        DB::table('price_list_conditional_rules')
            ->where('id', 300)
            ->update([
                'evaluation_scope' => PriceListConditionalRule::EVALUATION_SCOPE_PER_ROUTE,
            ]);

        $report = $this->createReport(
            serviceDate: '2026-08-15',
        );

        $calculation = $this->createCalculation(
            dailyReportId: $report,
        );

        $resolved = $this->resolver()
            ->resolvePerRouteSources(
                conditionalRuleId: 300,
                dailyReportId: $report,
            );

        self::assertSame(2, $resolved['organization_id']);
        self::assertSame(
            20,
            $resolved['organization_relationship_id'],
        );
        self::assertSame(100, $resolved['price_list_id']);
        self::assertSame(
            200,
            $resolved['price_list_version_id'],
        );
        self::assertSame(
            10,
            $resolved['performed_by_driver_id'],
        );
        self::assertSame(
            '2026-08-15',
            $resolved['period_start'],
        );
        self::assertSame(
            '2026-08-15',
            $resolved['period_end'],
        );
        self::assertSame(
            [$report],
            $resolved['daily_report_ids'],
        );
        self::assertSame(
            [$calculation],
            $resolved['financial_calculation_ids'],
        );
    }

    public function test_per_route_scope_rejects_non_per_route_rule(): void
    {
        $report = $this->createReport(
            serviceDate: '2026-08-15',
        );

        $this->createCalculation(
            dailyReportId: $report,
        );

        $this->expectException(
            DomainException::class,
        );

        $this->resolver()
            ->resolvePerRouteSources(
                conditionalRuleId: 300,
                dailyReportId: $report,
            );
    }

    public function test_per_route_scope_rejects_stale_current_source(): void
    {
        DB::table('price_list_conditional_rules')
            ->where('id', 300)
            ->update([
                'evaluation_scope' => PriceListConditionalRule::EVALUATION_SCOPE_PER_ROUTE,
            ]);

        $report = $this->createReport(
            serviceDate: '2026-08-15',
            currentVersion: 2,
        );

        $this->createCalculation(
            dailyReportId: $report,
            dailyReportVersion: 1,
        );

        $this->expectException(
            DomainException::class,
        );

        $this->resolver()
            ->resolvePerRouteSources(
                conditionalRuleId: 300,
                dailyReportId: $report,
            );
    }

    public function test_per_route_scope_rejects_route_outside_provider_assignment(): void
    {
        DB::table('price_list_conditional_rules')
            ->where('id', 300)
            ->update([
                'evaluation_scope' => PriceListConditionalRule::EVALUATION_SCOPE_PER_ROUTE,
            ]);

        DB::table(
            'driver_organization_assignments',
        )
            ->where('driver_id', 10)
            ->update([
                'organization_id' => 3,
            ]);

        $report = $this->createReport(
            serviceDate: '2026-08-15',
        );

        $this->createCalculation(
            dailyReportId: $report,
        );

        $this->expectException(
            DomainException::class,
        );

        $this->resolver()
            ->resolvePerRouteSources(
                conditionalRuleId: 300,
                dailyReportId: $report,
            );
    }

    public function test_per_route_scope_requires_price_list_version_to_cover_service_date(): void
    {
        DB::table('price_list_conditional_rules')
            ->where('id', 300)
            ->update([
                'evaluation_scope' => PriceListConditionalRule::EVALUATION_SCOPE_PER_ROUTE,
            ]);

        DB::table('price_list_versions')
            ->where('id', 200)
            ->update([
                'valid_from' => '2026-08-16',
            ]);

        $report = $this->createReport(
            serviceDate: '2026-08-15',
        );

        $this->createCalculation(
            dailyReportId: $report,
        );

        $this->expectException(
            DomainException::class,
        );

        $this->resolver()
            ->resolvePerRouteSources(
                conditionalRuleId: 300,
                dailyReportId: $report,
            );
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
                $table->unsignedBigInteger('price_list_id');
                $table->unsignedInteger('version_number');
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
                $table->string('evaluation_scope');
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
                $table->date('valid_until')->nullable();
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
                $table->unsignedInteger('current_version');
                $table->timestamp('deleted_at')->nullable();
            },
        );

        Schema::create(
            'financial_calculations',
            static function (Blueprint $table): void {
                $table->id();
                $table->string('public_id');
                $table->unsignedBigInteger('organization_id');
                $table->unsignedBigInteger(
                    'organization_relationship_id',
                );
                $table->unsignedBigInteger('price_list_id');
                $table->unsignedBigInteger(
                    'price_list_version_id',
                );
                $table->unsignedBigInteger('daily_report_id');
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
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            },
        );
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

        DB::table('price_list_conditional_rules')->insert([
            'id' => 300,
            'price_list_version_id' => 200,
            'evaluation_scope' => PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_DRIVER,
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
        int $dailyReportVersion = 1,
        int $calculationVersion = 1,
        string $status = FinancialCalculation::STATUS_APPROVED,
        ?int $supersedesCalculationId = null,
    ): int {
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
            'status' => $status,
            'currency' => 'CZK',
            'input_snapshot' => json_encode(
                [
                    'daily_report_id' => $dailyReportId,
                    'daily_report_version' => $dailyReportVersion,
                ],
                JSON_THROW_ON_ERROR,
            ),
            'subtotal_amount' => '0.00',
            'total_amount' => '0.00',
            'supersedes_calculation_id' => $supersedesCalculationId,
            'created_at' => '2026-08-13 00:00:00',
            'updated_at' => '2026-08-13 00:00:00',
        ]);
    }

    private function resolver(): FinancialConditionalScopeSourceResolver
    {
        return new FinancialConditionalScopeSourceResolver(
            $this->organizationContext(),
            new FinancialCalculationCurrentSourceResolver,
        );
    }

    private function organizationContext(): OrganizationContext
    {
        return app(OrganizationContext::class);
    }
}
