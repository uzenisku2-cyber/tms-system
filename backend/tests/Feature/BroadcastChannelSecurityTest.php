<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\TripRealtimeBroadcast;
use App\Models\User;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Trips\Models\Trip;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastChannelSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'broadcasting.default',
            'reverb'
        );

        config()->set(
            'broadcasting.connections.reverb.driver',
            'reverb'
        );

        config()->set(
            'broadcasting.connections.reverb.key',
            'broadcast-test-key'
        );

        config()->set(
            'broadcasting.connections.reverb.secret',
            'broadcast-test-secret'
        );

        config()->set(
            'broadcasting.connections.reverb.app_id',
            'broadcast-test-app'
        );

        config()->set(
            'broadcasting.connections.reverb.options',
            [
                'host' => '127.0.0.1',
                'port' => 8080,
                'scheme' => 'http',
                'useTLS' => false,
            ]
        );

        config()->set(
            'broadcasting.connections.reverb.client_options',
            []
        );

        app(BroadcastManager::class)->purge(
            'reverb'
        );

        /*
         * phpunit.xml initially registers the channel callbacks
         * on NullBroadcaster. Register them again after changing
         * the test broadcaster to Reverb.
         */
        require base_path(
            'routes/channels.php'
        );
    }

    public function test_realtime_test_page_is_hidden_outside_local_environment(): void
    {
        $this
            ->get('/realtime-test')
            ->assertNotFound();
    }

    public function test_trip_realtime_event_uses_private_channel(): void
    {
        $event = new TripRealtimeBroadcast(
            'trip.42',
            [
                'trip_id' => 42,
                'latitude' => 50.087,
                'longitude' => 14.421,
            ]
        );

        $channel = $event->broadcastOn();

        $this->assertInstanceOf(
            PrivateChannel::class,
            $channel
        );

        $this->assertSame(
            'private-trip.42',
            $channel->name
        );

        $this->assertSame(
            'trip.position.updated',
            $event->broadcastAs()
        );
    }

    public function test_guest_cannot_authorize_private_trip_channel(): void
    {
        $this
            ->postJson(
                '/broadcasting/auth',
                $this->authorizationPayload(1)
            )
            ->assertUnauthorized();
    }

    public function test_owner_can_authorize_private_trip_channel(): void
    {
        $owner = User::factory()->create();
        $trip = $this->createTrip($owner);

        $token = $owner
            ->createToken('broadcast-test')
            ->plainTextToken;

        $this
            ->withToken($token)
            ->postJson(
                '/broadcasting/auth',
                $this->authorizationPayload(
                    (int) $trip->getKey()
                )
            )
            ->assertOk()
            ->assertJsonStructure([
                'auth',
            ]);
    }

    public function test_user_cannot_authorize_foreign_trip_channel(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $foreignTrip = $this->createTrip(
            $otherUser
        );

        $token = $owner
            ->createToken('broadcast-test')
            ->plainTextToken;

        $this
            ->withToken($token)
            ->postJson(
                '/broadcasting/auth',
                $this->authorizationPayload(
                    (int) $foreignTrip->getKey()
                )
            )
            ->assertForbidden();
    }

    private function createTrip(User $user): Trip
    {
        $vehicle = Vehicle::query()->create([
            'user_id' => $user->getKey(),
            'registration_number' => 'BC-'.$user->getKey(),
            'vin' => 'BCAST'.
                str_pad(
                    (string) $user->getKey(),
                    12,
                    '0',
                    STR_PAD_LEFT
                ),
            'manufacturer' => 'Test',
            'model' => 'Vehicle',
            'year' => 2026,
            'fuel_type' => 'diesel',
            'mileage' => 0,
            'active' => true,
        ]);

        return Trip::query()->create([
            'user_id' => $user->getKey(),
            'vehicle_id' => $vehicle->getKey(),
            'origin' => 'Praha',
            'destination' => 'Brno',
            'status' => Trip::STATUS_PLANNED,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function authorizationPayload(int $tripId): array
    {
        return [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-trip.'.$tripId,
        ];
    }
}
