<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialCalculation extends Model
{
    use HasUuids;

    public const STATUS_CALCULATED = 'calculated';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_CALCULATED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_CLOSED,
        self::STATUS_CANCELLED,
    ];

    protected $table = 'financial_calculations';

    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'organization_id',
        'organization_relationship_id',
        'price_list_id',
        'price_list_version_id',
        'daily_report_id',
        'daily_report_version',
        'calculation_version',
        'status',
        'currency',
        'input_snapshot',
        'subtotal_amount',
        'total_amount',
        'calculated_by_user_id',
        'calculated_at',
        'approved_by_user_id',
        'approved_at',
        'closed_at',
        'supersedes_calculation_id',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'calculation_version' => 1,
        'status' => self::STATUS_CALCULATED,
        'subtotal_amount' => 0,
        'total_amount' => 0,
    ];

    /**
     * @return list<string>
     */
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
        return $this->belongsTo(
            Organization::class,
        );
    }

    /**
     * @return BelongsTo<OrganizationRelationship, $this>
     */
    public function organizationRelationship(): BelongsTo
    {
        return $this->belongsTo(
            OrganizationRelationship::class,
        );
    }

    /** @return BelongsTo<PriceList, $this> */
    public function priceList(): BelongsTo
    {
        return $this->belongsTo(
            PriceList::class,
        );
    }

    /** @return BelongsTo<PriceListVersion, $this> */
    public function priceListVersion(): BelongsTo
    {
        return $this->belongsTo(
            PriceListVersion::class,
        );
    }

    /** @return BelongsTo<DailyReport, $this> */
    public function dailyReport(): BelongsTo
    {
        return $this->belongsTo(
            DailyReport::class,
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

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by_user_id',
        );
    }

    /**
     * @return BelongsTo<FinancialCalculation, $this>
     */
    public function supersedesCalculation(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'supersedes_calculation_id',
        );
    }

    /**
     * @return HasMany<FinancialCalculation, $this>
     */
    public function supersededByCalculations(): HasMany
    {
        return $this->hasMany(
            self::class,
            'supersedes_calculation_id',
        );
    }

    /**
     * @return HasMany<FinancialCalculationLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(
            FinancialCalculationLine::class,
        )->orderBy('position');
    }

    /**
     * @return HasMany<FinancialCalculationEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(
            FinancialCalculationEvent::class,
        )
            ->orderBy('created_at')
            ->orderBy('id');
    }

    /**
     * @param  Builder<FinancialCalculation>  $query
     * @return Builder<FinancialCalculation>
     */
    public function scopeForOrganization(
        Builder $query,
        int $organizationId,
    ): Builder {
        return $query->where(
            'organization_id',
            $organizationId,
        );
    }

    /**
     * @param  Builder<FinancialCalculation>  $query
     * @return Builder<FinancialCalculation>
     */
    public function scopeWithStatus(
        Builder $query,
        string $status,
    ): Builder {
        return $query->where(
            'status',
            $status,
        );
    }

    public function isCalculated(): bool
    {
        return $this->status === self::STATUS_CALCULATED;
    }

    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'daily_report_version' => 'integer',
            'calculation_version' => 'integer',
            'input_snapshot' => 'array',
            'subtotal_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'calculated_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }
}
