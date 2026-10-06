<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class CarrierProvisionalRemunerationService
{
    public function __construct(
        private readonly ProvisionalRouteRemunerationCalculator $calculator,
    ) {}

    /** @return array<string, mixed> */
    public function overview(int $carrierId): array
    {
        $scope = DB::table('daily_reports as r')
            ->whereNull('r.deleted_at')
            ->whereExists(static function (Builder $query) use ($carrierId): void {
                $query->selectRaw('1')->from('driver_organization_assignments as a')
                    ->whereColumn('a.driver_id', 'r.performed_by_driver_id')
                    ->where('a.organization_id', $carrierId)
                    ->whereRaw('DATE(a.valid_from) <= r.service_date')
                    ->where(static function (Builder $dates): void {
                        $dates->whereNull('a.valid_until')
                            ->orWhereRaw('DATE(a.valid_until) >= r.service_date');
                    });
            })
            ->whereExists(static function (Builder $query) use ($carrierId): void {
                $query->selectRaw('1')->from('organization_relationships as rel')
                    ->join('organizations as owner', 'owner.id', '=', 'rel.source_organization_id')
                    ->whereColumn('rel.source_organization_id', 'r.organization_id')
                    ->where('rel.target_organization_id', $carrierId)
                    ->where('rel.relationship_type', 'subcontracting')
                    ->whereIn('rel.status', ['active', 'ended'])
                    ->where('owner.type', Organization::TYPE_MASTER)
                    ->whereRaw('DATE(rel.valid_from) <= r.service_date')
                    ->where(static function (Builder $dates): void {
                        $dates->whereNull('rel.valid_until')
                            ->orWhereRaw('DATE(rel.valid_until) >= r.service_date');
                    });
            });

        $reports = $scope->get([
            'r.id', 'r.public_id', 'r.organization_id', 'r.performed_by_driver_id',
            'r.current_version', 'r.service_date', 'r.status', 'r.loaded_parcels',
            'r.delivered_parcels', 'r.redirected_parcels', 'r.undelivered_parcels',
            'r.actual_km', 'r.surcharge_amount',
        ]);
        $relationships = DB::table('organization_relationships as rel')
            ->join('organizations as owner', 'owner.id', '=', 'rel.source_organization_id')
            ->where('rel.target_organization_id', $carrierId)
            ->where('rel.relationship_type', 'subcontracting')
            ->whereIn('rel.status', ['active', 'ended'])
            ->where('owner.type', Organization::TYPE_MASTER)
            ->get(['rel.id', 'rel.source_organization_id', 'rel.valid_from', 'rel.valid_until']);
        $relationshipIds = $relationships->pluck('id')->all();
        $versions = $relationshipIds === [] ? collect() : DB::table('price_lists as p')
            ->join('price_list_versions as v', 'v.price_list_id', '=', 'p.id')
            ->whereIn('p.organization_relationship_id', $relationshipIds)
            ->where('p.provider_organization_id', $carrierId)
            ->where('p.status', 'active')->where('v.status', 'active')
            ->get(['p.organization_relationship_id', 'p.customer_organization_id',
                'p.currency', 'v.id', 'v.valid_from', 'v.valid_until']);
        $ids = $versions->pluck('id')->all();
        $items = $ids === [] ? collect() : DB::table('price_list_items')
            ->whereIn('price_list_version_id', $ids)
            ->get(['price_list_version_id', 'code', 'unit_rate'])
            ->groupBy('price_list_version_id');
        $rules = $ids === [] ? collect() : DB::table('price_list_conditional_rules as r')
            ->join('price_list_conditional_bands as b', 'b.price_list_conditional_rule_id', '=', 'r.id')
            ->whereIn('r.price_list_version_id', $ids)
            ->get(['r.id', 'r.price_list_version_id', 'r.code', 'r.metric_type',
                'r.evaluation_scope', 'r.reward_method', 'b.minimum_value',
                'b.minimum_inclusive', 'b.maximum_value', 'b.maximum_inclusive',
                'b.adjustment_value'])
            ->groupBy('price_list_version_id');
        $rewardRuleIds = $rules->flatten(1)->pluck('id')->all();
        $components = $rewardRuleIds === [] ? collect() : DB::table('price_list_conditional_rule_metric_components')
            ->whereIn('price_list_conditional_rule_id', $rewardRuleIds)
            ->get(['price_list_conditional_rule_id', 'component_role', 'metric_source'])
            ->groupBy('price_list_conditional_rule_id');
        $rewards = $rewardRuleIds === [] ? collect() : DB::table('price_list_conditional_rule_reward_components')
            ->whereIn('price_list_conditional_rule_id', $rewardRuleIds)
            ->get(['price_list_conditional_rule_id', 'metric_source'])
            ->groupBy('price_list_conditional_rule_id');

        $priced = [];
        $unpriced = 0;
        $unpricedMonths = [];
        foreach ($reports as $report) {
            $date = substr((string) $report->service_date, 0, 10);
            $links = $relationships->filter(static fn ($link): bool => (int) $link->source_organization_id === (int) $report->organization_id
                && substr((string) $link->valid_from, 0, 10) <= $date
                && ($link->valid_until === null || substr((string) $link->valid_until, 0, 10) >= $date));
            $candidates = $versions->filter(static fn ($version): bool => $links->contains(static fn ($link): bool => (int) $link->id === (int) $version->organization_relationship_id)
                && (int) $version->customer_organization_id === (int) $report->organization_id
                && (string) $version->valid_from <= $date
                && ($version->valid_until === null || (string) $version->valid_until >= $date));
            if ($candidates->count() !== 1 || $report->loaded_parcels === null
                || $report->delivered_parcels === null || $report->redirected_parcels === null
                || $report->actual_km === null) {
                $unpriced++;
                $unpricedMonths[substr($date, 0, 7)] = true;

                continue;
            }
            $version = $candidates->first();
            $versionItems = $items->get($version->id, collect())->keyBy('code');
            $versionRules = $rules->get($version->id, collect());
            if ($versionItems->count() !== 4 || $versionRules->count() > 1) {
                $unpriced++;
                $unpricedMonths[substr($date, 0, 7)] = true;

                continue;
            }
            $quality = '0';
            if ($versionRules->count() === 1) {
                $rule = $versionRules->first();
                $sources = $rewards->get($rule->id, collect())->pluck('metric_source')->sort()->values()->all();
                $numerator = $components->get($rule->id, collect())->where('component_role', 'numerator')
                    ->pluck('metric_source')->sort()->values()->all();
                $denominator = $components->get($rule->id, collect())->where('component_role', 'denominator')
                    ->pluck('metric_source')->values()->all();
                if ($rule->code !== 'delivery_quality' || $rule->metric_type !== 'ratio_percentage'
                    || $rule->evaluation_scope !== 'monthly_price_list'
                    || $rule->reward_method !== 'amount_per_unit'
                    || (float) $rule->minimum_value !== 20.0 || ! $rule->minimum_inclusive
                    || (float) $rule->maximum_value !== 100.0 || ! $rule->maximum_inclusive
                    || $numerator !== ['customer_rejected_parcels', 'delivered_parcels', 'redirected_parcels']
                    || $denominator !== ['loaded_parcels']
                    || $sources !== ['delivered_parcels', 'redirected_parcels']) {
                    $unpriced++;
                    $unpricedMonths[substr($date, 0, 7)] = true;

                    continue;
                }
                $quality = (string) $rule->adjustment_value;
            }
            foreach (['delivered_parcels', 'redirected_parcels', 'actual_km'] as $code) {
                if (! $versionItems->has($code)) {
                    $unpriced++;
                    $unpricedMonths[substr($date, 0, 7)] = true;

                    continue 2;
                }
            }
            $priced[] = [
                'report' => $report, 'date' => $date, 'version_id' => (int) $version->id,
                'currency' => (string) $version->currency,
                'rates' => [
                    'delivered' => (string) $versionItems->get('delivered_parcels')->unit_rate,
                    'redirected' => (string) $versionItems->get('redirected_parcels')->unit_rate,
                    'km' => (string) $versionItems->get('actual_km')->unit_rate,
                    'quality' => $quality,
                ],
            ];
        }

        $months = [];
        foreach ($priced as $row) {
            $report = $row['report'];
            $key = $row['version_id'].':'.substr($row['date'], 0, 7);
            $months[$key] ??= ['loaded' => 0, 'delivered' => 0, 'redirected' => 0, 'rejected' => 0];
            $months[$key]['loaded'] += (int) $report->loaded_parcels;
            $months[$key]['delivered'] += (int) $report->delivered_parcels;
            $months[$key]['redirected'] += (int) $report->redirected_parcels;
            $months[$key]['rejected'] += (int) ($report->undelivered_parcels ?? 0);
        }

        $routeAmounts = [];
        $total = 0;
        foreach ($priced as $row) {
            $report = $row['report'];
            $key = $row['version_id'].':'.substr($row['date'], 0, 7);
            $qualityPending = isset($unpricedMonths[substr($row['date'], 0, 7)]);
            $rates = $row['rates'];
            if ($qualityPending) {
                $rates['quality'] = '0';
            }
            $amount = $this->calculator->calculate([
                'delivered' => (int) $report->delivered_parcels,
                'redirected' => (int) $report->redirected_parcels,
                'km' => (string) $report->actual_km,
                'surcharge' => (string) ($report->surcharge_amount ?? '0'),
            ], $rates, $months[$key]);
            $total += $amount['total_minor'];
            $routeAmounts[] = [
                'report_public_id' => $report->public_id,
                'report_version' => (int) $report->current_version,
                'service_date' => $row['date'],
                'performed_by_driver_id' => (int) $report->performed_by_driver_id,
                'price_list_version_id' => $row['version_id'],
                'status' => 'provisional', 'quality_pending' => $qualityPending,
                'amounts_minor' => $amount,
            ];
        }

        return [
            'status' => 'provisional', 'currency' => 'CZK',
            'source' => 'current_driver_report', 'depot_agreement_required_for_billing' => true,
            'route_count' => count($routeAmounts), 'unpriced_route_count' => $unpriced,
            'total_minor' => $unpriced === 0 ? $total : null,
            'priced_subtotal_minor' => $total, 'routes' => $routeAmounts,
        ];
    }
}
