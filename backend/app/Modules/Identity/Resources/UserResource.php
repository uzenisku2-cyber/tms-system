<?php

declare(strict_types=1);

namespace App\Modules\Identity\Resources;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationMembership;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;

        $now = now();
        $organizations = $user->organizationMemberships()
            ->where('status', OrganizationMembership::STATUS_ACTIVE)
            ->where(function ($query) use ($now): void {
                $query->whereNull('valid_from')->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('valid_until')->orWhere('valid_until', '>=', $now);
            })
            ->whereHas('organization', function ($query): void {
                $query->where('status', Organization::STATUS_ACTIVE);
            })
            ->with('organization:id,name')
            ->orderBy('organization_id')
            ->get()
            ->map(static fn (OrganizationMembership $membership): array => [
                'id' => $membership->organization_id,
                'name' => $membership->organization?->name,
            ])
            ->values();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'organizations' => $organizations,
            'created_at' => $user->created_at,
        ];
    }
}
