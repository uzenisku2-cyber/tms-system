<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Bus\ProcessCommandJob;
use App\Models\User;
use App\Modules\Drivers\Application\Commands\CreateDriverCommand;
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

    public function test_driver_creation_uses_encrypted_job_and_safe_trace(): void
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
        ])->assertAccepted();

        $trace = DB::table('traces')
            ->where('type', 'driver.store')
            ->first();

        $this->assertNotNull($trace);

        $payload = json_decode(
            (string) $trace->payload,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame([
            'user_id' => $user->getKey(),
        ], $payload);

        $jobReflection = new \ReflectionClass(
            ProcessCommandJob::class
        );

        $this->assertTrue(
            $jobReflection->implementsInterface(
                ShouldBeEncrypted::class
            )
        );

        Queue::assertPushed(
            ProcessCommandJob::class,
            static fn (ProcessCommandJob $job): bool => $job->command
                instanceof CreateDriverCommand
        );
    }

    public function test_driver_created_event_contains_no_personal_payload(): void
    {
        $event = new DriverCreated(driverId: 123);

        $this->assertSame(123, $event->driverId);
        $this->assertArrayNotHasKey('payload', get_object_vars($event));
    }
}
