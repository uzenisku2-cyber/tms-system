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

final class BillingDocument extends Model
{
    use HasUuids;

    public const TYPE_CUSTOMER_INVOICE = 'customer_invoice';

    public const TYPE_EXTERNAL_CARRIER_SETTLEMENT = 'external_carrier_settlement';

    public const TYPE_DRIVER_REMUNERATION = 'driver_remuneration';

    public const VAT_STANDARD = 'standard';

    public const VAT_NOT_APPLICABLE = 'not_applicable';

    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'owner_organization_id',
        'counterparty_organization_id',
        'driver_id',
        'document_type',
        'period_from',
        'period_until',
        'currency',
        'vat_treatment',
        'vat_status_snapshot',
        'net_amount',
        'vat_rate',
        'vat_amount',
        'gross_amount',
        'status',
        'source_snapshot',
        'created_by_user_id',
        'approved_by_user_id',
        'approved_at',
        'closed_at',
    ];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected function casts(): array
    {
        return [
            'period_from' => 'immutable_date',
            'period_until' => 'immutable_date',
            'net_amount' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'source_snapshot' => 'array',
            'approved_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Organization, $this> */
    public function ownerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'owner_organization_id');
    }

    /** @return BelongsTo<Organization, $this> */
    public function counterpartyOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'counterparty_organization_id');
    }

    /** @return BelongsTo<Driver, $this> */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /** @return HasMany<BillingDocumentLine, $this> */
    public function lines(): HasMany
    {
        return $this->hasMany(BillingDocumentLine::class)->orderBy('position');
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
