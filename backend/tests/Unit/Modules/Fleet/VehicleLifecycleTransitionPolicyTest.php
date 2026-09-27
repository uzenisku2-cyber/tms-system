<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Fleet;

use App\Modules\Fleet\Services\VehicleLifecycleTransitionPolicy;
use PHPUnit\Framework\TestCase;

final class VehicleLifecycleTransitionPolicyTest extends TestCase
{
    public function test_only_approved_transitions_are_permitted(): void
    {
        $expected = [
            'active' => ['temporarily_inactive', 'restricted'],
            'temporarily_inactive' => ['active', 'disposed', 'written_off'],
            'restricted' => ['active', 'disposed', 'written_off'],
            'disposed' => ['archived'],
            'written_off' => ['archived'],
            'archived' => [],
        ];
        foreach ($expected as $from => $targets) {
            self::assertSame($targets, VehicleLifecycleTransitionPolicy::targets($from));
            foreach (array_keys($expected) as $to) {
                self::assertSame(in_array($to, $targets, true), VehicleLifecycleTransitionPolicy::permits($from, $to), "$from -> $to");
            }
        }
        self::assertSame([], VehicleLifecycleTransitionPolicy::targets('unknown'));
    }
}
