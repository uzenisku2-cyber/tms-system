<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fuel;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FuelImportFinalizationContractTest extends TestCase
{
    #[Test]
    public function finalization_contract_is_transactional_audited_and_permission_protected(): void
    {
        $migration = $this->source('database/migrations/2026_08_28_010000_create_fuel_import_row_finalizations.php');
        $model = $this->source('app/Modules/Fuel/Models/FuelImportRowFinalization.php');
        $request = $this->source('app/Modules/Fuel/Requests/FinalizeFuelImportRowRequest.php');
        $service = $this->source('app/Modules/Fuel/Services/FuelImportFinalizationService.php');
        $controller = $this->source('app/Modules/Fuel/Controllers/FuelTransactionImportController.php');
        $routes = $this->source('app/Modules/Fuel/Routes/api.php');
        $review = $this->source('app/Modules/Fuel/Services/FuelImportReviewService.php');

        self::assertStringContainsString("Schema::create('fuel_import_row_finalizations'", $migration);
        self::assertStringContainsString("fuel_import_row_id')->unique()", $migration);
        self::assertStringContainsString('fuel_import_row_correction_id', $migration);
        self::assertStringContainsString('fuel_transaction_id', $migration);
        self::assertStringContainsString('before_snapshot', $migration);
        self::assertStringContainsString('after_snapshot', $migration);
        self::assertStringContainsString('finalized_by_user_id', $migration);
        self::assertStringContainsString('correction_revision > 0', $migration);

        self::assertStringContainsString('final class FuelImportRowFinalization', $model);
        self::assertStringContainsString("'before_snapshot' => 'array'", $model);
        self::assertStringContainsString('public function finalizedBy(): BelongsTo', $model);

        self::assertStringContainsString("'expected_correction_revision' => ['required', 'integer', 'min:1']", $request);
        self::assertStringContainsString("'reason' => ['required', 'string', 'min:10', 'max:2000']", $request);

        self::assertStringContainsString('DB::transaction(', $service);
        self::assertGreaterThanOrEqual(4, substr_count($service, 'lockForUpdate()'));
        self::assertStringContainsString("['review', 'rejected']", $service);
        self::assertStringContainsString('already been finalized', $service);
        self::assertStringContainsString('At least one audited correction is required', $service);
        self::assertStringContainsString('The reviewed correction revision is stale', $service);
        self::assertStringContainsString('$match[\'match_status\'] !== \'matched\'', $service);
        self::assertStringContainsString('duplicates an existing fuel transaction', $service);
        self::assertStringContainsString("'status' => 'accepted'", $service);
        self::assertStringContainsString('\'from_status\' => $fromStatus', $service);
        self::assertStringContainsString('\'before_snapshot\' => $before', $service);
        self::assertStringContainsString('\'after_snapshot\' => $transaction->fresh()->attributesToArray()', $service);
        self::assertStringContainsString('private function refreshBatch', $service);
        self::assertStringContainsString('}, 3);', $service);

        self::assertStringContainsString('public function finalize(FinalizeFuelImportRowRequest', $controller);
        self::assertStringContainsString("Route::middleware('perm:compensation.manage')", $routes);
        self::assertStringContainsString("name('rows.finalization.store')", $routes);
        self::assertStringContainsString('\'finalization\' => $finalization instanceof FuelImportRowFinalization', $review);
    }

    private function source(string $relativePath): string
    {
        $content = file_get_contents(base_path($relativePath));
        self::assertIsString($content);

        return $content;
    }
}
