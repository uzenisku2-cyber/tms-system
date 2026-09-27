<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

final class VehicleLifecycleTransitionPolicy
{
    private const TRANSITIONS = [
        'active' => ['temporarily_inactive', 'restricted'],
        'temporarily_inactive' => ['active', 'disposed', 'written_off'],
        'restricted' => ['active', 'disposed', 'written_off'],
        'disposed' => ['archived'],
        'written_off' => ['archived'],
        'archived' => [],
    ];

    /** @return list<string> */
    public static function targets(string $from): array
    {
        return self::TRANSITIONS[$from] ?? [];
    }

    public static function permits(string $from, string $to): bool
    {
        return in_array($to, self::targets($from), true);
    }
}
