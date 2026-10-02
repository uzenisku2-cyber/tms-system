<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Core\Organizations\OrganizationContext;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class CarrierOperationalOverviewController
{
    public function index(OrganizationContext $context): JsonResponse
    {
        $carrierId = $context->requireId();
        abort_unless(Organization::query()->whereKey($carrierId)
            ->where('type', Organization::TYPE_SUBCONTRACTOR)->exists(), 403);

        $reports = DB::table('daily_reports')
            ->whereNull('daily_reports.deleted_at')
            ->whereExists(static function (Builder $query) use ($carrierId): void {
                $query->selectRaw('1')->from('driver_organization_assignments as a')
                    ->whereColumn('a.driver_id', 'daily_reports.performed_by_driver_id')
                    ->where('a.organization_id', $carrierId)
                    ->whereRaw('DATE(a.valid_from) <= daily_reports.service_date')
                    ->where(static function (Builder $dates): void {
                        $dates->whereNull('a.valid_until')
                            ->orWhereRaw('DATE(a.valid_until) >= daily_reports.service_date');
                    });
            })
            ->whereExists(static function (Builder $query) use ($carrierId): void {
                $query->selectRaw('1')->from('organization_relationships as rel')
                    ->join('organizations as owner', 'owner.id', '=', 'rel.source_organization_id')
                    ->whereColumn('rel.source_organization_id', 'daily_reports.organization_id')
                    ->where('rel.target_organization_id', $carrierId)
                    ->where('rel.relationship_type', 'subcontracting')
                    ->whereIn('rel.status', ['active', 'ended'])
                    ->where('owner.type', Organization::TYPE_MASTER)
                    ->whereRaw('DATE(rel.valid_from) <= daily_reports.service_date')
                    ->where(static function (Builder $dates): void {
                        $dates->whereNull('rel.valid_until')
                            ->orWhereRaw('DATE(rel.valid_until) >= daily_reports.service_date');
                    });
            });

        $stats = (clone $reports)->selectRaw(
            'COUNT(*) AS routes, COALESCE(SUM(delivered_parcels),0) AS delivered, '
            .'COALESCE(SUM(redirected_parcels),0) AS redirected, '
            .'COALESCE(SUM(actual_km),0) AS actual_km',
        )->first();

        $ownerRelationship = static function (Builder $query, string $outer, int $carrierId): void {
            $query->selectRaw('1')->from('organization_relationships as rel')
                ->join('organizations as owner', 'owner.id', '=', 'rel.source_organization_id')
                ->whereColumn('rel.source_organization_id', $outer.'.owner_organization_id')
                ->where('rel.target_organization_id', $carrierId)
                ->where('rel.relationship_type', 'subcontracting')
                ->whereIn('rel.status', ['active', 'ended'])
                ->where('owner.type', Organization::TYPE_MASTER);
        };

        $statements = DB::table('financial_settlement_statements')
            ->where('recipient_type', 'organization')
            ->where('recipient_organization_id', $carrierId)
            ->whereIn('status', ['approved', 'closed'])
            ->whereExists(static function (Builder $query) use ($ownerRelationship, $carrierId): void {
                $ownerRelationship($query, 'financial_settlement_statements', $carrierId);
            })
            ->orderByDesc('period_until')->limit(100)
            ->get(['public_id', 'period_from', 'period_until', 'currency', 'status',
                'earning_amount_minor', 'deduction_amount_minor', 'net_balance_minor']);

        $documents = DB::table('billing_documents')
            ->where('counterparty_organization_id', $carrierId)
            ->where('document_type', 'external_carrier_settlement')
            ->whereIn('status', ['approved', 'closed'])
            ->whereExists(static function (Builder $query) use ($ownerRelationship, $carrierId): void {
                $ownerRelationship($query, 'billing_documents', $carrierId);
            })
            ->orderByDesc('period_until')->limit(100)
            ->get(['public_id', 'period_from', 'period_until', 'currency', 'status',
                'net_amount', 'vat_amount', 'gross_amount']);

        $fuel = DB::table('fuel_transactions')
            ->whereNotNull('actual_driver_id')
            ->whereExists(static function (Builder $query) use ($carrierId): void {
                $query->selectRaw('1')->from('driver_organization_assignments as a')
                    ->whereColumn('a.driver_id', 'fuel_transactions.actual_driver_id')
                    ->where('a.organization_id', $carrierId)
                    ->whereRaw('DATE(a.valid_from) <= DATE(fuel_transactions.occurred_at)')
                    ->where(static function (Builder $dates): void {
                        $dates->whereNull('a.valid_until')
                            ->orWhereRaw('DATE(a.valid_until) >= DATE(fuel_transactions.occurred_at)');
                    });
            })
            ->whereExists(static function (Builder $query) use ($ownerRelationship, $carrierId): void {
                $ownerRelationship($query, 'fuel_transactions', $carrierId);
            })
            ->orderByDesc('occurred_at')->limit(100)
            ->get(['public_id', 'occurred_at', 'provider', 'product_name',
                'quantity', 'unit_of_measure', 'gross_amount', 'currency']);

        return response()->json(['data' => [
            'statistics' => [
                'routes' => (int) ($stats->routes ?? 0),
                'delivered' => (int) ($stats->delivered ?? 0),
                'redirected' => (int) ($stats->redirected ?? 0),
                'actual_km' => (string) ($stats->actual_km ?? '0'),
            ],
            'settlements' => $statements,
            'billing_documents' => $documents,
            'fuel_transactions' => $fuel,
        ]]);
    }
}
