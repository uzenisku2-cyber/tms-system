<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use Tests\TestCase;

final class DriverQualityProfileSettingsUiTest extends TestCase
{
    public function test_statistics_settings_administer_quality_profiles_through_canonical_api(): void
    {
        $source = file_get_contents(
            resource_path('views/mvp/app.blade.php'),
        );

        self::assertIsString($source);

        foreach (
            [
                'S027-03A STATISTICS QUALITY PROFILE SETTINGS',
                'data-driver-statistics-tab="overview"',
                'data-driver-statistics-tab="quality-settings"',
                'NASTAVENÍ DÍLČÍ KVALITY',
                'drayviaDriverQualitySettingsHost',
                'drayviaDriverQualityCreateForm',
                'drayviaDriverQualityDraftForm',
                'drayviaDriverQualityBindingForm',
                'drayviaDriverQualityEffectiveForm',
                '/api/v1/daily-reports/quality-profiles',
                "+ '/targets'",
                "+ '/bindings'",
                "+ '/effective?'",
                "+ '/versions'",
                "+ '/activate'",
                "method: 'POST'",
                "method: 'PUT'",
                "method: 'DELETE'",
                'lock_version:',
                'processed_share',
                'disabled',
                'delivered_parcels',
                'redirected_parcels',
                'customer_rejected_parcels',
                'Naložené zásilky',
                'driverQualityMonthStart',
                'organization_relationship_id',
                'driver_organization_assignment_id',
                'bindDriverStatisticsTabs();',
            ] as $marker
        ) {
            self::assertStringContainsString(
                $marker,
                $source,
            );
        }

        $start = strpos(
            $source,
            'S027-03A STATISTICS QUALITY PROFILE SETTINGS',
        );
        $end = strpos(
            $source,
            'const realDriverAssignmentAdminState',
            $start === false ? 0 : $start,
        );

        if (! is_int($start) || ! is_int($end)) {
            self::fail(
                'The isolated Statistics settings block was not found.',
            );
        }

        self::assertGreaterThan($start, $end);

        $settingsBlock = substr(
            $source,
            $start,
            $end - $start,
        );

        self::assertStringNotContainsString(
            '/api/v1/external-carriers',
            $settingsBlock,
        );
        self::assertStringNotContainsString(
            '/api/v1/drivers',
            $settingsBlock,
        );
        self::assertStringNotContainsString(
            'financePrice',
            $settingsBlock,
        );
    }
}
