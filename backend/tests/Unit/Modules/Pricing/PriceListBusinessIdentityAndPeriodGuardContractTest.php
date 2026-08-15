<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Pricing;

use PHPUnit\Framework\TestCase;

final class PriceListBusinessIdentityAndPeriodGuardContractTest extends TestCase
{
    public function test_visible_business_identity_is_unique_and_immutable(): void
    {
        $backendRoot = dirname(__DIR__, 4);

        $migration = file_get_contents(
            $backendRoot
            .'/database/migrations/2026_08_12_165500_add_price_list_business_identity_and_period_guard.php',
        );

        $resource = file_get_contents(
            $backendRoot
            .'/app/Modules/Pricing/Resources/PriceListResource.php',
        );

        self::assertIsString($migration);
        self::assertIsString($resource);

        self::assertStringContainsString(
            "string('code', 32)",
            $migration,
        );

        self::assertStringContainsString(
            'price_lists_code_unique',
            $migration,
        );

        self::assertStringContainsString(
            'Price-list business code is immutable.',
            $migration,
        );

        self::assertStringContainsString(
            "'PL-'",
            $migration,
        );

        self::assertStringContainsString(
            "'code' => (string) \$priceList->getAttribute(",
            $resource,
        );
    }

    public function test_same_relationship_cannot_overlap_on_one_calendar_day(): void
    {
        $backendRoot = dirname(__DIR__, 4);

        $migration = file_get_contents(
            $backendRoot
            .'/database/migrations/2026_08_12_165500_add_price_list_business_identity_and_period_guard.php',
        );

        self::assertIsString($migration);

        self::assertStringContainsString(
            'organization_relationship_id',
            $migration,
        );

        self::assertStringContainsString(
            'EXCLUDE USING gist',
            $migration,
        );

        self::assertStringContainsString(
            'price_list_versions_relationship_period_exclusion',
            $migration,
        );

        self::assertStringContainsString(
            "'active'",
            $migration,
        );

        self::assertStringContainsString(
            "'replaced'",
            $migration,
        );

        self::assertStringContainsString(
            "'expired'",
            $migration,
        );

        self::assertStringContainsString(
            'daterange(',
            $migration,
        );
    }
}
