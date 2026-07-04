<?php

declare(strict_types=1);

namespace App\Modules\Identity\DTO;

use App\Models\User;

final readonly class LoginResponseDto
{
    public function __construct(
        public string $token,
        public User $user,
    ) {
    }
}