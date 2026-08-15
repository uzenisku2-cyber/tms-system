<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class CustomerBillingAdministrationContractTest extends TestCase
{
    public function test_customer_browser_keeps_customer_as_source_and_provider_as_target(): void
    {
        $backend = dirname(__DIR__, 4);

        $controller = file_get_contents(
            $backend.'/app/Modules/Organizations/Controllers/CustomerAdminController.php',
        );

        self::assertIsString($controller);

        self::assertStringContainsString(
            "'target_organization_id'",
            $controller,
        );

        self::assertStringContainsString(
            'sourceOrganization',
            $controller,
        );

        self::assertStringContainsString(
            'OrganizationRelationship::TYPE_SUBCONTRACTING',
            $controller,
        );
    }

    public function test_provider_managed_billing_price_list_preserves_financial_direction(): void
    {
        $backend = dirname(__DIR__, 4);

        $service = file_get_contents(
            $backend.'/app/Modules/Pricing/Services/PriceListWriteService.php',
        );

        self::assertIsString($service);

        self::assertStringContainsString(
            'createProviderManagedDraft',
            $service,
        );

        self::assertStringContainsString(
            "'target_organization_id'",
            $service,
        );

        self::assertStringContainsString(
            "'owner_organization_id' =>",
            $service,
        );

        self::assertStringContainsString(
            "'customer_organization_id' =>",
            $service,
        );

        self::assertStringContainsString(
            "'provider_organization_id' =>",
            $service,
        );

        self::assertStringContainsString(
            "'managed_by_organization_id' =>",
            $service,
        );

        self::assertStringContainsString(
            '$customerId',
            $service,
        );

        self::assertStringContainsString(
            '$providerId',
            $service,
        );
    }

    public function test_customer_billing_routes_are_explicit_and_permission_scoped(): void
    {
        $backend = dirname(__DIR__, 4);

        $routes = file_get_contents(
            $backend.'/routes/api.php',
        );

        self::assertIsString($routes);

        self::assertStringContainsString(
            "'/customers'",
            $routes,
        );

        self::assertStringContainsString(
            "'/customers/{relationship}'",
            $routes,
        );

        self::assertStringContainsString(
            "'/customers/{relationship}/price-lists'",
            $routes,
        );

        self::assertStringContainsString(
            "'perm:pricing.view'",
            $routes,
        );

        self::assertStringContainsString(
            "'perm:pricing.manage'",
            $routes,
        );
    }

    public function test_customer_write_foundation_uses_incoming_relationship_without_new_entity(): void
    {
        $backend = dirname(__DIR__, 4);

        $controller = file_get_contents(
            $backend.'/app/Modules/Organizations/Controllers/CustomerAdminController.php',
        );

        $routes = file_get_contents(
            $backend.'/routes/api.php',
        );

        self::assertIsString($controller);
        self::assertIsString($routes);

        foreach ([
            'public function store(',
            'AresEconomicSubjectService',
            'Organization::TYPE_CARRIER',
            "'source_organization_id'",
            "'target_organization_id'",
            'OrganizationRelationship::TYPE_SUBCONTRACTING',
            'OrganizationRelationship::STATUS_ENDED',
        ] as $marker) {
            self::assertStringContainsString(
                $marker,
                $controller,
            );
        }

        self::assertStringContainsString(
            "'/customers'",
            $routes,
        );

        self::assertStringContainsString(
            "'store'",
            $routes,
        );

        self::assertStringContainsString(
            "'perm:pricing.manage'",
            $routes,
        );
    }
}
