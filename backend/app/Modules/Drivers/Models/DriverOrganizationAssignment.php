<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Models;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

final class DriverOrganizationAssignment extends Model
{
    protected $fillable = [
        'driver_id',
        'organization_id',
        'valid_from',
        'valid_until',
        'end_reason',
        'created_by_user_id',
        'ended_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    /** @return BelongsTo<Driver, $this> */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(
            Driver::class,
        );
    }

    /** @return BelongsTo<Organization, $this> */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class,
        );
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id',
        );
    }

    /** @return BelongsTo<User, $this> */
    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'ended_by_user_id',
        );
    }

    public function isActiveAt(
        Carbon $moment,
    ): bool {
        $date = $moment->toDateString();

        if (Carbon::parse($this->valid_from)->toDateString() > $date) {
            return false;
        }

        if (
            $this->valid_until !== null
            && Carbon::parse($this->valid_until)->toDateString() < $date
        ) {
            return false;
        }

        return true;
    }
}
