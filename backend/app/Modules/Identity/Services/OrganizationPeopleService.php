<?php

declare(strict_types=1);

namespace App\Modules\Identity\Services;

use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

final class OrganizationPeopleService
{
    public function __construct(private readonly OrganizationPeopleRoleProvisioner $roles) {}

    /** @return array<string, mixed> */
    public function index(int $organizationId): array
    {
        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($organizationId);

        try {
            $members = OrganizationMembership::query()
                ->where('organization_id', $organizationId)
                ->where('status', OrganizationMembership::STATUS_ACTIVE)
                ->where(static function ($query): void {
                    $query->whereNull('valid_from')->orWhere('valid_from', '<=', now());
                })
                ->where(static function ($query): void {
                    $query->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                })
                ->with('user.driver')->orderBy('id')->get()
                ->map(static function (OrganizationMembership $membership): array {
                    $user = $membership->user;
                    $user->unsetRelation('roles');
                    $user->unsetRelation('permissions');

                    return [
                        'id' => (int) $user->getKey(),
                        'name' => (string) $user->name,
                        'email' => (string) $user->email,
                        'status' => (string) $user->status,
                        'roles' => $user->getRoleNames()->values()->all(),
                        'driver_id' => $user->driver?->getKey(),
                    ];
                })->values()->all();
        } finally {
            $registrar->setPermissionsTeamId($previous);
        }

        return ['members' => $members];
    }

    /**
     * @param  list<string>  $roleNames
     * @return array<string, mixed>
     */
    public function replaceRoles(int $organizationId, User $actor, int $userId, array $roleNames): array
    {
        $target = User::query()->findOrFail($userId);
        abort_if($target->getKey() === $actor->getKey(), 403, 'You cannot change your own roles.');

        OrganizationMembership::query()
            ->where('organization_id', $organizationId)
            ->where('user_id', $userId)
            ->where('status', OrganizationMembership::STATUS_ACTIVE)
            ->where(static function ($query): void {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(static function ($query): void {
                $query->whereNull('valid_until')->orWhere('valid_until', '>=', now());
            })
            ->firstOrFail();

        $roleNames = array_values(array_unique($roleNames));
        sort($roleNames);
        if ($roleNames === [] || array_diff($roleNames, array_keys(OrganizationPeopleRoleProvisioner::ROLES)) !== []) {
            throw ValidationException::withMessages(['roles' => 'Vyberte povolené role.']);
        }
        if (in_array('driver', $roleNames, true) && ! Driver::query()
            ->where('user_id', $userId)
            ->whereHas('organizationAssignments', static function ($query) use ($organizationId): void {
                $query->where('organization_id', $organizationId);
            })->exists()) {
            throw ValidationException::withMessages(['roles' => 'Řidič musí mít profil a přiřazení k dopravci.']);
        }

        $registrar = app(PermissionRegistrar::class);
        $previous = $registrar->getPermissionsTeamId();
        try {
            $registrar->setPermissionsTeamId($organizationId);
            $target->unsetRelation('roles');
            $target->unsetRelation('permissions');
            $current = $target->getRoleNames()->all();
            if (array_diff($current, array_keys(OrganizationPeopleRoleProvisioner::ROLES)) !== []) {
                abort(403, 'Protected organization roles cannot be changed here.');
            }
            if (DB::table('model_has_permissions')
                ->where('organization_id', $organizationId)
                ->where('model_type', $target->getMorphClass())
                ->where('model_id', $userId)->exists()) {
                abort(409, 'Direct permissions must be reviewed separately.');
            }

            DB::transaction(function () use ($organizationId, $userId, $target, $roleNames): void {
                DB::table('model_has_roles')->where('organization_id', $organizationId)
                    ->where('model_type', $target->getMorphClass())
                    ->where('model_id', $userId)->delete();
                $target->unsetRelation('roles');
                $this->roles->assign($target, $organizationId, $roleNames);
            });
            $target->unsetRelation('roles');
            $target->unsetRelation('permissions');

            return [
                'user_id' => $userId,
                'organization_id' => $organizationId,
                'roles' => $target->getRoleNames()->values()->all(),
            ];
        } finally {
            $target->unsetRelation('roles');
            $target->unsetRelation('permissions');
            $registrar->setPermissionsTeamId($previous);
            $registrar->forgetCachedPermissions();
        }
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function create(int $organizationId, User $actor, array $input): array
    {
        $organization = Organization::query()->whereKey($organizationId)
            ->where('status', Organization::STATUS_ACTIVE)
            ->whereIn('type', [Organization::TYPE_MASTER, Organization::TYPE_SUBCONTRACTOR])
            ->firstOrFail();
        $email = mb_strtolower(trim((string) $input['email']));
        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Účet již existuje. Propojení existujícího účtu vyžaduje samostatný postup.',
            ]);
        }

        /** @var list<string> $roleNames */
        $roleNames = array_values(array_unique($input['roles']));
        sort($roleNames);
        $initialPassword = Str::random(24);

        return DB::transaction(function () use ($organization, $actor, $input, $email, $roleNames, $initialPassword): array {
            $user = new User;
            $user->forceFill([
                'name' => trim((string) $input['first_name'].' '.(string) $input['last_name']),
                'email' => $email,
                'password' => Hash::make($initialPassword),
                'status' => User::STATUS_ACTIVE,
            ]);
            $user->save();
            OrganizationMembership::query()->create([
                'organization_id' => (int) $organization->getKey(),
                'user_id' => (int) $user->getKey(),
                'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
                'status' => OrganizationMembership::STATUS_ACTIVE,
                'valid_from' => now(),
            ]);
            $this->roles->assign($user, (int) $organization->getKey(), $roleNames);
            if (in_array('driver', $roleNames, true)) {
                $driver = Driver::query()->create([
                    'user_id' => (int) $user->getKey(),
                    'first_name' => trim((string) $input['first_name']),
                    'last_name' => trim((string) $input['last_name']),
                    'email' => $email,
                    'active' => true,
                ]);
                DriverOrganizationAssignment::query()->create([
                    'driver_id' => (int) $driver->getKey(),
                    'organization_id' => (int) $organization->getKey(),
                    'employment_type' => $organization->type === Organization::TYPE_MASTER
                        ? DriverOrganizationAssignment::EMPLOYMENT_EMPLOYEE
                        : DriverOrganizationAssignment::EMPLOYMENT_OTHER,
                    'valid_from' => now()->toDateString(),
                    'created_by_user_id' => (int) $actor->getKey(),
                ]);
            }

            return [
                'user_id' => (int) $user->getKey(),
                'organization_id' => (int) $organization->getKey(),
                'email' => $email,
                'roles' => $roleNames,
                'initial_password' => $initialPassword,
            ];
        });
    }
}
