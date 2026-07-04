<?php

namespace App\Modules\Identity\Controllers;

use App\Core\Http\BaseController;
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

    public function user(Request $request): JsonResponse
    {
        return $this->success(
            new UserResource($request->user())
        );
    }
}
