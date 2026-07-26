<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Organizations\OrganizationContext;
use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

final class ResolveOrganizationContext
{
    public function __construct(
        private readonly OrganizationContext $context,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(
        Request $request,
        Closure $next,
    ): Response {
        $requestedId = $request->header(
            'X-Organization-ID',
        );

        if (
            ! is_string($requestedId)
            || preg_match('/^[1-9][0-9]*$/', $requestedId) !== 1
        ) {
            abort(
                400,
                'Valid organization context is required.',
            );
        }

        $user = $request->user();

        if (! $user instanceof User) {
            abort(401, 'Unauthenticated.');
        }

        $organizationId = (int) $requestedId;
        $now = now();

        $membershipExists = $user
            ->organizationMemberships()
            ->where('organization_id', $organizationId)
            ->where(
                'status',
                OrganizationMembership::STATUS_ACTIVE,
            )
            ->where(function ($query) use ($now): void {
                $query
                    ->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query
                    ->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $now);
            })
            ->whereHas(
                'organization',
                function ($query): void {
                    $query->where(
                        'status',
                        Organization::STATUS_ACTIVE,
                    );
                },
            )
            ->exists();

        if (! $membershipExists) {
            abort(403, 'Organization access denied.');
        }

        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');

        try {
            $this->context->set($organizationId);
            $this->permissionRegistrar
                ->setPermissionsTeamId($organizationId);

            return $next($request);
        } finally {
            $user->unsetRelation('roles');
            $user->unsetRelation('permissions');

            $this->permissionRegistrar
                ->setPermissionsTeamId(null);

            $this->context->clear();
        }
    }
}
