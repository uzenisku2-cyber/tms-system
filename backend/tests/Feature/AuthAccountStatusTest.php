<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthAccountStatusTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN_URL = '/api/v1/auth/login';

    private const PASSWORD = 'secure-test-password';

    public function test_active_user_can_login_and_receives_token(): void
    {
        $user = $this->createUser(User::STATUS_ACTIVE);

        $response = $this->postJson(self::LOGIN_URL, [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user',
                ],
            ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'api',
        ]);
    }

    public function test_suspended_user_cannot_login(): void
    {
        $user = $this->createUser(User::STATUS_SUSPENDED);

        $this->postJson(self::LOGIN_URL, [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ])->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_disabled_user_cannot_login(): void
    {
        $user = $this->createUser(User::STATUS_DISABLED);

        $this->postJson(self::LOGIN_URL, [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ])->assertUnauthorized();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_authenticated_user_can_access_me_endpoint(): void
    {
        $user = $this->createUser(User::STATUS_ACTIVE);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/me')->assertOk();
    }

    private function createUser(string $status): User
    {
        return User::factory()->create([
            'password' => Hash::make(self::PASSWORD),
            'status' => $status,
            'status_changed_at' => now(),
        ]);
    }
}
