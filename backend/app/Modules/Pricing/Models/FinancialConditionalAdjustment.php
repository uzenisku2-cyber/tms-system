<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FinancialConditionalAdjustment extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'financial_conditional_adjustments';

    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'organization_id',
        'organization_relationship_id',
        'price_list_id',
        'price_list_version_id',
        'price_list_conditional_rule_id',
        'price_list_conditional_band_id',
        'performed_by_driver_id',
        'evaluation_scope',
        'period_start',
        'period_end',
        'calculation_version',
        'currency',
        'metric_type',
        'metric_numerator_source',
        'metric_numerator_value',
        'metric_denominator_source',
        'metric_denominator_value',
        'metric_value',
        'reward_method',
        'reward_quantity_source',
        'reward_quantity_value',
        'reward_target_item_code',
        'reward_target_item_amount',
        'adjustment_value',
        'conditional_amount',
        'evaluation_snapshot',
        'calculated_by_user_id',
        'calculated_at',
        'supersedes_adjustment_id',
        'created_at',
    ];

    /** @return list<string> */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<OrganizationRelationship, $this> */
    public function organizationRelationship(): BelongsTo
    {
        return $this->belongsTo(OrganizationRelationship::class);
    }

    /** @return BelongsTo<PriceList, $this> */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(PriceList::class);
    }

    /** @return BelongsTo<PriceListVersion, $this> */
    public function priceListVersion(): BelongsTo
    {
        return $this->belongsTo(PriceListVersion::class);
    }

    /** @return BelongsTo<PriceListConditionalRule, $this> */
    public function conditionalRule(): BelongsTo
    {
        return $this->belongsTo(
            PriceListConditionalRule::class,
            'price_list_conditional_rule_id',
        );
    }

    /** @return BelongsTo<PriceListConditionalBand, $this> */
    public function conditionalBand(): BelongsTo
    {
        return $this->belongsTo(
            PriceListConditionalBand::class,
            'price_list_conditional_band_id',
        );
    }

    /** @return BelongsTo<Driver, $this> */
    public function performedByDriver(): BelongsTo
    {
        return $this->belongsTo(
            Driver::class,
            'performed_by_driver_id',
        );
    }

    /** @return BelongsTo<User, $this> */
    public function calculatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'calculated_by_user_id',
        );
    }

    /** @return BelongsTo<self, $this> */
    public function supersedesAdjustment(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'supersedes_adjustment_id',
        );
    }

    /** @return HasMany<self, $this> */
    public function supersededByAdjustments(): HasMany
    {
        return $this->hasMany(
            self::class,
            'supersedes_adjustment_id',
        );
    }

    /** @return HasMany<FinancialConditionalAdjustmentSource, $this> */
    public function sources(): HasMany
    {
        return $this->hasMany(
            FinancialConditionalAdjustmentSource::class,
        )->orderBy('source_position');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'period_start' => 'immutable_date',
            'period_end' => 'immutable_date',
            'calculation_version' => 'integer',
            'metric_numerator_value' => 'decimal:6',
            'metric_denominator_value' => 'decimal:6',
            'metric_value' => 'decimal:6',
            'reward_quantity_value' => 'decimal:6',
            'reward_target_item_amount' => 'decimal:2',
            'adjustment_value' => 'decimal:4',
            'conditional_amount' => 'decimal:2',
            'evaluation_snapshot' => 'array',
            'calculated_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
