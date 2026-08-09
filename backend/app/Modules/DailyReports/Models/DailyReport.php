<?php

namespace App\Modules\DailyReports\Models;

use App\Models\User;
use App\Models\Vehicle;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Trips\Models\Trip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyReport extends Model
{
    use HasUuids;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_UNDER_REVIEW = 'under_review';

    public const STATUS_CORRECTION_REQUESTED = 'correction_requested';

    public const STATUS_CORRECTED = 'corrected';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CLOSED = 'closed';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_SUBMITTED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_CORRECTION_REQUESTED,
        self::STATUS_CORRECTED,
        self::STATUS_APPROVED,
        self::STATUS_CLOSED,
    ];

    public const ENTRY_METHOD_DRIVER = 'driver';

    public const ENTRY_METHOD_DELEGATED = 'delegated';

    public const ENTRY_METHOD_AUTHORIZED_IMPORT = 'authorized_import';

    /** @var list<string> */
    public const ENTRY_METHODS = [
        self::ENTRY_METHOD_DRIVER,
        self::ENTRY_METHOD_DELEGATED,
        self::ENTRY_METHOD_AUTHORIZED_IMPORT,
    ];

    public const ACTUAL_KM_SOURCE_DELIVERY_APPLICATION = 'delivery_application';

    public const ACTUAL_KM_SOURCE_MANUAL = 'manual';

    public const ACTUAL_KM_SOURCE_AUTHORIZED_IMPORT = 'authorized_import';

    public const ACTUAL_KM_SOURCE_OTHER = 'other';

    /** @var list<string> */
    public const ACTUAL_KM_SOURCES = [
        self::ACTUAL_KM_SOURCE_DELIVERY_APPLICATION,
        self::ACTUAL_KM_SOURCE_MANUAL,
        self::ACTUAL_KM_SOURCE_AUTHORIZED_IMPORT,
        self::ACTUAL_KM_SOURCE_OTHER,
    ];

    protected $table = 'daily_reports';

    /** @var list<string> */
    protected $fillable = [
        'public_id',
        'organization_id',
        'trip_id',
        'performed_by_driver_id',
        'vehicle_id',
        'entered_by_user_id',
        'route_number',
        'route_number_normalized',
        'service_date',
        'daily_report_form_configuration_id',
        'custom_field_values',
        'status',
        'entry_method',
        'entered_on_behalf',
        'completion_confirmed_at',
        'departure_time',
        'arrival_time',
        'loaded_parcels',
        'delivered_parcels',
        'redirected_parcels',
        'undelivered_parcels',
        'planned_km',
        'actual_km',
        'actual_km_source',
        'surcharge_amount',
        'operational_notes',
        'current_version',
        'submitted_at',
        'review_started_at',
        'reviewed_by_user_id',
        'approved_at',
        'approved_by_user_id',
        'closed_at',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /** @return BelongsTo<Trip, $this> */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /** @return BelongsTo<Driver, $this> */
    public function performedByDriver(): BelongsTo
    {
        return $this->belongsTo(
            Driver::class,
            'performed_by_driver_id',
        );
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<User, $this> */
    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'entered_by_user_id',
        );
    }

    /** @return BelongsTo<User, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by_user_id',
        );
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by_user_id',
        );
    }

    /** @return HasMany<DailyReportVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(DailyReportVersion::class);
    }

    /** @return HasMany<DailyReportEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(DailyReportEvent::class);
    }

    /**
     * @param  Builder<DailyReport>  $query
     * @return Builder<DailyReport>
     */
    public function scopeForOrganization(
        Builder $query,
        int $organizationId,
    ): Builder {
        return $query->where(
            'organization_id',
            $organizationId,
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'service_date' => 'immutable_date',
            'daily_report_form_configuration_id' => 'integer',
            'custom_field_values' => 'array',
            'entered_on_behalf' => 'boolean',
            'completion_confirmed_at' => 'immutable_datetime',
            'loaded_parcels' => 'integer',
            'delivered_parcels' => 'integer',
            'redirected_parcels' => 'integer',
            'undelivered_parcels' => 'integer',
            'planned_km' => 'decimal:2',
            'actual_km' => 'decimal:2',
            'surcharge_amount' => 'decimal:2',
            'current_version' => 'integer',
            'submitted_at' => 'immutable_datetime',
            'review_started_at' => 'immutable_datetime',
            'approved_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
        ];
    }
}
