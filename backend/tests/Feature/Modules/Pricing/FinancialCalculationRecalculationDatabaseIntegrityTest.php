<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class FinancialCalculationRecalculationDatabaseIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_supersession_source_has_unique_database_constraint(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            self::markTestSkipped(
                'PostgreSQL index inspection is required.',
            );
        }

        $index = DB::selectOne(<<<'SQL'
SELECT
    indexname,
    indexdef
FROM pg_indexes
WHERE schemaname = current_schema()
  AND tablename = 'financial_calculations'
  AND indexname = 'fin_calcs_supersedes_unique'
SQL);

        self::assertNotNull($index);

        /** @var object{indexname: string, indexdef: string} $index */
        self::assertSame(
            'fin_calcs_supersedes_unique',
            $index->indexname,
        );

        self::assertStringContainsString(
            'CREATE UNIQUE INDEX',
            strtoupper($index->indexdef),
        );

        self::assertStringContainsString(
            '(supersedes_calculation_id)',
            $index->indexdef,
        );
    }
}
