<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialCalculationEvent extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_CALCULATED = 'calculated';

    public const TYPE_REVIEW_STARTED = 'review_started';

    public const TYPE_APPROVED = 'approved';

    public const TYPE_CLOSED = 'closed';

    public const TYPE_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const EVENT_TYPES = [
        self::TYPE_CALCULATED,
        self::TYPE_REVIEW_STARTED,
        self::TYPE_APPROVED,
        self::TYPE_CLOSED,
        self::TYPE_CANCELLED,
    ];

    protected $table = 'financial_calculation_events';

    /** @var list<string> */
    protected $fillable = [
        'financial_calculation_id',
        'organization_id',
        'event_type',
        'from_status',
        'to_status',
        'acted_by_user_id',
        'reason',
        'metadata',
        'created_at',
    ];

    /**
     * @return BelongsTo<FinancialCalculation, $this>
     */
    public function financialCalculation(): BelongsTo
    {
        return $this->belongsTo(
            FinancialCalculation::class,
        );
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class,
        );
    }

    /** @return BelongsTo<User, $this> */
    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'acted_by_user_id',
        );
    }

    public function isInitialCalculationEvent(): bool
    {
        return $this->event_type === self::TYPE_CALCULATED
            && $this->from_status === null
            && $this->to_status ===
                FinancialCalculation::STATUS_CALCULATED;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }
}
