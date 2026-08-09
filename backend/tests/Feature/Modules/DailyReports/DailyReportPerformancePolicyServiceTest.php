<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\DailyReports;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\DailyReports\Models\DailyReportPerformancePolicy;
use App\Modules\DailyReports\Services\DailyReportPerformancePolicyService;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DailyReportPerformancePolicyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        app(OrganizationContext::class)->clear();

        parent::tearDown();
    }

    public function test_system_defaults_are_used_before_configuration_exists(): void
    {
        $organization = $this->organization();

        app(OrganizationContext::class)->set(
            (int) $organization->getKey(),
        );

        $effective = $this->service()->effective(
            '35',
        );

        self::assertSame(
            '15.00',
            $effective['thresholds'][
                'redirected_max_percent'
            ],
        );

        self::assertSame(
            '10.00',
            $effective['thresholds'][
                'kilometre_deviation_max_percent'
            ],
        );

        self::assertSame(
            'system',
            $effective['sources'][
                'redirected_max_percent'
            ],
        );
    }

    public function test_route_override_inherits_other_organization_thresholds(): void
    {
        $organization = $this->organization();
        $actor = User::factory()->create();

        app(OrganizationContext::class)->set(
            (int) $organization->getKey(),
        );

        $service = $this->service();

        $service->updateOrganizationDefaults(
            $actor,
            $this->thresholds(
                redirected: '20.00',
                kilometres: '12.00',
            ),
        );

        $route35 = $service->updateRouteOverride(
            $actor,
            '  35  ',
            $this->thresholds(
                redirected: '25.00',
                kilometres: null,
            ),
        );

        self::assertSame(
            '35',
            $route35['route_number'],
        );

        self::assertSame(
            '25.00',
            $route35['thresholds'][
                'redirected_max_percent'
            ],
        );

        self::assertSame(
            '12.00',
            $route35['thresholds'][
                'kilometre_deviation_max_percent'
            ],
        );

        self::assertSame(
            'route',
            $route35['sources'][
                'redirected_max_percent'
            ],
        );

        self::assertSame(
            'organization',
            $route35['sources'][
                'kilometre_deviation_max_percent'
            ],
        );

        $route16 = $service->effective('16');

        self::assertSame(
            '20.00',
            $route16['thresholds'][
                'redirected_max_percent'
            ],
        );

        self::assertSame(
            'organization',
            $route16['sources'][
                'redirected_max_percent'
            ],
        );
    }

    public function test_all_null_route_override_removes_the_override(): void
    {
        $organization = $this->organization();
        $actor = User::factory()->create();

        app(OrganizationContext::class)->set(
            (int) $organization->getKey(),
        );

        $service = $this->service();

        $service->updateOrganizationDefaults(
            $actor,
            $this->thresholds(
                redirected: '18.00',
                kilometres: '11.00',
            ),
        );

        $service->updateRouteOverride(
            $actor,
            '35',
            $this->thresholds(
                redirected: '30.00',
                kilometres: null,
            ),
        );

        self::assertDatabaseCount(
            'daily_report_performance_policies',
            2,
        );

        $effective =
            $service->updateRouteOverride(
                $actor,
                '35',
                $this->thresholds(
                    redirected: null,
                    kilometres: null,
                ),
            );

        self::assertDatabaseCount(
            'daily_report_performance_policies',
            1,
        );

        self::assertSame(
            '18.00',
            $effective['thresholds'][
                'redirected_max_percent'
            ],
        );
    }

    public function test_organization_policy_can_disable_optional_limits(): void
    {
        $organization = $this->organization();
        $actor = User::factory()->create();

        app(OrganizationContext::class)->set(
            (int) $organization->getKey(),
        );

        $service = $this->service();

        $service->updateOrganizationDefaults(
            $actor,
            $this->thresholds(
                redirected: '15.00',
                kilometres: '10.00',
                deliveredMinimum: null,
                rejectedMaximum: null,
                notDeliveredMaximum: null,
            ),
        );

        $configuration =
            $service->configuration();

        self::assertNull(
            $configuration[
                'effective_organization_defaults'
            ]['delivered_address_min_percent'],
        );

        self::assertSame(
            1,
            DailyReportPerformancePolicy::query()
                ->where(
                    'organization_id',
                    $organization->getKey(),
                )
                ->count(),
        );
    }

    private function service(): DailyReportPerformancePolicyService
    {
        return app(
            DailyReportPerformancePolicyService::class,
        );
    }

    private function organization(): Organization
    {
        return Organization::create([
            'name' => 'Performance policy carrier',
            'type' => Organization::TYPE_CARRIER,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    /**
     * @return array<string, string|null>
     */
    private function thresholds(
        ?string $redirected,
        ?string $kilometres,
        ?string $deliveredMinimum = null,
        ?string $rejectedMaximum = null,
        ?string $notDeliveredMaximum = null,
    ): array {
        return [
            'redirected_max_percent' =>
                $redirected,
            'kilometre_deviation_max_percent' =>
                $kilometres,
            'delivered_address_min_percent' =>
                $deliveredMinimum,
            'rejected_max_percent' =>
                $rejectedMaximum,
            'not_delivered_max_percent' =>
                $notDeliveredMaximum,
        ];
    }
}