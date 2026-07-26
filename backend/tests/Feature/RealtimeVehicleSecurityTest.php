<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\TripRealtimeBroadcast;
use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Trips\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RealtimeVehicleSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_vehicle_list(): void
    {
        $this
            ->getJson('/api/vehicles')
            ->assertUnauthorized();
    }

    public function test_guest_cannot_access_vehicle_positions(): void
    {
        $this
            ->getJson('/api/vehicles/1/positions')
            ->assertUnauthorized();
    }

    public function test_guest_cannot_update_gps_position(): void
    {
        $this
            ->postJson('/api/gps/update', [])
            ->assertUnauthorized();
    }

    public function test_user_only_sees_owned_active_vehicles(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownedActive = $this->createVehicle(
            $owner,
            'OWN-ACTIVE',
            'VIN-OWN-ACTIVE',
            true
        );

        $ownedInactive = $this->createVehicle(
            $owner,
            'OWN-INACTIVE',
            'VIN-OWN-INACTIVE',
            false
        );

        $foreignActive = $this->createVehicle(
            $otherUser,
            'FOREIGN-ACTIVE',
            'VIN-FOREIGN-ACTIVE',
            true
        );

        Sanctum::actingAs($owner);

        $response = $this
            ->getJson('/api/vehicles')
            ->assertOk();

        $vehicleIds = collect(
            $response->json()
        )->pluck('id');

        $this->assertTrue(
            $vehicleIds->contains($ownedActive->getKey())
        );

        $this->assertFalse(
            $vehicleIds->contains($ownedInactive->getKey())
        );

        $this->assertFalse(
            $vehicleIds->contains($foreignActive->getKey())
        );
    }

    public function test_user_can_read_own_vehicle_positions(): void
    {
        $owner = User::factory()->create();

        $ownedVehicle = $this->createVehicle(
            $owner,
            'OWN-POSITIONS',
            'VIN-OWN-POSITIONS',
            true
        );

        Sanctum::actingAs($owner);

        $this
            ->getJson(
                '/api/vehicles/'
                .$ownedVehicle->getKey()
                .'/positions'
            )
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_user_cannot_read_foreign_vehicle_positions(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $foreignVehicle = $this->createVehicle(
            $otherUser,
            'FOREIGN-POSITIONS',
            'VIN-FOREIGN-POSITIONS',
            true
        );

        Sanctum::actingAs($owner);

        $this
            ->getJson(
                '/api/vehicles/'
                .$foreignVehicle->getKey()
                .'/positions'
            )
            ->assertNotFound();
    }

    public function test_gps_requires_owned_matching_vehicle_and_trip(): void
    {
        Event::fake([
            TripRealtimeBroadcast::class,
        ]);

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownedVehicle = $this->createVehicle(
            $owner,
            'OWN-GPS',
            'VIN-OWN-GPS',
            true
        );

        $secondOwnedVehicle = $this->createVehicle(
            $owner,
            'OWN-GPS-SECOND',
            'VIN-OWN-GPS-SECOND',
            true
        );

        $foreignVehicle = $this->createVehicle(
            $otherUser,
            'FOREIGN-GPS',
            'VIN-FOREIGN-GPS',
            true
        );

        $ownedTrip = $this->createTrip(
            $owner,
            $ownedVehicle
        );

        $otherOwnedTrip = $this->createTrip(
            $owner,
            $secondOwnedVehicle
        );

        $foreignTrip = $this->createTrip(
            $otherUser,
            $foreignVehicle
        );

        Sanctum::actingAs($owner);

        $this
            ->postJson(
                '/api/gps/update',
                $this->gpsPayload(
                    $foreignVehicle,
                    $foreignTrip
                )
            )
            ->assertNotFound();

        $this
            ->postJson(
                '/api/gps/update',
                $this->gpsPayload(
                    $ownedVehicle,
                    $otherOwnedTrip
                )
            )
            ->assertNotFound();

        $this
            ->postJson(
                '/api/gps/update',
                $this->gpsPayload(
                    $ownedVehicle,
                    $ownedTrip
                )
            )
            ->assertOk()
            ->assertJsonPath(
                'data.vehicle_id',
                $ownedVehicle->getKey()
            )
            ->assertJsonPath(
                'data.trip_id',
                $ownedTrip->getKey()
            );

        $this->assertDatabaseHas(
            'vehicle_positions',
            [
                'vehicle_id' => $ownedVehicle->getKey(),
                'trip_id' => $ownedTrip->getKey(),
            ]
        );

        $this->assertDatabaseCount(
            'vehicle_positions',
            1
        );

        Event::assertDispatched(
            TripRealtimeBroadcast::class
        );
    }

    public function test_gps_rejects_invalid_tracking_values(): void
    {
        $owner = User::factory()->create();

        $vehicle = $this->createVehicle(
            $owner,
            'GPS-VALIDATION',
            'VIN-GPS-VALIDATION',
            true
        );

        $trip = $this->createTrip(
            $owner,
            $vehicle
        );

        Sanctum::actingAs($owner);

        $this
            ->postJson(
                '/api/gps/update',
                [
                    'vehicle_id' => $vehicle->getKey(),
                    'trip_id' => $trip->getKey(),
                    'latitude' => 91,
                    'longitude' => 181,
                    'speed' => 301,
                    'heading' => 360,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'latitude',
                'longitude',
                'speed',
                'heading',
            ]);

        $this->assertDatabaseCount(
            'vehicle_positions',
            0
        );
    }

    private function createVehicle(
        User $user,
        string $registrationNumber,
        string $vin,
        bool $active
    ): Vehicle {
        return Vehicle::query()->create([
            'user_id' => $user->getKey(),
            'registration_number' => $registrationNumber,
            'vin' => $vin,
            'manufacturer' => 'Test',
            'model' => 'Vehicle',
            'year' => 2026,
            'fuel_type' => 'diesel',
            'mileage' => 0,
            'active' => $active,
        ]);
    }

    private function createTrip(
        User $user,
        Vehicle $vehicle
    ): Trip {
        return Trip::query()->create([
            'user_id' => $user->getKey(),
            'vehicle_id' => $vehicle->getKey(),
            'origin' => 'Praha',
            'destination' => 'Brno',
            'status' => Trip::STATUS_PLANNED,
        ]);
    }

    /**
     * @return array<string, int|float>
     */
    private function gpsPayload(
        Vehicle $vehicle,
        Trip $trip
    ): array {
        return [
            'vehicle_id' => $vehicle->getKey(),
            'trip_id' => $trip->getKey(),
            'latitude' => 50.087,
            'longitude' => 14.421,
            'speed' => 42,
            'heading' => 180,
        ];
    }
}
