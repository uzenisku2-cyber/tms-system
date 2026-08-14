<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\DailyReports;

use PHPUnit\Framework\TestCase;

final class DriverOperationalWriteEligibilityGuardContractTest extends TestCase
{
    public function test_guard_requires_current_and_service_date_assignment(): void
    {
        $backendRoot = dirname(__DIR__, 4);

        $source = file_get_contents(
            $backendRoot
            .'/app/Modules/DailyReports/Services/DriverOperationalWriteEligibilityGuard.php',
        );

        self::assertIsString($source);

        self::assertStringContainsString(
            'CarbonImmutable::today()',
            $source,
        );

        self::assertStringContainsString(
            "'valid_from'",
            $source,
        );

        self::assertStringContainsString(
            "'valid_until'",
            $source,
        );

        self::assertStringContainsString(
            'k dnešnímu dni aktivní organizační přiřazení',
            $source,
        );

        self::assertStringContainsString(
            'k datu trasy platné organizační přiřazení',
            $source,
        );
    }

    public function test_direct_create_and_existing_report_mutations_are_guarded(): void
    {
        $backendRoot = dirname(__DIR__, 4);

        $source = file_get_contents(
            $backendRoot
            .'/app/Modules/DailyReports/Services/DailyReportWriteService.php',
        );

        self::assertIsString($source);

        self::assertStringContainsString(
            'DriverOperationalWriteEligibilityGuard::assertEligible(',
            $source,
        );

        self::assertStringContainsString(
            'assertDirectOperationalEligibilityIfActorIsDriver(',
            $source,
        );

        foreach ([
            'public function update(',
            'public function deleteDraft(',
            'public function submit(',
            'public function recordCorrection(',
            'public function resubmit(',
        ] as $method) {
            self::assertStringContainsString(
                $method,
                $source,
            );
        }

        self::assertGreaterThanOrEqual(
            6,
            substr_count(
                $source,
                'assertDirectOperationalEligibilityIfActorIsDriver(',
            ),
        );
    }

    public function test_candidate_service_date_is_guarded_on_update_and_correction(): void
    {
        $backendRoot = dirname(__DIR__, 4);

        $source = file_get_contents(
            $backendRoot
            .'/app/Modules/DailyReports/Services/DailyReportWriteService.php',
        );

        self::assertIsString($source);

        self::assertGreaterThanOrEqual(
            2,
            substr_count(
                $source,
                "array_key_exists(\n                'service_date',",
            ),
        );
    }

    public function test_delegated_and_authorized_import_paths_remain_separate(): void
    {
        $backendRoot = dirname(__DIR__, 4);

        $persistence = file_get_contents(
            $backendRoot
            .'/app/Modules/DailyReports/Services/DailyReportPersistenceService.php',
        );

        self::assertIsString($persistence);

        self::assertStringContainsString(
            'createDelegatedDraft(',
            $persistence,
        );

        self::assertStringContainsString(
            'createAuthorizedImportDraft(',
            $persistence,
        );

        self::assertStringContainsString(
            'ENTRY_METHOD_AUTHORIZED_IMPORT',
            $persistence,
        );
    }
}
