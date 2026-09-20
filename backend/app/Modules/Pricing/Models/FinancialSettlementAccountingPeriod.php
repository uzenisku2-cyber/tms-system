<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

final class FinancialSettlementAccountingPeriod extends Model
{
    use HasUuids;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_REOPENED = 'reopened';

    public $timestamps = false;

    protected $fillable = [
        'public_id', 'owner_organization_id', 'period_start', 'period_end', 'currency',
        'status', 'revision', 'closed_by_user_id', 'closed_at', 'reopened_by_user_id',
        'reopened_at', 'last_reason',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected static function booted(): void
    {
        self::deleting(static fn (): never => throw new RuntimeException('Financial settlement accounting periods cannot be deleted.'));
    }

    public function ownerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'owner_organization_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(FinancialSettlementAccountingPeriodEvent::class, 'accounting_period_id')->orderBy('revision');
    }

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'revision' => 'integer', 'closed_at' => 'immutable_datetime', 'reopened_at' => 'immutable_datetime'];
    }
}
