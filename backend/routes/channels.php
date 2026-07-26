<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\Trips\Models\Trip;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'App.Models.User.{id}',
    static function (User $user, int|string $id): bool {
        return (int) $user->getAuthIdentifier() === (int) $id;
    },
    [
        'guards' => [
            'sanctum',
        ],
    ]
);

Broadcast::channel(
    'trip.{tripId}',
    static function (User $user, int|string $tripId): bool {
        return Trip::query()
            ->whereKey((int) $tripId)
            ->where(
                'user_id',
                (int) $user->getAuthIdentifier()
            )
            ->exists();
    },
    [
        'guards' => [
            'sanctum',
        ],
    ]
);
