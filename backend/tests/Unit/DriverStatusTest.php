<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\Drivers\Models\Driver;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DriverStatusTest extends TestCase
{
    public function test_new_driver_has_active_default_status(): void
    {
        $driver = new Driver;

        $this->assertSame(Driver::STATUS_ACTIVE, $driver->status);
        $this->assertTrue($driver->isActive());
        $this->assertTrue($driver->canOperate());
    }

    public function test_suspended_driver_cannot_operate(): void
    {
        $driver = new Driver;
        $driver->status = Driver::STATUS_SUSPENDED;

        $this->assertTrue($driver->isSuspended());
        $this->assertFalse($driver->isActive());
        $this->assertFalse($driver->canOperate());
    }

    public function test_inactive_driver_cannot_operate(): void
    {
        $driver = new Driver;
        $driver->status = Driver::STATUS_INACTIVE;

        $this->assertTrue($driver->isInactive());
        $this->assertFalse($driver->isActive());
        $this->assertFalse($driver->canOperate());
    }

    public function test_status_is_not_mass_assignable(): void
    {
        $driver = new Driver;

        $this->assertFalse($driver->isFillable('status'));
        $this->assertTrue($driver->isFillable('active'));
    }

    public function test_status_changed_at_is_cast_to_datetime(): void
    {
        $driver = new Driver;
        $driver->setRawAttributes([
            'status_changed_at' => '2026-07-25 12:00:00',
        ]);

        $this->assertInstanceOf(
            Carbon::class,
            $driver->status_changed_at
        );
    }
}
