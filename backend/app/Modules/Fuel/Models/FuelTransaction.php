<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read Driver|null $importedDriver
 * @property-read Driver|null $actualDriver
 * @property-read Collection<int, FuelTransactionDriverAttribution> $driverAttributions
 * @property-read FuelTransactionReconciliation|null $reconciliation
 */
final class FuelTransaction extends Model
{
    protected $fillable = ['public_id', 'owner_organization_id', 'provider', 'provider_transaction_identifier', 'transaction_fingerprint', 'occurred_at', 'posting_date', 'provider_card_identifier', 'fuel_card_id', 'fuel_card_assignment_id', 'responsible_organization_id', 'driver_id', 'actual_driver_id', 'actual_driver_organization_assignment_id', 'driver_attribution_revision', 'vehicle_id', 'match_status', 'match_method', 'station_identifier', 'station_name', 'station_address', 'product_code', 'product_name', 'quantity', 'unit_of_measure', 'unit_price', 'net_amount', 'tax_amount', 'gross_amount', 'discount_amount', 'tax_rate', 'currency', 'vehicle_registration', 'odometer', 'invoice_reference', 'source_description', 'fuel_import_batch_id', 'source_row'];

    protected $casts = ['occurred_at' => 'datetime', 'posting_date' => 'date', 'quantity' => 'decimal:6', 'unit_price' => 'decimal:6', 'net_amount' => 'decimal:6', 'tax_amount' => 'decimal:6', 'gross_amount' => 'decimal:6', 'discount_amount' => 'decimal:6', 'tax_rate' => 'decimal:4', 'odometer' => 'decimal:2', 'source_row' => 'integer', 'driver_attribution_revision' => 'integer'];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function importedDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function actualDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'actual_driver_id');
    }

    public function actualDriverOrganizationAssignment(): BelongsTo
    {
        return $this->belongsTo(DriverOrganizationAssignment::class, 'actual_driver_organization_assignment_id');
    }

    public function driverAttributions(): HasMany
    {
        return $this->hasMany(FuelTransactionDriverAttribution::class)->orderBy('revision');
    }

    /** @return HasOne<FuelTransactionReconciliation, $this> */
    public function reconciliation(): HasOne
    {
        return $this->hasOne(FuelTransactionReconciliation::class);
    }

    public function effectiveDriverId(): ?int
    {
        $value = $this->getAttribute('actual_driver_id') ?? $this->getAttribute('driver_id');

        return $value === null ? null : (int) $value;
    }
}
