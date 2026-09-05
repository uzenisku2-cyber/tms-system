<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Models\VehiclePosition;
use App\Modules\Trips\Models\Trip;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string|null $public_id
 * @property int|null $user_id
 * @property string $registration_number
 * @property string $vin
 * @property string $manufacturer
 * @property string $model
 * @property int|null $year
 * @property string|null $vehicle_type
 * @property string|null $vehicle_size
 * @property string|null $color
 * @property string|null $icon
 * @property string|null $manufacturer_logo
 * @property string|null $body_style
 * @property string|null $fuel_type
 * @property int $mileage
 * @property string $odometer_unit
 * @property Carbon|null $first_registered_on
 * @property string $lifecycle_status
 * @property int $current_revision
 * @property bool $active
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Collection<int, Trip> $trips
 * @property-read Collection<int, VehiclePosition> $positions
 * @property-read VehiclePosition|null $latestPosition
 * @property-read Collection<int, VehicleOwnership> $ownerships
 * @property-read Collection<int, VehicleResponsibility> $responsibilities
 * @property-read Collection<int, VehicleDocument> $documents
 * @property-read Collection<int, VehicleRegistryEvent> $registryEvents
 * @property-read Collection<int, VehicleComplianceRecord> $complianceRecords
 * @property-read Collection<int, VehicleInsurancePolicy> $insurancePolicies
 * @property-read Collection<int, VehicleServiceRecord> $serviceRecords
 * @property-read Collection<int, VehicleIncident> $incidents
 * @property-read string $label
 */
class Vehicle extends Model
{
    protected $fillable = [
        'public_id', 'user_id', 'registration_number', 'vin', 'manufacturer', 'model',
        'year', 'vehicle_type', 'vehicle_size', 'color', 'icon', 'manufacturer_logo',
        'body_style', 'fuel_type', 'mileage', 'odometer_unit', 'first_registered_on',
        'lifecycle_status', 'current_revision', 'active', 'archived_at',
    ];

    protected $casts = [
        'active' => 'boolean', 'year' => 'integer', 'mileage' => 'integer',
        'current_revision' => 'integer', 'first_registered_on' => 'date', 'archived_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(static function (Vehicle $vehicle): void {
            $vehicle->public_id ??= (string) Str::uuid();
        });
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<Trip, $this> */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'vehicle_id');
    }

    /** @return HasMany<VehiclePosition, $this> */
    public function positions(): HasMany
    {
        return $this->hasMany(VehiclePosition::class, 'vehicle_id');
    }

    /** @return HasOne<VehiclePosition, $this> */
    public function latestPosition(): HasOne
    {
        return $this->hasOne(VehiclePosition::class, 'vehicle_id')->latestOfMany();
    }

    /** @return HasMany<VehicleOwnership, $this> */
    public function ownerships(): HasMany
    {
        return $this->hasMany(VehicleOwnership::class);
    }

    /** @return HasMany<VehicleResponsibility, $this> */
    public function responsibilities(): HasMany
    {
        return $this->hasMany(VehicleResponsibility::class);
    }

    /** @return HasMany<VehicleDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(VehicleDocument::class);
    }

    /** @return HasMany<VehicleRegistryEvent, $this> */
    public function registryEvents(): HasMany
    {
        return $this->hasMany(VehicleRegistryEvent::class);
    }

    /** @return HasMany<VehicleComplianceRecord, $this> */
    public function complianceRecords(): HasMany
    {
        return $this->hasMany(VehicleComplianceRecord::class);
    }

    /** @return HasMany<VehicleInsurancePolicy, $this> */
    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(VehicleInsurancePolicy::class);
    }

    /** @return HasMany<VehicleServiceRecord, $this> */
    public function serviceRecords(): HasMany
    {
        return $this->hasMany(VehicleServiceRecord::class);
    }

    /** @return HasMany<VehicleIncident, $this> */
    public function incidents(): HasMany
    {
        return $this->hasMany(VehicleIncident::class);
    }

    public function hasActiveTrip(): bool
    {
        return $this->trips()->whereIn('status', [Trip::STATUS_ASSIGNED, Trip::STATUS_STARTED])->exists();
    }

    public function isAvailable(): bool
    {
        return ! $this->hasActiveTrip();
    }

    public function getLabelAttribute(): string
    {
        return trim($this->manufacturer.' '.$this->model.' ('.$this->registration_number.')');
    }
}
