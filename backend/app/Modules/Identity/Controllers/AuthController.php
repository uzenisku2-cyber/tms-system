<?php

declare(strict_types=1);

namespace App\Modules\Identity\Controllers;

use App\Core\Http\BaseController;
use App\Models\User;
use App\Modules\Identity\Requests\LoginRequest;
use App\Modules\Identity\Resources\UserResource;
use App\Modules\Identity\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseController
{
    public function __construct(
        private readonly AuthService $service,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->service->login(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
        );

        return $this->success([
            'token' => $result->token,
            'user' => new UserResource($result->user),
        ], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->service->logout($request->user());

        return $this->success(
            message: 'Logout successful.'
        );
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(
            new UserResource($request->user())
        );
    }

    public function capabilities(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401);
        }

        $permissions = [
            'users.manage',
            'people.manage',
            'availability.confirm',
            'pricing.view',
            'compensation.view',
            'daily-reports.view',
            'daily-reports.create',
            'daily-reports.update',
            'daily-reports.enter-for-driver',
            'daily-reports.review',
            'settings.catalogs.manage',
        ];

        return $this->success([
            'permissions' => array_values(array_filter(
                $permissions,
                static fn (string $permission): bool => $user->can($permission),
            )),
        ]);
    }
}
