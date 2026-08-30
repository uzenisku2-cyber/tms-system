<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FuelTransactionDriverAttribution extends Model
{
    protected $fillable = ['public_id', 'fuel_transaction_id', 'revision', 'previous_driver_id', 'new_driver_id', 'previous_driver_organization_assignment_id', 'new_driver_organization_assignment_id', 'reason', 'corrected_by_user_id', 'corrected_at'];

    protected $casts = ['revision' => 'integer', 'corrected_at' => 'datetime'];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FuelTransaction::class, 'fuel_transaction_id');
    }

    public function previousDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'previous_driver_id');
    }

    public function newDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'new_driver_id');
    }

    public function previousAssignment(): BelongsTo
    {
        return $this->belongsTo(DriverOrganizationAssignment::class, 'previous_driver_organization_assignment_id');
    }

    public function newAssignment(): BelongsTo
    {
        return $this->belongsTo(DriverOrganizationAssignment::class, 'new_driver_organization_assignment_id');
    }

    public function correctedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'corrected_by_user_id');
    }
}
