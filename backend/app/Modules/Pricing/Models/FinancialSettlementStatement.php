<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FinancialSettlementStatement extends Model
{
    use HasUuids;

    public const RECIPIENT_ORGANIZATION = 'organization';

    public const RECIPIENT_DRIVER = 'driver';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_CANCELLED = 'cancelled';

    public const OUTPUT_CARRIER_PAYABLE = 'carrier_payable';

    public const OUTPUT_CARRIER_RECEIVABLE = 'carrier_receivable';

    public const OUTPUT_DRIVER_PAYOUT = 'driver_payout';

    public const OUTPUT_DRIVER_DEDUCTION = 'driver_deduction';

    public const OUTPUT_ZERO_BALANCE = 'zero_balance';

    /** @var list<string> */
    public const OUTPUT_KINDS = [
        self::OUTPUT_CARRIER_PAYABLE,
        self::OUTPUT_CARRIER_RECEIVABLE,
        self::OUTPUT_DRIVER_PAYOUT,
        self::OUTPUT_DRIVER_DEDUCTION,
        self::OUTPUT_ZERO_BALANCE,
    ];

    /** @var list<string> */
    public const RECIPIENT_TYPES = [self::RECIPIENT_ORGANIZATION, self::RECIPIENT_DRIVER];

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_CLOSED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'public_id', 'owner_organization_id', 'recipient_type',
        'recipient_organization_id', 'recipient_driver_id',
        'period_from', 'period_until', 'currency', 'status',
        'earning_amount_minor', 'deduction_amount_minor', 'net_balance_minor',
        'source_snapshot', 'idempotency_key', 'command_fingerprint', 'revision',
        'created_by_user_id', 'approved_by_user_id', 'approved_at', 'closed_at', 'cancelled_at',
        'billing_document_id', 'output_kind', 'output_direction', 'output_materialized_at',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function ownerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'owner_organization_id');
    }

    public function recipientOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'recipient_organization_id');
    }

    public function recipientDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'recipient_driver_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function billingDocument(): BelongsTo
    {
        return $this->belongsTo(BillingDocument::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(FinancialSettlementStatementLine::class)->orderBy('position');
    }

    public function events(): HasMany
    {
        return $this->hasMany(FinancialSettlementStatementEvent::class)->orderBy('revision');
    }

    protected function casts(): array
    {
        return [
            'period_from' => 'immutable_date', 'period_until' => 'immutable_date',
            'earning_amount_minor' => 'integer', 'deduction_amount_minor' => 'integer',
            'net_balance_minor' => 'integer', 'source_snapshot' => 'array', 'revision' => 'integer',
            'approved_at' => 'immutable_datetime', 'closed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime', 'output_materialized_at' => 'immutable_datetime',
        ];
    }
}
