<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Models;

use Illuminate\Database\Eloquent\Model;

final class DriverAvailabilityDayEvent extends Model
{
    public $timestamps = false;

    protected static function booted(): void
    {
        self::updating(static function (): void {
            throw new \RuntimeException('Availability history is append-only.');
        });
        self::deleting(static function (): void {
            throw new \RuntimeException('Availability history is append-only.');
        });
    }

    protected $fillable = [
        'availability_day_id', 'revision', 'action', 'availability',
        'decision', 'reason', 'actor_user_id', 'created_at',
    ];
}
