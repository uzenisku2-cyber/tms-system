<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use App\Modules\Fleet\Services\CsobBankStatementCsvAdapter;
use PHPUnit\Framework\TestCase;

final class CsobBankStatementCsvAdapterTest extends TestCase
{
    public function test_synthetic_csob_export_is_normalized_without_losing_raw_rows(): void
    {
        $rows = (new CsobBankStatementCsvAdapter)->parse(__DIR__.'/../../../Fixtures/Fleet/csob-bank-statement-synthetic.csv');
        self::assertCount(2, $rows);
        self::assertSame('credit', $rows[0]['normalized']['direction']);
        self::assertSame('1250.50', $rows[0]['normalized']['amount']);
        self::assertSame('2026-09-07', $rows[0]['normalized']['booked_at']);
        self::assertSame('123456789/0800', $rows[0]['normalized']['counterparty_account_identifier']);
        self::assertSame('CSOB-SYNTHETIC-0001', $rows[0]['normalized']['original_source_reference']);
        self::assertNull($rows[0]['normalized']['value_date']);
        self::assertCount(24, $rows[0]['raw']);
        self::assertSame('debit', $rows[1]['normalized']['direction']);
    }
}
