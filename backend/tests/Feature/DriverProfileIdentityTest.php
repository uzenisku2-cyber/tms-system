<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverProfileIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_one_driver_profile_relation(): void
    {
        $user = User::factory()->create();
        $driver = $this->createDriver($user, 'DRIVER-001');

        $this->assertTrue(
            $user->driver()->firstOrFail()->is($driver)
        );
    }

    public function test_user_cannot_have_two_driver_profiles(): void
    {
        $user = User::factory()->create();

        $this->createDriver($user, 'DRIVER-001');

        $this->expectException(QueryException::class);

        $this->createDriver($user, 'DRIVER-002');
    }

    private function createDriver(User $user, string $license): Driver
    {
        return Driver::create([
            'user_id' => $user->id,
            'first_name' => 'Test',
            'last_name' => 'Driver',
            'license_number' => $license,
        ]);
    }
}
