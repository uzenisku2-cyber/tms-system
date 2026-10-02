<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Modules\Organizations\Models\Organization;
use App\Modules\Organizations\Models\OrganizationRelationship;
use App\Modules\Pricing\Models\PriceList;
use App\Modules\Pricing\Models\PriceListVersion;
use App\Modules\Pricing\Resources\PriceListResource;
use App\Modules\Pricing\Resources\PriceListVersionResource;
use Illuminate\Http\JsonResponse;

final class CarrierPriceListReadController
{
    public function index(OrganizationContext $context): JsonResponse
    {
        $carrierId = $context->requireId();
        abort_unless(Organization::query()
            ->whereKey($carrierId)
            ->where('type', Organization::TYPE_SUBCONTRACTOR)
            ->exists(), 403);

        $lists = PriceList::query()
            ->where('provider_organization_id', $carrierId)
            ->where('status', PriceList::STATUS_ACTIVE)
            ->whereColumn('owner_organization_id', 'customer_organization_id')
            ->whereColumn('managed_by_organization_id', 'customer_organization_id')
            ->whereHas('organizationRelationship', static function ($query) use ($carrierId): void {
                $query->where('target_organization_id', $carrierId)
                    ->whereColumn('source_organization_id', 'price_lists.customer_organization_id')
                    ->where('relationship_type', OrganizationRelationship::TYPE_SUBCONTRACTING)
                    ->whereIn('status', [
                        OrganizationRelationship::STATUS_ACTIVE,
                        OrganizationRelationship::STATUS_ENDED,
                    ]);
            })
            ->whereHas('versions', static function ($query): void {
                $query->where('status', PriceListVersion::STATUS_ACTIVE);
            })
            ->with(['customerOrganization', 'providerOrganization', 'versions' => static function ($query): void {
                $query->where('status', PriceListVersion::STATUS_ACTIVE)
                    ->with(['items', 'conditionalRules.metricComponents', 'conditionalRules.rewardComponents', 'conditionalRules.bands']);
            }])
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $lists->map(static function (PriceList $list): array {
            return [
                'price_list' => (new PriceListResource($list))->resolve(),
                'versions' => $list->versions->map(
                    static fn (PriceListVersion $version): array => (new PriceListVersionResource($version))->resolve(),
                )->values()->all(),
            ];
        })->values()->all()]);
    }
}
