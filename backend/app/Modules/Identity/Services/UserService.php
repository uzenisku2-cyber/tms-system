<?php

namespace App\Modules\Identity\Services;

use App\Core\Services\BaseService;
use App\Models\User;
use App\Modules\Identity\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService extends BaseService
{
    public function __construct(
        private readonly UserRepository $users,
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->users->paginateUsers($perPage);
    }

    public function find(int $id): User
    {
        /** @var User */
        return $this->users->findOrFail($id);
    }

    public function create(array $data): User
    {
        return $this->users->createUser($data);
    }

    public function update(int $id, array $data): User
    {
        $user = $this->find($id);

        $this->users->updateUser($user, $data);

        return $user->refresh();
    }

    public function delete(int $id): void
    {
        $user = $this->find($id);

        $this->users->delete($user);
    }
}