<?php

declare(strict_types=1);

namespace App\Modules\Drivers\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Drivers\Services\DriverRoleProvisioner;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class OwnDriverAdminController
{
    public function index(
        OrganizationContext $context,
    ): JsonResponse {
        $organizationId = $context->requireId();

        $this->assertMasterOrganization(
            $organizationId,
        );

        $drivers = $this->ownDriverQuery(
            $organizationId,
        )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->map(
                fn (Driver $driver): array => $this->resource(
                    $driver,
                ),
            )
            ->values();

        return response()->json([
            'data' => [
                'items' => $drivers,
            ],
        ]);
    }

    public function accountLookup(
        Request $request,
        OrganizationContext $context,
    ): JsonResponse {
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
            ],
        ]);

        $organizationId = $context->requireId();

        $this->assertMasterOrganization(
            $organizationId,
        );

        $email = $this->normalizeEmail(
            (string) $validated['email'],
        );

        $user = User::query()
            ->where('email', $email)
            ->first();

        if (! $user instanceof User) {
            return response()->json([
                'data' => [
                    'exists' => false,
                    'linkable' => true,
                    'requires_password' => true,
                    'account_name' => null,
                    'membership_type' => null,
                    'message' => 'Nový účet. Nastavte dočasné heslo.',
                ],
            ]);
        }

        $membership = $this->activeMembership(
            $user,
            $organizationId,
        );

        if (! $membership instanceof OrganizationMembership) {
            return response()->json([
                'data' => [
                    'exists' => true,
                    'linkable' => false,
                    'requires_password' => false,
                    'account_name' => null,
                    'membership_type' => null,
                    'message' => 'Tento e-mail už existuje, ale účet nelze v této organizaci připojit jako řidiče.',
                ],
            ]);
        }

        if (
            (string) $user->getAttribute('status')
            !== User::STATUS_ACTIVE
        ) {
            return response()->json([
                'data' => [
                    'exists' => true,
                    'linkable' => false,
                    'requires_password' => false,
                    'account_name' => (string) $user->getAttribute('name'),
                    'membership_type' => (string) $membership->getAttribute('relationship_type'),
                    'message' => 'Existující účet není aktivní.',
                ],
            ]);
        }

        if ($user->driver()->exists()) {
            return response()->json([
                'data' => [
                    'exists' => true,
                    'linkable' => false,
                    'requires_password' => false,
                    'account_name' => (string) $user->getAttribute('name'),
                    'membership_type' => (string) $membership->getAttribute('relationship_type'),
                    'message' => 'Tento účet už má profil řidiče.',
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'exists' => true,
                'linkable' => true,
                'requires_password' => false,
                'account_name' => (string) $user->getAttribute('name'),
                'membership_type' => (string) $membership->getAttribute('relationship_type'),
                'message' => 'Existující účet lze připojit jako řidiče. Heslo ani současná oprávnění se nezmění.',
            ],
        ]);
    }

    public function store(
        Request $request,
        OrganizationContext $context,
        DriverRoleProvisioner $roles,
    ): JsonResponse {
        $validated = $request->validate([
            'first_name' => [
                'required',
                'string',
                'max:100',
            ],
            'last_name' => [
                'required',
                'string',
                'max:100',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:64',
            ],            'external_driver_id' => [
                'nullable',
                'string',
                'max:32',
                'regex:/^\d{1,32}$/',
                'unique:drivers,external_driver_id',
            ],            'license_number' => [
                'nullable',
                'string',
                'max:100',
                'unique:drivers,license_number',
            ],
            'license_category' => [
                'nullable',
                'string',
                Rule::in(Driver::LICENSE_CATEGORIES),
            ],
            'password' => [
                'nullable',
                'string',
                'min:10',
                'max:128',
                'confirmed',
            ],
        ]);

        $organizationId = $context->requireId();

        $this->assertMasterOrganization(
            $organizationId,
        );

        $email = $this->normalizeEmail(
            (string) $validated['email'],
        );

        $existingUser = User::query()
            ->where('email', $email)
            ->first();

        $isExistingAccount = $existingUser instanceof User;

        if ($isExistingAccount) {
            if (
                $request->filled('password')
                || $request->filled('password_confirmation')
            ) {
                throw ValidationException::withMessages([
                    'password' => [
                        'U existujícího účtu se heslo při přidání řidiče nemění.',
                    ],
                ]);
            }

            $membership = $this->activeMembership(
                $existingUser,
                $organizationId,
            );

            if (! $membership instanceof OrganizationMembership) {
                throw ValidationException::withMessages([
                    'email' => [
                        'Tento e-mail už existuje, ale účet není aktivním členem této organizace.',
                    ],
                ]);
            }

            if (
                (string) $existingUser->getAttribute('status')
                !== User::STATUS_ACTIVE
            ) {
                throw ValidationException::withMessages([
                    'email' => [
                        'Existující účet není aktivní.',
                    ],
                ]);
            }

            if ($existingUser->driver()->exists()) {
                throw ValidationException::withMessages([
                    'email' => [
                        'Tento účet už má profil řidiče.',
                    ],
                ]);
            }
        } else {
            $request->validate([
                'password' => [
                    'required',
                    'string',
                    'min:10',
                    'max:128',
                    'confirmed',
                ],
            ]);
        }

        $result = DB::transaction(
            function () use (
                $validated,
                $organizationId,
                $roles,
                $email,
                $existingUser,
                $isExistingAccount,
            ): array {
                $firstName = trim(
                    (string) $validated['first_name'],
                );

                $lastName = trim(
                    (string) $validated['last_name'],
                );

                if ($existingUser instanceof User) {
                    $user = $existingUser;
                } else {
                    $user = new User;

                    $user->forceFill([
                        'name' => trim(
                            $firstName.' '.$lastName,
                        ),
                        'email' => $email,
                        'password' => Hash::make(
                            (string) $validated['password'],
                        ),
                        'status' => User::STATUS_ACTIVE,
                    ]);

                    $user->save();

                    OrganizationMembership::query()->create([
                        'organization_id' => $organizationId,
                        'user_id' => (int) $user->getKey(),
                        'relationship_type' => OrganizationMembership::RELATIONSHIP_EMPLOYEE,
                        'status' => OrganizationMembership::STATUS_ACTIVE,
                        'valid_from' => now(),
                        'valid_until' => null,
                    ]);
                }

                $driver = Driver::query()->create([
                    'user_id' => (int) $user->getKey(),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => self::nullableTrimmed(
                        $validated['phone'] ?? null,
                    ),
                    'email' => $email,
                    'external_driver_id' => self::nullableTrimmed(
                        $validated['external_driver_id'] ?? null,
                    ),
                    'license_number' => self::nullableTrimmed(
                        $validated['license_number'] ?? null,
                    ),
                    'license_category' => self::nullableTrimmed(
                        $validated['license_category'] ?? null,
                    ),
                    'active' => true,
                ]);

                $roles->assign(
                    $user,
                    $organizationId,
                );

                return [
                    'driver' => $driver,
                    'existing_account' => $isExistingAccount,
                ];
            },
        );

        /** @var Driver $driver */
        $driver = $result['driver'];

        $existingAccount = (bool) $result['existing_account'];

        return response()->json([
            'message' => $existingAccount
                ? 'Existující účet byl připojen jako řidič. Současná oprávnění zůstala zachována.'
                : 'Řidič a jeho přihlašovací účet byly vytvořeny.',
            'data' => $this->resource(
                $driver->load('user'),
            ) + [
                'linked_existing_account' => $existingAccount,
            ],
        ], 201);
    }

    public function update(
        Request $request,
        OrganizationContext $context,
        int $driver,
    ): JsonResponse {
        $organizationId = $context->requireId();

        $this->assertMasterOrganization(
            $organizationId,
        );

        $target = $this->findOwnDriver(
            $organizationId,
            $driver,
        );

        $user = $target->user;

        if (! $user instanceof User) {
            abort(404);
        }

        $validated = $request->validate([
            'first_name' => [
                'required',
                'string',
                'max:100',
            ],
            'last_name' => [
                'required',
                'string',
                'max:100',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore(
                        $user->getKey(),
                    ),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:64',
            ],            'external_driver_id' => [
                'nullable',
                'string',
                'max:32',
                'regex:/^\d{1,32}$/',
                Rule::unique(
                    'drivers',
                    'external_driver_id',
                )
                    ->ignore(
                        $target->getKey(),
                    ),
            ],            'license_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('drivers', 'license_number')
                    ->ignore(
                        $target->getKey(),
                    ),
            ],
            'license_category' => [
                'nullable',
                'string',
                Rule::in(Driver::LICENSE_CATEGORIES),
            ],
        ], [
            'email.unique' => 'Tento přihlašovací e-mail již používá jiný účet.',
        ]);

        DB::transaction(
            static function () use (
                $validated,
                $target,
                $user,
            ): void {
                $firstName = trim(
                    (string) $validated['first_name'],
                );

                $lastName = trim(
                    (string) $validated['last_name'],
                );

                $email = mb_strtolower(
                    trim(
                        (string) $validated['email'],
                    ),
                    'UTF-8',
                );

                $user->forceFill([
                    'name' => trim(
                        $firstName.' '.$lastName,
                    ),
                    'email' => $email,
                ]);

                $user->save();

                $target->fill([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => self::nullableTrimmed(
                        $validated['phone'] ?? null,
                    ),
                    'email' => $email,
                    'external_driver_id' => self::nullableTrimmed(
                        $validated['external_driver_id'] ?? null,
                    ),
                    'license_number' => self::nullableTrimmed(
                        $validated['license_number'] ?? null,
                    ),
                    'license_category' => self::nullableTrimmed(
                        $validated['license_category'] ?? null,
                    ),
                ]);

                $target->save();
            },
        );

        return response()->json([
            'message' => 'Údaje řidiče byly upraveny.',
            'data' => $this->resource(
                $target->refresh()->load('user'),
            ),
        ]);
    }

    private function findOwnDriver(
        int $organizationId,
        int $driverId,
    ): Driver {
        return $this->ownDriverQuery(
            $organizationId,
        )
            ->whereKey(
                $driverId,
            )
            ->firstOrFail();
    }

    /**
     * @return Builder<Driver>
     */
    private function ownDriverQuery(
        int $organizationId,
    ): Builder {
        $now = now();

        return Driver::query()
            ->with('user')
            ->whereHas(
                'user.organizationMemberships',
                static function ($query) use (
                    $organizationId,
                    $now,
                ): void {
                    $query
                        ->where(
                            'organization_id',
                            $organizationId,
                        )
                        ->where(
                            'status',
                            OrganizationMembership::STATUS_ACTIVE,
                        )
                        ->where(
                            static function ($validFrom) use ($now): void {
                                $validFrom
                                    ->whereNull('valid_from')
                                    ->orWhere(
                                        'valid_from',
                                        '<=',
                                        $now,
                                    );
                            },
                        )
                        ->where(
                            static function ($validUntil) use ($now): void {
                                $validUntil
                                    ->whereNull('valid_until')
                                    ->orWhere(
                                        'valid_until',
                                        '>=',
                                        $now,
                                    );
                            },
                        );
                },
            );
    }

    private function activeMembership(
        User $user,
        int $organizationId,
    ): ?OrganizationMembership {
        $moment = now();

        return $user
            ->organizationMemberships()
            ->where(
                'organization_id',
                $organizationId,
            )
            ->where(
                'status',
                OrganizationMembership::STATUS_ACTIVE,
            )
            ->get()
            ->first(
                static fn (
                    OrganizationMembership $membership,
                ): bool => $membership->isActiveAt(
                    $moment,
                ),
            );
    }

    private function assertMasterOrganization(
        int $organizationId,
    ): void {
        $organization = Organization::query()
            ->whereKey($organizationId)
            ->where(
                'status',
                Organization::STATUS_ACTIVE,
            )
            ->firstOrFail();

        if (
            $organization->getAttribute('type')
            !== Organization::TYPE_MASTER
        ) {
            abort(
                403,
                'Own-driver administration requires master organization context.',
            );
        }
    }

    /**
     * @return array<string, int|string|null>
     */
    private function resource(
        Driver $driver,
    ): array {
        $user = $driver->user;

        return [
            'id' => (int) $driver->getKey(),
            'first_name' => (string) $driver->getAttribute('first_name'),
            'last_name' => (string) $driver->getAttribute('last_name'),
            'full_name' => (string) $driver->full_name,
            'email' => $user instanceof User
                ? (string) $user->getAttribute('email')
                : $driver->getAttribute('email'),
            'phone' => $driver->getAttribute('phone'),
            'external_driver_id' => $driver->getAttribute('external_driver_id'),
            'license_number' => $driver->getAttribute('license_number'),
            'license_category' => $driver->getAttribute('license_category'),
            'driver_status' => (string) $driver->getAttribute('status'),
            'account_status' => $user instanceof User
                ? (string) $user->getAttribute('status')
                : null,
            'role' => DriverRoleProvisioner::ROLE_NAME,
        ];
    }

    private function normalizeEmail(
        string $email,
    ): string {
        return mb_strtolower(
            trim($email),
            'UTF-8',
        );
    }

    private static function nullableTrimmed(
        mixed $value,
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }
}
