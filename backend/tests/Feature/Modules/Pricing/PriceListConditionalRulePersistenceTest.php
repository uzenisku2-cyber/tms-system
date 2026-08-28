<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Pricing;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListConditionalRule;
use App\Modules\Pricing\Models\PriceListConditionalRuleMetricComponent;
use App\Modules\Pricing\Models\PriceListConditionalRuleRewardComponent;
use App\Modules\Pricing\Models\PriceListItem;
use App\Modules\Pricing\Models\PriceListVersion;
use App\Modules\Pricing\Services\PriceListWriteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class PriceListConditionalRulePersistenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(
            Carbon::parse('2026-08-16 12:00:00'),
        );

        $this->organizationContext()->clear();
    }

    protected function tearDown(): void
    {
        $this->organizationContext()->clear();
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_rule_tree_is_created_replaced_and_copied_to_a_new_version(): void
    {
        [$actor, $relationship] = $this->commercialFoundation();

        $priceList = $this->writeService()
            ->createProviderManagedDraft(
                $actor,
                (int) $relationship->getKey(),
                $this->createPayload(),
            );

        $version = $priceList->versions()
            ->where('version_number', 1)
            ->firstOrFail();

        self::assertSame(
            PriceListVersion::STATUS_DRAFT,
            $version->getAttribute('status'),
        );
        self::assertSame(2, $version->conditionalRules()->count());
        self::assertSame(2, $this->rewardComponentCount($version));
        self::assertSame(
            6,
            $this->componentCount($version),
        );
        self::assertSame(2, $this->bandCount($version));

        $quality = $version->conditionalRules()
            ->where('code', 'delivery_quality')
            ->firstOrFail();

        self::assertSame(
            PriceListConditionalRule::SOURCE_DELIVERED_PARCELS,
            $quality->getAttribute('metric_numerator_source'),
        );
        self::assertSame(
            PriceListConditionalRule::SOURCE_LOADED_PARCELS,
            $quality->getAttribute('metric_denominator_source'),
        );
        self::assertSame(
            [
                PriceListConditionalRule::SOURCE_DELIVERED_PARCELS,
                PriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
                PriceListConditionalRule::SOURCE_CUSTOMER_REJECTED_PARCELS,
            ],
            $quality->numeratorComponents()
                ->pluck('metric_source')
                ->all(),
        );
        self::assertSame(
            [
                PriceListConditionalRule::SOURCE_DELIVERED_PARCELS,
                PriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
            ],
            $quality->rewardComponents()
                ->pluck('metric_source')
                ->all(),
        );

        $updated = $this->writeService()->updateDraftVersion(
            $actor,
            (string) $priceList->getAttribute('public_id'),
            1,
            [
                'expected_lock_version' => 1,
                'valid_from' => '2026-08-01',
                'valid_until' => null,
                'change_reason' => 'Replace conditional rules',
                'items' => $this->items('40.0000'),
                'conditional_rules' => [
                    $this->redirectedRule('600.0000'),
                ],
            ],
        );

        self::assertSame(2, $updated->getAttribute('lock_version'));
        self::assertSame(1, $updated->conditionalRules()->count());
        self::assertSame(0, $this->rewardComponentCount($updated));
        self::assertSame(2, $this->componentCount($updated));
        self::assertSame(1, $this->bandCount($updated));
        self::assertFalse(
            $updated->conditionalRules()
                ->where('code', 'delivery_quality')
                ->exists(),
        );

        $approved = $this->writeService()->approveDraftVersion(
            $actor,
            (string) $priceList->getAttribute('public_id'),
            1,
            ['expected_lock_version' => 2],
        );

        self::assertSame(
            PriceListVersion::STATUS_APPROVED,
            $approved->getAttribute('status'),
        );

        $copy = $this->writeService()->createDraftVersion(
            $actor,
            (string) $priceList->getAttribute('public_id'),
            [
                'expected_current_version' => 1,
                'valid_from' => '2026-09-01',
                'valid_until' => null,
                'change_reason' => 'September replacement',
            ],
        );

        self::assertSame(2, $copy->getAttribute('version_number'));
        self::assertSame(
            PriceListVersion::STATUS_DRAFT,
            $copy->getAttribute('status'),
        );
        self::assertSame(
            0,
            $copy->items()->count(),
        );
        self::assertSame(1, $copy->conditionalRules()->count());
        self::assertSame(0, $this->rewardComponentCount($copy));
        self::assertSame(2, $this->componentCount($copy));
        self::assertSame(1, $this->bandCount($copy));

        $copiedRule = $copy->conditionalRules()->firstOrFail();

        self::assertNotSame(
            $updated->conditionalRules()->firstOrFail()->getKey(),
            $copiedRule->getKey(),
        );
        self::assertSame(
            PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_PRICE_LIST,
            $copiedRule->getAttribute('evaluation_scope'),
        );
        self::assertSame(
            2,
            PriceList::query()
                ->whereKey($priceList->getKey())
                ->value('current_version'),
        );
    }

    public function test_invalid_replacement_preserves_the_existing_draft_tree(): void
    {
        [$actor, $relationship] = $this->commercialFoundation();

        $priceList = $this->writeService()
            ->createProviderManagedDraft(
                $actor,
                (int) $relationship->getKey(),
                $this->createPayload(),
            );

        $invalidRule = $this->qualityRule();
        $invalidRule['bands'] = [
            [
                'minimum_value' => '90.0000',
                'maximum_value' => '96.0000',
                'minimum_inclusive' => true,
                'maximum_inclusive' => true,
                'adjustment_value' => '1.0000',
            ],
            [
                'minimum_value' => '95.0000',
                'maximum_value' => '100.0000',
                'minimum_inclusive' => true,
                'maximum_inclusive' => true,
                'adjustment_value' => '2.0000',
            ],
        ];

        try {
            $this->writeService()->updateDraftVersion(
                $actor,
                (string) $priceList->getAttribute('public_id'),
                1,
                [
                    'expected_lock_version' => 1,
                    'valid_from' => '2026-08-01',
                    'valid_until' => null,
                    'change_reason' => 'Invalid overlap',
                    'items' => $this->items('99.0000'),
                    'conditional_rules' => [$invalidRule],
                ],
            );

            self::fail('An overlapping replacement was accepted.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey(
                'conditional_rules.0.bands',
                $exception->errors(),
            );
        }

        $version = $priceList->versions()
            ->where('version_number', 1)
            ->firstOrFail();

        self::assertSame(1, $version->getAttribute('lock_version'));
        self::assertSame(2, $version->conditionalRules()->count());
        self::assertSame(6, $this->componentCount($version));
        self::assertSame(2, $this->bandCount($version));
        self::assertSame(
            '35.0000',
            $version->items()
                ->where('code', PriceListItem::CODE_DELIVERED_PARCELS)
                ->firstOrFail()
                ->getAttribute('unit_rate'),
        );
    }

    /**
     * @return array{User, OrganizationRelationship}
     */
    private function commercialFoundation(): array
    {
        $customer = $this->createOrganization(
            'Parcel network customer',
            Organization::TYPE_CARRIER,
        );

        $provider = $this->createOrganization(
            'Delivery provider',
            Organization::TYPE_MASTER,
        );

        $actor = User::factory()->create();

        $relationship = OrganizationRelationship::query()->create([
            'source_organization_id' => $customer->getKey(),
            'target_organization_id' => $provider->getKey(),
            'relationship_type' => OrganizationRelationship::TYPE_SUBCONTRACTING,
            'status' => OrganizationRelationship::STATUS_ACTIVE,
            'valid_from' => '2026-01-01 00:00:00',
            'valid_until' => null,
        ]);

        $this->organizationContext()->set(
            (int) $provider->getKey(),
        );

        return [$actor, $relationship];
    }

    private function createOrganization(
        string $name,
        string $type,
    ): Organization {
        return Organization::query()->create([
            'name' => $name,
            'type' => $type,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    /** @return array<string, mixed> */
    private function createPayload(): array
    {
        return [
            'name' => 'Customer billing 2026',
            'description' => 'Integration persistence test',
            'currency' => 'CZK',
            'valid_from' => '2026-08-01',
            'valid_until' => null,
            'change_reason' => 'Initial configuration',
            'items' => $this->items('35.0000'),
            'conditional_rules' => [
                $this->qualityRule(),
                $this->redirectedRule('500.0000'),
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    private function items(string $deliveredRate): array
    {
        $rates = [
            PriceListItem::CODE_DELIVERED_PARCELS => $deliveredRate,
            PriceListItem::CODE_REDIRECTED_PARCELS => '10.0000',
            PriceListItem::CODE_UNDELIVERED_PARCELS => '0.0000',
            PriceListItem::CODE_ACTUAL_KM => '5.0000',
        ];

        $items = [];

        foreach (PriceListItem::CODES as $code) {
            $items[] = [
                'code' => $code,
                'description' => null,
                'unit_rate' => $rates[$code],
            ];
        }

        return $items;
    }

    /** @return array<string, mixed> */
    private function qualityRule(): array
    {
        return [
            'code' => 'delivery_quality',
            'name' => 'Delivery quality',
            'description' => null,
            'metric_type' => PriceListConditionalRule::METRIC_TYPE_RATIO_PERCENTAGE,
            'metric_numerator_sources' => [
                PriceListConditionalRule::SOURCE_DELIVERED_PARCELS,
                PriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
                PriceListConditionalRule::SOURCE_CUSTOMER_REJECTED_PARCELS,
            ],
            'metric_denominator_sources' => [
                PriceListConditionalRule::SOURCE_LOADED_PARCELS,
            ],
            'evaluation_scope' => PriceListConditionalRule::EVALUATION_SCOPE_PER_ROUTE,
            'reward_method' => PriceListConditionalRule::REWARD_METHOD_AMOUNT_PER_UNIT,
            'reward_quantity_sources' => [
                PriceListConditionalRule::SOURCE_DELIVERED_PARCELS,
                PriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
            ],
            'reward_quantity_source' => PriceListConditionalRule::SOURCE_DELIVERED_PARCELS,
            'reward_target_item_code' => null,
            'rounding_scale' => 2,
            'bands' => [
                [
                    'minimum_value' => '95.0000',
                    'maximum_value' => null,
                    'minimum_inclusive' => true,
                    'maximum_inclusive' => false,
                    'adjustment_value' => '2.0000',
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function redirectedRule(string $adjustment): array
    {
        return [
            'code' => 'redirected_share',
            'name' => 'Redirected share',
            'description' => null,
            'metric_type' => PriceListConditionalRule::METRIC_TYPE_RATIO_PERCENTAGE,
            'metric_numerator_sources' => [
                PriceListConditionalRule::SOURCE_REDIRECTED_PARCELS,
            ],
            'metric_denominator_sources' => [
                PriceListConditionalRule::SOURCE_LOADED_PARCELS,
            ],
            'evaluation_scope' => PriceListConditionalRule::EVALUATION_SCOPE_MONTHLY_PRICE_LIST,
            'reward_method' => PriceListConditionalRule::REWARD_METHOD_FIXED_AMOUNT,
            'reward_quantity_source' => null,
            'reward_target_item_code' => null,
            'rounding_scale' => 2,
            'bands' => [
                [
                    'minimum_value' => '5.0000',
                    'maximum_value' => null,
                    'minimum_inclusive' => true,
                    'maximum_inclusive' => false,
                    'adjustment_value' => $adjustment,
                ],
            ],
        ];
    }

    private function componentCount(
        PriceListVersion $version,
    ): int {
        return PriceListConditionalRuleMetricComponent::query()
            ->whereIn(
                'price_list_conditional_rule_id',
                $version->conditionalRules()->select('id'),
            )
            ->count();
    }

    private function bandCount(PriceListVersion $version): int
    {
        return (int) $version->conditionalRules()
            ->withCount('bands')
            ->get()
            ->sum('bands_count');
    }

    private function rewardComponentCount(PriceListVersion $version): int
    {
        return PriceListConditionalRuleRewardComponent::query()
            ->whereIn(
                'price_list_conditional_rule_id',
                $version->conditionalRules()->select('id'),
            )
            ->count();
    }

    private function organizationContext(): OrganizationContext
    {
        return app(OrganizationContext::class);
    }

    private function writeService(): PriceListWriteService
    {
        return app(PriceListWriteService::class);
    }
}
