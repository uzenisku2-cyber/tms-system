<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use App\Modules\Pricing\Models\FinancialCalculation;
use App\Modules\Pricing\Services\FinancialCalculationCurrentSourceResolver;
use DomainException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LogicException;
use Tests\TestCase;

final class FinancialCalculationCurrentSourceResolverTest extends TestCase
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

    protected function tearDown(): void
    {
        Schema::dropAllTables();

        parent::tearDown();
    }

    public function test_initial_approved_leaf_is_usable(): void
    {
        $id = $this->createCalculation(
            status: FinancialCalculation::STATUS_APPROVED,
        );

        $resolved = $this->resolver()
            ->resolveUsableForDailyReport(
                organizationId: 10,
                organizationRelationshipId: 20,
                priceListVersionId: 200,
                dailyReportId: 1000,
            );

        self::assertSame(
            $id,
            $resolved->getKey(),
        );
    }

    public function test_closed_leaf_is_usable(): void
    {
        $id = $this->createCalculation(
            status: FinancialCalculation::STATUS_CLOSED,
        );

        $resolved = $this->resolver()
            ->resolveUsableForDailyReport(
                organizationId: 10,
                organizationRelationshipId: 20,
                priceListVersionId: 200,
                dailyReportId: 1000,
            );

        self::assertSame(
            $id,
            $resolved->getKey(),
        );
    }

    public function test_approved_successor_replaces_old_approved_source(): void
    {
        $v1 = $this->createCalculation(
            status: FinancialCalculation::STATUS_APPROVED,
            calculationVersion: 1,
            dailyReportVersion: 1,
        );

        $v2 = $this->createCalculation(
            status: FinancialCalculation::STATUS_APPROVED,
            calculationVersion: 2,
            dailyReportVersion: 2,
            supersedesCalculationId: $v1,
        );

        $resolved = $this->resolver()
            ->resolveUsableForDailyReport(
                organizationId: 10,
                organizationRelationshipId: 20,
                priceListVersionId: 200,
                dailyReportId: 1000,
            );

        self::assertSame(
            $v2,
            $resolved->getKey(),
        );

        self::assertNotSame(
            $v1,
            $resolved->getKey(),
        );
    }

    public function test_pending_successor_blocks_fallback_to_old_approved_source(): void
    {
        $v1 = $this->createCalculation(
            status: FinancialCalculation::STATUS_APPROVED,
            calculationVersion: 1,
            dailyReportVersion: 1,
        );

        $this->createCalculation(
            status: FinancialCalculation::STATUS_CALCULATED,
            calculationVersion: 2,
            dailyReportVersion: 2,
            supersedesCalculationId: $v1,
        );

        $this->expectException(
            DomainException::class,
        );

        $this->resolver()
            ->resolveUsableForDailyReport(
                organizationId: 10,
                organizationRelationshipId: 20,
                priceListVersionId: 200,
                dailyReportId: 1000,
            );
    }

    public function test_under_review_successor_blocks_fallback(): void
    {
        $v1 = $this->createCalculation(
            status: FinancialCalculation::STATUS_APPROVED,
            calculationVersion: 1,
            dailyReportVersion: 1,
        );

        $this->createCalculation(
            status: FinancialCalculation::STATUS_UNDER_REVIEW,
            calculationVersion: 2,
            dailyReportVersion: 2,
            supersedesCalculationId: $v1,
        );

        $this->expectException(
            DomainException::class,
        );

        $this->resolver()
            ->resolveUsableForDailyReport(
                organizationId: 10,
                organizationRelationshipId: 20,
                priceListVersionId: 200,
                dailyReportId: 1000,
            );
    }

    public function test_cancelled_successor_blocks_fallback_to_old_approved_source(): void
    {
        $v1 = $this->createCalculation(
            status: FinancialCalculation::STATUS_APPROVED,
            calculationVersion: 1,
            dailyReportVersion: 1,
        );

        $this->createCalculation(
            status: FinancialCalculation::STATUS_CANCELLED,
            calculationVersion: 2,
            dailyReportVersion: 2,
            supersedesCalculationId: $v1,
        );

        $this->expectException(
            DomainException::class,
        );

        $this->resolver()
            ->resolveUsableForDailyReport(
                organizationId: 10,
                organizationRelationshipId: 20,
                priceListVersionId: 200,
                dailyReportId: 1000,
            );
    }

    public function test_multiple_current_roots_are_rejected_as_corrupt_lineage(): void
    {
        $this->createCalculation(
            status: FinancialCalculation::STATUS_APPROVED,
            calculationVersion: 1,
            dailyReportVersion: 1,
        );

        $this->createCalculation(
            status: FinancialCalculation::STATUS_APPROVED,
            calculationVersion: 2,
            dailyReportVersion: 2,
        );

        $this->expectException(
            LogicException::class,
        );

        $this->resolver()
            ->resolveUsableForDailyReport(
                organizationId: 10,
                organizationRelationshipId: 20,
                priceListVersionId: 200,
                dailyReportId: 1000,
            );
    }

    public function test_successor_outside_requested_context_still_prevents_fallback(): void
    {
        $v1 = $this->createCalculation(
            status: FinancialCalculation::STATUS_APPROVED,
            calculationVersion: 1,
            dailyReportVersion: 1,
        );

        $this->createCalculation(
            status: FinancialCalculation::STATUS_APPROVED,
            organizationId: 11,
            calculationVersion: 2,
            dailyReportVersion: 2,
            supersedesCalculationId: $v1,
        );

        $this->expectException(
            DomainException::class,
        );

        $this->resolver()
            ->resolveUsableForDailyReport(
                organizationId: 10,
                organizationRelationshipId: 20,
                priceListVersionId: 200,
                dailyReportId: 1000,
            );
    }

    public function test_unrelated_route_does_not_affect_resolution(): void
    {
        $expected = $this->createCalculation(
            status: FinancialCalculation::STATUS_APPROVED,
            dailyReportId: 1000,
        );

        $this->createCalculation(
            status: FinancialCalculation::STATUS_APPROVED,
            dailyReportId: 1001,
        );

        $resolved = $this->resolver()
            ->resolveUsableForDailyReport(
                organizationId: 10,
                organizationRelationshipId: 20,
                priceListVersionId: 200,
                dailyReportId: 1000,
            );

        self::assertSame(
            $expected,
            $resolved->getKey(),
        );
    }

    private function resolver(): FinancialCalculationCurrentSourceResolver
    {
        return new FinancialCalculationCurrentSourceResolver;
    }

    private function createCalculation(
        string $status,
        int $organizationId = 10,
        int $organizationRelationshipId = 20,
        int $priceListVersionId = 200,
        int $dailyReportId = 1000,
        int $dailyReportVersion = 1,
        int $calculationVersion = 1,
        ?int $supersedesCalculationId = null,
    ): int {
        return DB::table(
            'financial_calculations',
        )->insertGetId([
            'public_id' => (string) Str::uuid(),
            'organization_id' => $organizationId,
            'organization_relationship_id' => $organizationRelationshipId,
            'price_list_id' => 100,
            'price_list_version_id' => $priceListVersionId,
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
}
