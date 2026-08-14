<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Drivers;

use PHPUnit\Framework\TestCase;

final class DriverOrganizationAssignmentTransferContractTest extends TestCase
{
    public function test_atomic_transfer_contract_is_present(): void
    {
        $backendRoot = dirname(__DIR__, 4);

        $controller = file_get_contents(
            $backendRoot
            .'/app/Modules/Drivers/Controllers/DriverOrganizationAssignmentController.php',
        );

        self::assertIsString($controller);
        self::assertStringContainsString(
            'public function transfer(',
            $controller,
        );
        self::assertStringContainsString(
            'DB::transaction(',
            $controller,
        );
        self::assertStringContainsString(
            '->lockForUpdate()',
            $controller,
        );
        self::assertStringContainsString(
            '->subDay()',
            $controller,
        );
        self::assertStringContainsString(
            "'previous_assignment'",
            $controller,
        );
        self::assertStringContainsString(
            "'new_assignment'",
            $controller,
        );
    }

    public function test_transfer_preserves_historical_assignment_semantics(): void
    {
        $backendRoot = dirname(__DIR__, 4);

        $controller = file_get_contents(
            $backendRoot
            .'/app/Modules/Drivers/Controllers/DriverOrganizationAssignmentController.php',
        );

        self::assertIsString($controller);
        self::assertStringContainsString(
            "'valid_until' => \$previousUntil",
            $controller,
        );
        self::assertStringContainsString(
            "'valid_from' => \$effectiveDate",
            $controller,
        );
        self::assertStringContainsString(
            'Nový dopravce musí být jiný než současný.',
            $controller,
        );
        self::assertStringContainsString(
            'Nové přiřazení by se překrývalo',
            $controller,
        );
    }

    public function test_external_target_must_be_valid_for_effective_date(): void
    {
        $backendRoot = dirname(__DIR__, 4);

        $controller = file_get_contents(
            $backendRoot
            .'/app/Modules/Drivers/Controllers/DriverOrganizationAssignmentController.php',
        );

        self::assertIsString($controller);
        self::assertStringContainsString(
            "'source_organization_id'",
            $controller,
        );
        self::assertStringContainsString(
            "'target_organization_id'",
            $controller,
        );
        self::assertStringContainsString(
            "'relationship_type'",
            $controller,
        );
        self::assertStringContainsString(
            'Vybraný dopravce nemá k datu změny platný vztah',
            $controller,
        );
    }

    public function test_transfer_route_is_registered_in_source(): void
    {
        $backendRoot = dirname(__DIR__, 4);

        $routes = file_get_contents(
            $backendRoot.'/routes/api.php',
        );

        self::assertIsString($routes);
        self::assertStringContainsString(
            "'/{assignment}/transfer'",
            $routes,
        );
        self::assertStringContainsString(
            "'transfer'",
            $routes,
        );
        self::assertStringContainsString(
            'own-drivers.assignments.transfer',
            $routes,
        );
    }
}
