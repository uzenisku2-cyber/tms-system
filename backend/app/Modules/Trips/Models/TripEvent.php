<?php

declare(strict_types=1);

namespace App\Modules\Trips\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripEvent extends Model
{
    protected $fillable = [
        'trip_id',
        'user_id',
        'old_status',
        'new_status',
    ];


    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}