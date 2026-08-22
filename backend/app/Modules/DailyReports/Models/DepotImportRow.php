<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Models;

use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class DepotImportRow extends Model
{
    use HasUuids;

    public const STATUS_READY = 'ready';

    public const STATUS_NO_RUN = 'no_run';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_READY,
        self::STATUS_NO_RUN,
    ];

    protected $table = 'depot_import_rows';

    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'depot_import_batch_id',
        'source_row',
        'status',
        'service_date',
        'route_number',
        'route_number_normalized',
        'carrier_name',
        'source_driver_name',
        'source_driver_key',
        'assigned_driver_id',
        'assigned_driver_organization_assignment_id',
        'departure_time',
        'arrival_time',
        'actual_km',
        'planned_km',
        'loaded_parcels',
        'delivered_parcels',
        'redirected_parcels',
        'customer_rejected_parcels',
        'reported_not_delivered_parcels',
        'computed_not_delivered_parcels',
        'surcharge_amount',
        'operational_notes',
        'errors',
        'warnings',
        'protected_values_sha256',
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

    /** @return BelongsTo<DepotImportBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(
            DepotImportBatch::class,
            'depot_import_batch_id',
        );
    }

    /** @return BelongsTo<Driver, $this> */
    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(
            Driver::class,
            'assigned_driver_id',
        );
    }

    /** @return BelongsTo<DriverOrganizationAssignment, $this> */
    public function assignedDriverOrganizationAssignment(): BelongsTo
    {
        return $this->belongsTo(
            DriverOrganizationAssignment::class,
            'assigned_driver_organization_assignment_id',
        );
    }

    /** @return HasMany<DepotImportEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(DepotImportEvent::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'depot_import_batch_id' => 'integer',
            'source_row' => 'integer',
            'service_date' => 'date',
            'assigned_driver_id' => 'integer',
            'assigned_driver_organization_assignment_id' => 'integer',
            'actual_km' => 'decimal:2',
            'planned_km' => 'decimal:2',
            'loaded_parcels' => 'integer',
            'delivered_parcels' => 'integer',
            'redirected_parcels' => 'integer',
            'customer_rejected_parcels' => 'integer',
            'reported_not_delivered_parcels' => 'integer',
            'computed_not_delivered_parcels' => 'integer',
            'surcharge_amount' => 'decimal:2',
            'errors' => 'array',
            'warnings' => 'array',
        ];
    }
}
