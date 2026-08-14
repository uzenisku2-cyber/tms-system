<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DriverScheduleDay extends Model
{
    public const STATUS_WORKING = 'working';

    public const STATUS_OFF = 'off';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_WORKING,
        self::STATUS_OFF,
    ];

    /** @var list<string> */
    protected $fillable = [
        'driver_id',
        'date',
        'status',
    ];

    /** @return BelongsTo<Driver, $this> */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function isWorking(): bool
    {
        return $this->status === self::STATUS_WORKING;
    }

    public function isOff(): bool
    {
        return $this->status === self::STATUS_OFF;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
