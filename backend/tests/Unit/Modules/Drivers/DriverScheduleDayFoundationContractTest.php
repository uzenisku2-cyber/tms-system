<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Drivers;

use App\Modules\Drivers\Models\DriverScheduleDay;
use PHPUnit\Framework\TestCase;

final class DriverScheduleDayFoundationContractTest extends TestCase
{
    public function test_schedule_status_contract_is_intentionally_minimal(): void
    {
        self::assertSame(
            [
                'working',
                'off',
            ],
            DriverScheduleDay::STATUSES,
        );

        self::assertSame(
            'working',
            DriverScheduleDay::STATUS_WORKING,
        );

        self::assertSame(
            'off',
            DriverScheduleDay::STATUS_OFF,
        );
    }

    public function test_schedule_model_uses_driver_date_and_status_fields(): void
    {
        $model = new DriverScheduleDay;

        self::assertSame(
            [
                'driver_id',
                'date',
                'status',
            ],
            $model->getFillable(),
        );

        self::assertSame(
            'driver_schedule_days',
            $model->getTable(),
        );

        self::assertSame(
            'date',
            $model->getCasts()['date'] ?? null,
        );
    }

    public function test_schedule_migration_preserves_one_status_per_driver_and_day(): void
    {
        $migrationFiles = glob(
            dirname(__DIR__, 4)
                .'/database/migrations/*_create_driver_schedule_days_table.php'
        );

        self::assertIsArray($migrationFiles);
        self::assertCount(1, $migrationFiles);

        $source = file_get_contents($migrationFiles[0]);

        self::assertIsString($source);

        self::assertStringContainsString(
            "Schema::create('driver_schedule_days'",
            $source,
        );

        self::assertStringContainsString(
            "['driver_id', 'date']",
            $source,
        );

        self::assertStringContainsString(
            "'driver_schedule_days_driver_date_unique'",
            $source,
        );

        self::assertStringContainsString(
            "\$table->string('status', 16)",
            $source,
        );
    }
}
