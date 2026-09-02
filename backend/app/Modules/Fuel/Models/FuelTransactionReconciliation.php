<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Models;

use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FuelTransactionReconciliation extends Model
{
    use HasUuids;

    public const STATUS_PENDING = 'pending';

    public const STATUS_MATCHED = 'matched';

    public const STATUS_REVIEW_REQUIRED = 'review_required';

    public const STATUS_RESOLVED = 'resolved';

    protected $fillable = ['public_id', 'owner_organization_id', 'fuel_transaction_id', 'status', 'result_code', 'effective_driver_id', 'driver_organization_assignment_id', 'service_date', 'candidate_count', 'matched_daily_report_id', 'revision', 'evaluated_at', 'resolved_at'];

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<FuelTransaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FuelTransaction::class, 'fuel_transaction_id');
    }

    /** @return BelongsTo<Driver, $this> */
    public function effectiveDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'effective_driver_id');
    }

    /** @return BelongsTo<DriverOrganizationAssignment, $this> */
    public function driverOrganizationAssignment(): BelongsTo
    {
        return $this->belongsTo(DriverOrganizationAssignment::class);
    }

    /** @return BelongsTo<DailyReport, $this> */
    public function matchedDailyReport(): BelongsTo
    {
        return $this->belongsTo(DailyReport::class, 'matched_daily_report_id');
    }

    /** @return HasMany<FuelTransactionReconciliationEvaluation, $this> */
    public function evaluations(): HasMany
    {
        return $this->hasMany(FuelTransactionReconciliationEvaluation::class)->orderBy('revision');
    }

    /** @return HasMany<FuelTransactionReconciliationDecision, $this> */
    public function decisions(): HasMany
    {
        return $this->hasMany(FuelTransactionReconciliationDecision::class)->orderBy('revision');
    }

    protected function casts(): array
    {
        return ['service_date' => 'immutable_date', 'candidate_count' => 'integer', 'revision' => 'integer', 'evaluated_at' => 'immutable_datetime', 'resolved_at' => 'immutable_datetime'];
    }
}
