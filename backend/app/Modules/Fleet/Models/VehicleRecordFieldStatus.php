<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class VehicleRecordFieldStatus extends Model
{
    public const STATUS_MISSING = 'missing';

    public const STATUS_PENDING_DOCUMENT = 'pending_document';

    public const STATUS_UNVERIFIED = 'unverified';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_NOT_APPLICABLE = 'not_applicable';

    protected $fillable = ['public_id', 'vehicle_id', 'organization_context_id', 'field_key', 'status', 'reason', 'recorded_by_user_id', 'revision'];

    protected $casts = ['revision' => 'integer'];

    protected static function booted(): void
    {
        self::creating(static function (VehicleRecordFieldStatus $status): void {
            $status->public_id ??= (string) Str::uuid();
        });
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<Organization, $this> */
    public function organizationContext(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_context_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
