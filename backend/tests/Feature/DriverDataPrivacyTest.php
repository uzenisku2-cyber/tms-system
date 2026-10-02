<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Bus\ProcessCommandJob;
use App\Models\User;
use App\Modules\Drivers\Domain\Events\DriverCreated;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverDataPrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_self_provisioning_is_closed_and_driver_jobs_remain_encrypted(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/drivers', [
            'first_name' => 'Private',
            'last_name' => 'Driver',
            'phone' => '+420777123456',
            'email' => 'private.driver@example.test',
            'license_number' => 'PRIVATE-LICENSE-001',
            'license_category' => 'B',
        ])->assertStatus(410);

        $trace = DB::table('traces')
            ->where('type', 'driver.store')
            ->first();

        $this->assertNull($trace);

        $jobReflection = new \ReflectionClass(
            ProcessCommandJob::class
        );

        $this->assertTrue(
            $jobReflection->implementsInterface(
                ShouldBeEncrypted::class
            )
        );

        Queue::assertNotPushed(ProcessCommandJob::class);
    }

    public function test_driver_created_event_contains_no_personal_payload(): void
    {
        $event = new DriverCreated(driverId: 123);

        $this->assertSame(123, $event->driverId);
        $this->assertArrayNotHasKey('payload', get_object_vars($event));
    }
}
