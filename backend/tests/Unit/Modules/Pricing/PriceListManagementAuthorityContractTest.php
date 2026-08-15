<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class PriceListManagementAuthorityContractTest extends TestCase
{
    public function test_price_list_exposes_separate_management_authority(): void
    {
        $backend = dirname(__DIR__, 4);

        $model = file_get_contents(
            $backend.'/app/Modules/Pricing/Models/PriceList.php',
        );

        self::assertIsString($model);

        self::assertStringContainsString(
            "'owner_organization_id'",
            $model,
        );

        self::assertStringContainsString(
            "'customer_organization_id'",
            $model,
        );

        self::assertStringContainsString(
            "'provider_organization_id'",
            $model,
        );

        self::assertStringContainsString(
            "'managed_by_organization_id'",
            $model,
        );
    }

    public function test_existing_customer_creation_direction_is_preserved(): void
    {
        $backend = dirname(__DIR__, 4);

        $service = file_get_contents(
            $backend.'/app/Modules/Pricing/Services/PriceListWriteService.php',
        );

        self::assertIsString($service);

        self::assertStringContainsString(
            "'source_organization_id'",
            $service,
        );

        self::assertStringContainsString(
            "'owner_organization_id' => \$customerId",
            $service,
        );

        self::assertStringContainsString(
            "'customer_organization_id' => \$customerId",
            $service,
        );

        self::assertStringContainsString(
            "'provider_organization_id' => \$providerId",
            $service,
        );

        self::assertStringContainsString(
            "'managed_by_organization_id' => \$customerId",
            $service,
        );

        self::assertStringContainsString(
            "OrganizationRelationship::TYPE_SUBCONTRACTING",
            $service,
        );
    }

    public function test_lifecycle_writes_are_scoped_to_management_authority(): void
    {
        $backend = dirname(__DIR__, 4);

        $service = file_get_contents(
            $backend.'/app/Modules/Pricing/Services/PriceListWriteService.php',
        );

        self::assertIsString($service);

        self::assertStringNotContainsString(
            '->forOwnerOrganization($organizationId)',
            $service,
        );

        self::assertStringContainsString(
            "'managed_by_organization_id'",
            $service,
        );

        self::assertStringContainsString(
            "whereNull(",
            $service,
        );

        self::assertStringContainsString(
            "'owner_organization_id'",
            $service,
        );
    }

    public function test_migration_backfills_existing_management_from_owner(): void
    {
        $backend = dirname(__DIR__, 4);

        $migration = file_get_contents(
            $backend.'/database/migrations/2026_08_14_190000_add_price_list_management_organization.php',
        );

        self::assertIsString($migration);

        self::assertStringContainsString(
            'managed_by_organization_id',
            $migration,
        );

        self::assertStringContainsString(
            'SET managed_by_organization_id = owner_organization_id',
            $migration,
        );
    }
}