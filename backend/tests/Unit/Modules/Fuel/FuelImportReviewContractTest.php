<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fuel;

use Tests\TestCase;

final class FuelImportReviewContractTest extends TestCase
{
    public function test_review_corrections_are_append_only_audited_and_organization_scoped(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_08_27_220000_create_fuel_import_row_corrections.php'));
        $model = file_get_contents(app_path('Modules/Fuel/Models/FuelImportRowCorrection.php'));
        $request = file_get_contents(app_path('Modules/Fuel/Requests/StoreFuelImportRowCorrectionRequest.php'));
        $service = file_get_contents(app_path('Modules/Fuel/Services/FuelImportReviewService.php'));
        $routes = file_get_contents(app_path('Modules/Fuel/Routes/api.php'));

        self::assertIsString($migration);
        self::assertIsString($model);
        self::assertIsString($request);
        self::assertIsString($service);
        self::assertIsString($routes);

        foreach (['fuel_import_row_corrections', 'revision', 'original_payload', 'corrected_payload', 'reason', 'corrected_by_user_id'] as $marker) {
            self::assertStringContainsString($marker, $migration);
        }

        foreach (['public_id', 'original_payload', 'corrected_payload', 'reason', 'corrected_by_user_id'] as $marker) {
            self::assertStringContainsString($marker, $model);
        }

        foreach (["'corrected_payload' => ['required', 'array', 'min:1']", "'reason' => ['required', 'string', 'min:10', 'max:1000']"] as $marker) {
            self::assertStringContainsString($marker, $request);
        }

        foreach (['assertVisibleBatch', 'lockForUpdate', "['review', 'rejected']", 'original_payload', 'corrected_payload', 'corrected_by_user_id', 'orderByDesc'] as $marker) {
            self::assertStringContainsString($marker, $service);
        }

        self::assertStringNotContainsString('->update([', $service);
        self::assertStringContainsString('rows/{sourceRow}/corrections', $routes);
        self::assertStringContainsString("'perm:users.manage'", $routes);
    }
}
