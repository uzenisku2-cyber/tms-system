<?php

namespace App\Modules\Identity\Controllers;

use App\Core\Http\BaseController;
use App\Modules\Identity\Requests\StoreUserRequest;
use App\Modules\Identity\Requests\UpdateUserRequest;
use App\Modules\Identity\Resources\UserResource;
use App\Modules\Identity\Services\UserService;
use Illuminate\Http\JsonResponse;

class UserController extends BaseController
{
    public function __construct(
        private readonly UserService $service,
    ) {}

    public function index(): JsonResponse
    {
        $users = $this->service->paginate();

        return $this->success([
            'items' => UserResource::collection($users),
            'pagination' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(
            new UserResource(
                $this->service->find($id)
            )
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->service->create(
            $request->validated()
        );

        return $this->success(
            new UserResource($user),
            'User created.',
            201
        );
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->service->update(
            $id,
            $request->validated()
        );

        return $this->success(
            new UserResource($user),
            'User updated.'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);

        return $this->success(
            message: 'User deleted.'
        );
    }
}
