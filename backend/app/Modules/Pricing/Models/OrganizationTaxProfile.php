<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrganizationTaxProfile extends Model
{
    public const VAT_STATUS_PAYER = 'payer';

    public const VAT_STATUS_NON_PAYER = 'non_payer';

    /** @var list<string> */
    protected $fillable = [
        'organization_id',
        'vat_status',
        'vat_rate',
        'valid_from',
        'valid_until',
        'source',
        'verified_at',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'vat_rate' => 'decimal:2',
            'valid_from' => 'immutable_date',
            'valid_until' => 'immutable_date',
            'verified_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * @param  Builder<OrganizationTaxProfile>  $query
     * @return Builder<OrganizationTaxProfile>
     */
    public function scopeEffectiveOn(Builder $query, string $date): Builder
    {
        return $query
            ->whereDate('valid_from', '<=', $date)
            ->where(static function (Builder $period) use ($date): void {
                $period
                    ->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', $date);
            });
    }
}
