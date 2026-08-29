<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fuel;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class FuelSurchargeAuthorizationContractTest extends TestCase
{
    #[Test]
    public function internal_routes_require_compensation_management(): void
    {
        $routes = $this->source('app/Modules/Fuel/Routes/api.php');

        self::assertStringContainsString("prefix('fuel-surcharges')", $routes);
        self::assertStringContainsString("Route::get('/mine'", $routes);
        self::assertStringContainsString("middleware('perm:compensation.manage')", $routes);
        self::assertStringContainsString("Route::post('/', [FuelSurchargeController::class, 'store'])", $routes);
    }

    #[Test]
    public function recipient_payload_never_contains_revenue_or_margin(): void
    {
        $visibility = $this->source(
            'app/Modules/Fuel/Services/FuelSurchargeRecipientVisibilityService.php',
        );

        self::assertStringContainsString('payout_rate_per_actual_km', $visibility);
        self::assertStringContainsString("'quantity_source' => 'actual_km'", $visibility);
        self::assertStringNotContainsString('billing_rate_per_actual_km', $visibility);
        self::assertStringNotContainsString('margin_per_actual_km', $visibility);
        self::assertStringContainsString(
            'driver_organization_assignments.organization_id',
            $visibility,
        );
        self::assertStringContainsString(
            "'target_organization_id'",
            $visibility,
        );
    }

    #[Test]
    public function internal_payload_exposes_margin_only_in_management_service(): void
    {
        $management = $this->source(
            'app/Modules/Fuel/Services/FuelSurchargeManagementService.php',
        );

        self::assertStringContainsString('billing_rate_per_actual_km', $management);
        self::assertStringContainsString('payout_rate_per_actual_km', $management);
        self::assertStringContainsString('margin_per_actual_km', $management);
        self::assertStringContainsString('bcsub(', $management);
    }

    #[Test]
    public function new_customer_rate_closes_the_previous_active_period(): void
    {
        $management = $this->source(
            'app/Modules/Fuel/Services/FuelSurchargeManagementService.php',
        );

        self::assertStringContainsString('lockForUpdate()', $management);
        self::assertStringContainsString("'valid_until' => \$validFrom->subDay()->toDateString()", $management);
        self::assertStringContainsString("'status' => FuelSurcharge::STATUS_ENDED", $management);
        self::assertStringContainsString('FuelSurchargeRecipientRate::STATUS_ENDED', $management);
        self::assertStringContainsString("where('fuel_surcharge_id', \$existing->getKey())", $management);
    }

    #[Test]
    public function customer_and_carrier_relationship_directions_are_explicit(): void
    {
        $management = $this->source(
            'app/Modules/Fuel/Services/FuelSurchargeManagementService.php',
        );

        self::assertStringContainsString("where('target_organization_id', \$organizationId)", $management);
        self::assertStringContainsString("where('source_organization_id', \$organizationId)", $management);
        self::assertStringContainsString('OrganizationRelationship::TYPE_SUBCONTRACTING', $management);
        self::assertStringContainsString('OrganizationRelationship::STATUS_ACTIVE', $management);
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(base_path($relativePath));

        self::assertIsString($source);

        return $source;
    }
}
