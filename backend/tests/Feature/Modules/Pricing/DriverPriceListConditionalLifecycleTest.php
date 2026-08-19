<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Drivers\Services\DriverSupervisoryScopeService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Pricing\Models\DriverPriceList;
use App\Modules\Pricing\Models\DriverPriceListConditionalBand;
use App\Modules\Pricing\Models\DriverPriceListConditionalRule;
use App\Modules\Pricing\Models\DriverPriceListConditionalRuleMetricComponent;
use App\Modules\Pricing\Models\DriverPriceListItem;
use App\Modules\Pricing\Models\DriverPriceListVersion;
use App\Modules\Pricing\Services\DriverPriceListWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class DriverPriceListConditionalLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            Carbon::parse('2026-08-16 11:00:00'),
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_complete_conditional_rule_lifecycle_is_atomic(): void
    {
        $foundation = $this->fixture();
        $master = $foundation[0];
        $actor = $foundation[1];
        $priceList = $foundation[2];
        $assignment = $foundation[3];
        $service = app(DriverPriceListWriteService::class);

        $created = $service->createDraft(
            actor: $actor,
            organizationId: (int) $master->getKey(),
            data: [
                'driver_organization_assignment_id' => (int) $assignment->getKey(),
                'code' => 'DRV-COND-'.Str::random(16),
                'name' => 'Conditional driver compensation',
                'description' => null,
                'currency' => 'CZK',
                'valid_from' => '2026-09-01',
                'valid_until' => null,
                'change_reason' => 'Initial conditional rules',
                'items' => $this->items('15.0000'),
                'conditional_rules' => [
                    $this->conditionalRulePayload(
                        'redirected_share',
                        ['redirected_parcels'],
                        '1.5000',
                    ),
                    $this->conditionalRulePayload(
                        'delivery_quality',
                        ['delivered_parcels'],
                        '2.0000',
                    ),
                ],
            ],
        );

        $createdVersion = $created->versions()
            ->where('version_number', 1)
            ->firstOrFail();

        self::assertSame(2, $createdVersion->conditionalRules()->count());
        self::assertSame(4, $this->componentCount($createdVersion));
        self::assertSame(4, $this->bandCount($createdVersion));
        self::assertSame(4, $createdVersion->items()->count());

        $invalidCode = 'DRV-INVALID-'.Str::random(16);
        $invalidCreateRule = $this->overlappingRule('invalid_create');

        try {
            $service->createDraft(
                actor: $actor,
                organizationId: (int) $master->getKey(),
                data: [
                    'driver_organization_assignment_id' => (int) $assignment->getKey(),
                    'code' => $invalidCode,
                    'name' => 'Invalid conditional price list',
                    'description' => null,
                    'currency' => 'CZK',
                    'valid_from' => '2026-09-01',
                    'valid_until' => null,
                    'change_reason' => 'Must not persist',
                    'items' => $this->items('99.0000'),
                    'conditional_rules' => [$invalidCreateRule],
                ],
            );

            self::fail('Overlapping create payload was accepted.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey(
                'conditional_rules.0.bands',
                $exception->errors(),
            );
        }

        $this->assertDatabaseMissing('driver_price_lists', ['code' => $invalidCode]);

        $current = $priceList->versions()
            ->where('version_number', 1)
            ->firstOrFail();
        $sourceRule = $this->seedConditionalRule($current);

        $createResponse = $this
            ->withHeader('X-Organization-ID', (string) $master->getKey())
            ->postJson(
                $this->versionsUrl($priceList),
                [
                    'expected_current_version' => 1,
                    'valid_from' => '2026-09-01',
                    'valid_until' => null,
                    'change_reason' => 'Copied September rules',
                    'items' => $this->items('12.0000'),
                ],
            );

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.version_number', 2)
            ->assertJsonPath('data.lock_version', 1)
            ->assertJsonPath('data.status', 'draft');

        $draft = $priceList->versions()
            ->where('version_number', 2)
            ->firstOrFail();
        $copiedRule = $draft->conditionalRules()->firstOrFail();

        self::assertSame(1, $draft->conditionalRules()->count());
        self::assertSame(2, $this->componentCount($draft));
        self::assertSame(1, $this->bandCount($draft));
        self::assertNotSame($sourceRule->getKey(), $copiedRule->getKey());
        self::assertSame('redirected_share', $copiedRule->getAttribute('code'));

        $replacement = $this->conditionalRulePayload(
            'delivery_quality',
            [
                'delivered_parcels',
                'redirected_parcels',
                'customer_rejected_parcels',
            ],
            '2.0000',
        );

        $updateResponse = $this
            ->withHeader('X-Organization-ID', (string) $master->getKey())
            ->putJson(
                $this->versionUrl($priceList, 2),
                [
                    'expected_lock_version' => 1,
                    'valid_from' => '2026-09-01',
                    'valid_until' => null,
                    'change_reason' => 'Replace copied rules',
                    'items' => $this->items('14.0000'),
                    'conditional_rules' => [$replacement],
                ],
            );

        $updateResponse
            ->assertOk()
            ->assertJsonPath('data.lock_version', 2)
            ->assertJsonPath('data.status', 'draft');

        $draft->refresh();
        self::assertSame(1, $draft->conditionalRules()->count());
        self::assertTrue($draft->conditionalRules()->where('code', 'delivery_quality')->exists());
        self::assertFalse($draft->conditionalRules()->where('code', 'redirected_share')->exists());
        self::assertSame(4, $this->componentCount($draft));
        self::assertSame(2, $this->bandCount($draft));

        $invalidResponse = $this
            ->withHeader('X-Organization-ID', (string) $master->getKey())
            ->putJson(
                $this->versionUrl($priceList, 2),
                [
                    'expected_lock_version' => 2,
                    'valid_from' => '2026-09-01',
                    'valid_until' => null,
                    'change_reason' => 'Invalid overlapping update',
                    'items' => $this->items('99.0000'),
                    'conditional_rules' => [
                        $this->overlappingRule('invalid_update'),
                    ],
                ],
            );

        $invalidResponse
            ->assertUnprocessable()
            ->assertJsonValidationErrors('conditional_rules.0.bands');

        $draft->refresh();
        $deliveredRate = $draft->items()
            ->where('code', DriverPriceListItem::CODE_DELIVERED_PARCELS)
            ->firstOrFail()
            ->getAttribute('unit_rate');

        self::assertSame(2, (int) $draft->getAttribute('lock_version'));
        self::assertSame('14.0000', (string) $deliveredRate);
        self::assertTrue($draft->conditionalRules()->where('code', 'delivery_quality')->exists());
        self::assertFalse($draft->conditionalRules()->where('code', 'invalid_update')->exists());
        self::assertSame(4, $this->componentCount($draft));
        self::assertSame(2, $this->bandCount($draft));

        $approvalResponse = $this
            ->withHeader('X-Organization-ID', (string) $master->getKey())
            ->postJson(
                $this->versionUrl($priceList, 2).'/approve',
                ['expected_lock_version' => 2],
            );

        $approvalResponse
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.lock_version', 2);

        $draft->refresh();
        self::assertSame(DriverPriceListVersion::STATUS_APPROVED, $draft->getAttribute('status'));
        self::assertSame(1, $draft->conditionalRules()->count());
        self::assertSame(4, $this->componentCount($draft));
        self::assertSame(2, $this->bandCount($draft));
    }

    /**
     * @return array{
     *     Organization,
     *     User,
     *     DriverPriceList,
     *     DriverOrganizationAssignment
     * }
     */
    private function fixture(): array
    {
        $master = Organization::query()->create([
            'name' => 'Master carrier',
            'type' => Organization::TYPE_MASTER,
            'status' => Organization::STATUS_ACTIVE,
        ]);

        $actor = User::factory()->create();

        OrganizationMembership::query()->create([
            'organization_id' => $master->getKey(),
            'user_id' => $actor->getKey(),
            'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
        ]);

        $registrar = app(PermissionRegistrar::class);

        $registrar->setPermissionsTeamId(
            (int) $master->getKey(),
        );

        foreach ([
            DriverSupervisoryAuthorizationService::CURRENT_MANAGE_PERMISSION,
            'compensation.manage',
        ] as $permissionName) {
            $actor->givePermissionTo(
                Permission::findOrCreate(
                    $permissionName,
                    'web',
                ),
            );
        }

        $actor->unsetRelation('roles');
        $actor->unsetRelation('permissions');
        $registrar->forgetCachedPermissions();

        $driverUser = User::factory()->create();

        $driver = Driver::query()->create([
            'user_id' => $driverUser->getKey(),
            'first_name' => 'Draft',
            'last_name' => 'Version',
            'phone' => null,
            'email' => null,
            'license_number' => 'S022-R06-'.Str::uuid(),
            'license_category' => 'B',
            'active' => true,
        ]);

        $assignment = DriverOrganizationAssignment::query()->create([
            'driver_id' => $driver->getKey(),
            'organization_id' => $master->getKey(),
            'employment_type' => DriverOrganizationAssignment::EMPLOYMENT_EMPLOYEE,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'end_reason' => null,
            'created_by_user_id' => $actor->getKey(),
            'ended_by_user_id' => null,
        ]);

        app(DriverSupervisoryScopeService::class)
            ->grantOrganizationScope(
                organization: $master,
                supervisor: $actor,
                targetOrganization: $master,
                createdBy: $actor,
                validFrom: Carbon::parse('2026-08-01'),
            );

        Sanctum::actingAs($actor);

        $priceList = DriverPriceList::query()->create([
            'driver_organization_assignment_id' => $assignment->getKey(),
            'managed_by_organization_id' => $master->getKey(),
            'code' => 'DRV-R06-'.Str::random(16),
            'name' => 'Driver compensation',
            'description' => null,
            'currency' => 'CZK',
            'status' => DriverPriceList::STATUS_ACTIVE,
            'current_version' => 1,
            'created_by_user_id' => $actor->getKey(),
        ]);

        $version = $priceList->versions()->create([
            'version_number' => 1,
            'lock_version' => 1,
            'status' => DriverPriceListVersion::STATUS_ACTIVE,
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'change_reason' => 'Initial',
            'created_by_user_id' => $actor->getKey(),
            'approved_by_user_id' => $actor->getKey(),
            'approved_at' => now(),
            'activated_at' => now(),
        ]);

        $this->replaceItems(
            $version,
            '10.0000',
        );

        return [
            $master,
            $actor,
            $priceList,
            $assignment,
        ];
    }

    private function replaceItems(
        DriverPriceListVersion $version,
        string $deliveredRate,
    ): void {
        $position = 1;

        foreach ($this->items($deliveredRate) as $item) {
            $code = (string) $item['code'];

            $version->items()->create([
                'code' => $code,
                'description' => null,
                'calculation_method' => DriverPriceListItem::CALCULATION_METHOD_QUANTITY_TIMES_RATE,
                'unit' => $code === DriverPriceListItem::CODE_ACTUAL_KM
                        ? DriverPriceListItem::UNIT_KM
                        : DriverPriceListItem::UNIT_PARCEL,
                'unit_rate' => $item['unit_rate'],
                'currency' => 'CZK',
                'quantity_source' => $code,
                'rounding_scale' => 2,
                'rounding_method' => DriverPriceListItem::ROUNDING_METHOD_HALF_UP,
                'position' => $position,
            ]);

            $position++;
        }
    }

    /**
     * @return list<array{code: string, description: null, unit_rate: string}>
     */
    private function items(
        string $deliveredRate,
    ): array {
        return [
            [
                'code' => DriverPriceListItem::CODE_DELIVERED_PARCELS,
                'description' => null,
                'unit_rate' => $deliveredRate,
            ],
            [
                'code' => DriverPriceListItem::CODE_REDIRECTED_PARCELS,
                'description' => null,
                'unit_rate' => '5.0000',
            ],
            [
                'code' => DriverPriceListItem::CODE_UNDELIVERED_PARCELS,
                'description' => null,
                'unit_rate' => '0.0000',
            ],
            [
                'code' => DriverPriceListItem::CODE_ACTUAL_KM,
                'description' => null,
                'unit_rate' => '3.5000',
            ],
        ];
    }

    private function seedConditionalRule(
        DriverPriceListVersion $version,
    ): DriverPriceListConditionalRule {
        $rule = $version->conditionalRules()->create([
            'code' => 'redirected_share',
            'name' => 'Redirected share',
            'description' => null,
            'metric_type' => 'ratio_percentage',
            'metric_numerator_source' => 'redirected_parcels',
            'metric_denominator_source' => 'loaded_parcels',
            'evaluation_scope' => 'monthly_price_list',
            'reward_method' => 'fixed_amount',
            'reward_quantity_source' => null,
            'reward_target_item_code' => null,
            'rounding_scale' => 2,
            'rounding_method' => 'half_up',
            'position' => 1,
        ]);

        $rule->metricComponents()->create([
            'component_role' => 'numerator',
            'metric_source' => 'redirected_parcels',
            'position' => 1,
        ]);

        $rule->metricComponents()->create([
            'component_role' => 'denominator',
            'metric_source' => 'loaded_parcels',
            'position' => 1,
        ]);

        $rule->bands()->create([
            'minimum_value' => '30.0000',
            'maximum_value' => null,
            'minimum_inclusive' => true,
            'maximum_inclusive' => false,
            'adjustment_value' => '1.5000',
            'position' => 1,
        ]);

        return $rule;
    }

    /**
     * @param  list<string>  $numeratorSources
     * @return array<string, mixed>
     */
    private function conditionalRulePayload(
        string $code,
        array $numeratorSources,
        string $adjustment,
    ): array {
        return [
            'code' => $code,
            'name' => str_replace('_', ' ', $code),
            'description' => null,
            'metric_type' => 'ratio_percentage',
            'metric_numerator_sources' => $numeratorSources,
            'metric_denominator_sources' => ['loaded_parcels'],
            'evaluation_scope' => 'monthly_price_list',
            'reward_method' => 'fixed_amount',
            'reward_quantity_source' => null,
            'reward_target_item_code' => null,
            'rounding_scale' => 2,
            'bands' => [
                [
                    'minimum_value' => '30.0000',
                    'maximum_value' => '40.0000',
                    'minimum_inclusive' => true,
                    'maximum_inclusive' => false,
                    'adjustment_value' => $adjustment,
                ],
                [
                    'minimum_value' => '40.0000',
                    'maximum_value' => '100.0000',
                    'minimum_inclusive' => true,
                    'maximum_inclusive' => true,
                    'adjustment_value' => $adjustment,
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function overlappingRule(string $code): array
    {
        $rule = $this->conditionalRulePayload(
            $code,
            ['redirected_parcels'],
            '8.0000',
        );

        $rule['bands'] = [
            [
                'minimum_value' => '30.0000',
                'maximum_value' => '70.0000',
                'minimum_inclusive' => true,
                'maximum_inclusive' => true,
                'adjustment_value' => '4.0000',
            ],
            [
                'minimum_value' => '60.0000',
                'maximum_value' => '100.0000',
                'minimum_inclusive' => true,
                'maximum_inclusive' => true,
                'adjustment_value' => '8.0000',
            ],
        ];

        return $rule;
    }

    private function componentCount(DriverPriceListVersion $version): int
    {
        return DriverPriceListConditionalRuleMetricComponent::query()
            ->whereIn(
                'driver_price_list_conditional_rule_id',
                $version->conditionalRules()->select('id'),
            )
            ->count();
    }

    private function bandCount(DriverPriceListVersion $version): int
    {
        return DriverPriceListConditionalBand::query()
            ->whereIn(
                'driver_price_list_conditional_rule_id',
                $version->conditionalRules()->select('id'),
            )
            ->count();
    }

    private function versionsUrl(
        DriverPriceList $priceList,
    ): string {
        return '/api/v1/driver-price-lists/'
            .$priceList->getAttribute('public_id')
            .'/versions';
    }

    private function versionUrl(
        DriverPriceList $priceList,
        int $version,
    ): string {
        return $this->versionsUrl($priceList)
            .'/'.$version;
    }
}
