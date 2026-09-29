<?php

declare(strict_types=1);

namespace Tests\Feature\Modules\Drivers;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverAvailabilityDayEvent;
use App\Modules\Drivers\Services\DriverAvailabilityConflictService;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use App\Modules\Trips\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class DriverAvailabilityCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-29 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        parent::tearDown();
    }

    public function test_driver_submission_requires_independent_supervisor_decision_and_preserves_history(): void
    {
        $organization = $this->organization();
        $driverUser = User::factory()->create();
        $dispatcher = User::factory()->create();
        $this->member($organization, $driverUser);
        $this->member($organization, $dispatcher);
        $this->authorizeDispatcher($organization, $dispatcher);
        $driver = $this->driver($driverUser);
        $url = '/api/v1/driver-availability-calendar';
        $headers = ['X-Organization-ID' => (string) $organization->getKey()];

        Sanctum::actingAs($driverUser);
        $this->withHeaders($headers)->getJson($url.'?month=2026-10')
            ->assertOk()->assertJsonCount(0, 'data.days');
        $this->withHeaders($headers)->postJson($url, [
            'driver_id' => $driver->getKey(), 'date' => '2026-10-02',
            'availability' => 'available', 'expected_revision' => 0,
        ])->assertCreated()->assertJsonPath('data.decision', 'pending');
        $this->withHeaders($headers)->postJson($url, [
            'driver_id' => $driver->getKey(), 'date' => '2026-10-02',
            'availability' => 'available', 'expected_revision' => 0,
        ])->assertStatus(409);
        $day = $this->withHeaders($headers)->getJson($url.'?month=2026-10')
            ->assertOk()->assertJsonPath('data.days.0.revision', 1)
            ->json('data.days.0');
        $this->withHeaders($headers)->postJson($url.'/'.$day['id'].'/decision', [
            'decision' => 'confirmed', 'reason' => 'Plán směn ověřen dispečerem.', 'expected_revision' => 1,
        ])->assertForbidden();

        Sanctum::actingAs($dispatcher);
        $this->withHeaders($headers)->postJson($url.'/'.$day['id'].'/decision', [
            'decision' => 'confirmed', 'reason' => 'Plán směn ověřen dispečerem.', 'expected_revision' => 1,
        ])->assertOk()->assertJsonPath('data.revision', 2)->assertJsonPath('data.decision', 'confirmed');
        $this->withHeaders($headers)->postJson($url.'/'.$day['id'].'/decision', [
            'decision' => 'rejected', 'reason' => 'Pozdní změna směny.', 'expected_revision' => 1,
        ])->assertStatus(409);
        $this->assertSame(2, DriverAvailabilityDayEvent::query()->where('availability_day_id', $day['id'])->count());
    }

    public function test_other_organization_cannot_read_or_decide_availability(): void
    {
        $first = $this->organization();
        $other = $this->organization();
        $driverUser = User::factory()->create();
        $outsider = User::factory()->create();
        $this->member($first, $driverUser);
        $this->member($other, $outsider);
        $driver = $this->driver($driverUser);
        $url = '/api/v1/driver-availability-calendar';
        Sanctum::actingAs($driverUser);
        $day = $this->withHeader('X-Organization-ID', (string) $first->getKey())
            ->postJson($url, ['driver_id' => $driver->getKey(), 'date' => '2026-10-02',
                'availability' => 'unavailable', 'reason' => 'Dovolená', 'expected_revision' => 0])
            ->assertCreated()->json('data');
        Sanctum::actingAs($outsider);
        $this->withHeader('X-Organization-ID', (string) $other->getKey())
            ->getJson($url.'?month=2026-10')->assertOk()->assertJsonCount(0, 'data.days');
        $this->withHeader('X-Organization-ID', (string) $other->getKey())
            ->postJson($url.'/'.$day['id'].'/decision', ['decision' => 'confirmed',
                'reason' => 'Nesprávná organizace.', 'expected_revision' => 1])->assertForbidden();
    }

    public function test_partial_day_windows_validate_and_block_trip_start_after_confirmation(): void
    {
        $organization = $this->organization();
        $driverUser = User::factory()->create();
        $dispatcher = User::factory()->create();
        $this->member($organization, $driverUser);
        $this->member($organization, $dispatcher);
        $this->authorizeDispatcher($organization, $dispatcher);
        $driver = $this->driver($driverUser);
        $url = '/api/v1/driver-availability-calendar';
        $headers = ['X-Organization-ID' => (string) $organization->getKey()];

        Sanctum::actingAs($driverUser);
        $invalid = [
            'driver_id' => $driver->getKey(), 'date' => '2026-10-02',
            'availability' => 'unavailable', 'reason' => 'Lékař', 'expected_revision' => 0,
            'windows' => [['start' => '10:00', 'end' => '11:00'], ['start' => '10:30', 'end' => '12:00']],
        ];
        $this->withHeaders($headers)->postJson($url, $invalid)->assertStatus(422);
        $invalid['windows'] = [['start' => '10:00', 'end' => '11:00'], ['start' => '14:00', 'end' => '16:00']];
        $day = $this->withHeaders($headers)->postJson($url, $invalid)
            ->assertCreated()->assertJsonPath('data.windows.0.start', '10:00')
            ->assertJsonPath('data.windows.1.end', '16:00')->json('data');

        Sanctum::actingAs($dispatcher);
        $this->withHeaders($headers)->postJson($url.'/'.$day['id'].'/decision', [
            'decision' => 'confirmed', 'reason' => 'Časové úseky ověřeny.', 'expected_revision' => 1,
        ])->assertOk();
        $event = DriverAvailabilityDayEvent::query()->where('availability_day_id', $day['id'])
            ->where('revision', 2)->firstOrFail();
        $this->assertSame('14:00', $event->windows[1]['start']);

        $conflicts = app(DriverAvailabilityConflictService::class);
        $trip = new Trip(['scheduled_at' => '2026-10-02 08:30:00']); // Prague 10:30
        try {
            $conflicts->assertTripStartAllowed($trip, (int) $driver->getKey());
            $this->fail('Trip start inside confirmed unavailability must be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(409, $exception->getStatusCode());
        }
        $trip->scheduled_at = '2026-10-02 10:30:00'; // Prague 12:30
        $conflicts->assertTripStartAllowed($trip, (int) $driver->getKey());
        $this->assertTrue(true);
    }

    public function test_existing_assigned_trip_blocks_confirmation_of_unavailability(): void
    {
        $organization = $this->organization();
        $driverUser = User::factory()->create();
        $dispatcher = User::factory()->create();
        $this->member($organization, $driverUser);
        $this->member($organization, $dispatcher);
        $this->authorizeDispatcher($organization, $dispatcher);
        $driver = $this->driver($driverUser);
        $url = '/api/v1/driver-availability-calendar';
        $headers = ['X-Organization-ID' => (string) $organization->getKey()];

        Sanctum::actingAs($driverUser);
        $day = $this->withHeaders($headers)->postJson($url, [
            'driver_id' => $driver->getKey(), 'date' => '2026-10-02',
            'availability' => 'unavailable', 'reason' => 'Nedostupnost', 'expected_revision' => 0,
            'windows' => [['start' => '10:00', 'end' => '11:00']],
        ])->assertCreated()->json('data');

        $trip = Trip::query()->create([
            'user_id' => $dispatcher->getKey(), 'driver_id' => $driver->getKey(),
            'origin' => 'Depo', 'destination' => 'CĂ­l', 'status' => Trip::STATUS_ASSIGNED,
            'scheduled_at' => '2026-10-02 08:30:00',
        ]);

        Sanctum::actingAs($dispatcher);
        $decision = [
            'decision' => 'confirmed', 'reason' => 'Kontrola ÄŤasovĂ©ho okna.', 'expected_revision' => 1,
        ];
        $this->withHeaders($headers)->postJson($url.'/'.$day['id'].'/decision', $decision)
            ->assertStatus(409);
        $this->assertSame(1, DriverAvailabilityDayEvent::query()
            ->where('availability_day_id', $day['id'])->count());

        $trip->scheduled_at = '2026-10-02 10:30:00';
        $trip->save();
        $this->withHeaders($headers)->postJson($url.'/'.$day['id'].'/decision', $decision)
            ->assertOk()->assertJsonPath('data.decision', 'confirmed');
    }

    private function organization(): Organization
    {
        return Organization::query()->create(['name' => 'Availability '.Str::uuid(),
            'type' => Organization::TYPE_MASTER, 'status' => Organization::STATUS_ACTIVE]);
    }

    private function member(Organization $organization, User $actor): void
    {
        OrganizationMembership::query()->create(['organization_id' => $organization->getKey(),
            'user_id' => $actor->getKey(), 'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
            'status' => OrganizationMembership::STATUS_ACTIVE, 'valid_from' => '2026-09-01', 'valid_until' => null]);
    }

    private function authorizeDispatcher(Organization $organization, User $actor): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $organization->getKey());
        $registrar->forgetCachedPermissions();
        $actor->givePermissionTo(Permission::findOrCreate(DriverSupervisoryAuthorizationService::CURRENT_MANAGE_PERMISSION, 'web'));
        $actor->unsetRelation('roles');
        $actor->unsetRelation('permissions');
        $registrar->forgetCachedPermissions();
    }

    private function driver(User $user): Driver
    {
        return Driver::query()->create(['user_id' => $user->getKey(), 'first_name' => 'Jan',
            'last_name' => 'Kalendář', 'license_number' => 'S131-'.Str::uuid(), 'license_category' => 'B', 'active' => true]);
    }
}
