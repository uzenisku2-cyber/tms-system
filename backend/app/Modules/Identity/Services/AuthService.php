<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Core\Services\BaseService;
use App\Models\User;
use App\Modules\Identity\DTO\LoginResponseDto;
use App\Modules\Identity\Repositories\UserRepository;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class AuthService extends BaseService
{
    public function __construct(
        private readonly UserRepository $users
    ) {}

    public function login(string $email, string $password): LoginResponseDto
    {
        $user = $this->users->findByEmail($email);

        if (
            ! $user
            || ! Hash::check($password, $user->password)
            || ! $user->canAuthenticate()
        ) {
            throw new AuthenticationException('Invalid credentials.');
        }

        return new LoginResponseDto(
            user: $user,
            token: $user->createToken('api')->plainTextToken,
        );
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }
}
