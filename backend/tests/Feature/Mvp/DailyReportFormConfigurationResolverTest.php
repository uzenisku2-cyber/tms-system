<?php

declare(strict_types=1);

namespace Tests\Feature\Mvp;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReportFormConfiguration;
use App\Modules\DailyReports\Services\DailyReportFormConfigurationResolver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DailyReportFormConfigurationResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_historical_child_organization_uses_highest_effective_parent_configuration(): void
    {
        $master = Organization::query()->create([
            'name' => 'Master',
            'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ]);

        $carrier = Organization::query()->create([
            'name' => 'Carrier',
            'type' => Organization::TYPE_SUBCONTRACTOR,
            'status' => Organization::STATUS_ACTIVE,
        ]);

        OrganizationRelationship::query()->create([
            'source_organization_id' => (int) $master->getKey(),
            'target_organization_id' => (int) $carrier->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ENDED,
            'valid_from' => '2026-01-01 00:00:00',
            'valid_until' => '2026-07-31 23:59:59',
        ]);

        $user = User::factory()->create();

        $configuration = DailyReportFormConfiguration::query()->create([
            'organization_id' => (int) $master->getKey(),
            'version' => 1,
            'valid_from' => '2026-01-01',
            'valid_until' => null,
            'fields' => [
                [
                    'key' => 'service_date',
                    'label' => 'Datum jízdy',
                    'order' => 1,
                    'visible' => true,
                    'required' => true,
                    'system' => true,
                ],
            ],
            'created_by_user_id' => (int) $user->getKey(),
        ]);

        $resolver = app(
            DailyReportFormConfigurationResolver::class,
        );

        self::assertSame(
            (int) $master->getKey(),
            $resolver->ownerOrganizationId(
                (int) $carrier->getKey(),
                '2026-07-15',
            ),
        );

        $resolved = $resolver->resolve(
            (int) $carrier->getKey(),
            '2026-07-15',
        );

        self::assertNotNull($resolved);
        self::assertSame(
            (int) $configuration->getKey(),
            (int) $resolved->getKey(),
        );

        self::assertSame(
            (int) $carrier->getKey(),
            $resolver->ownerOrganizationId(
                (int) $carrier->getKey(),
                '2026-08-01',
            ),
        );

        self::assertNull(
            $resolver->resolve(
                (int) $carrier->getKey(),
                '2026-08-01',
            ),
        );
    }
}
