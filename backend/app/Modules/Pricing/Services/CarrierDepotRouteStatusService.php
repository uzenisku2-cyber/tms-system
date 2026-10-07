<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\DailyReports\Services\DepotDriverRecordReviewService;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Read-only, live operational comparison. No approval or financial status is inferred. */
final class CarrierDepotRouteStatusService
{
    public function __construct(private readonly DepotDriverRecordReviewService $reviews) {}

    /** @return array<string, mixed> */
    public function overview(int $carrierId): array
    {
        $relationships = DB::table('organization_relationships as rel')
            ->join('organizations as owner', 'owner.id', '=', 'rel.source_organization_id')
            ->where('rel.target_organization_id', $carrierId)
            ->where('rel.relationship_type', 'subcontracting')
            ->whereIn('rel.status', ['active', 'ended'])
            ->where('owner.type', Organization::TYPE_MASTER)
            ->get(['rel.source_organization_id', 'rel.valid_from', 'rel.valid_until']);
        $owners = $relationships->pluck('source_organization_id')->unique()->all();
        $reports = DB::table('daily_reports as r')
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
            })
            ->orderByDesc('r.service_date')
            ->get(['r.public_id', 'r.organization_id', 'r.performed_by_driver_id',
                'r.service_date', 'r.route_number', 'r.current_version']);
        $ownReports = $reports->keyBy('public_id');
        $assignments = DB::table('driver_organization_assignments')
            ->where('organization_id', $carrierId)
            ->get(['driver_id', 'valid_from', 'valid_until']);
        $comparisonByReport = [];
        $exceptions = [];
        if ($owners !== []) {
            $batches = DB::table('depot_import_batches')
                ->whereIn('organization_id', $owners)
                ->where('status', 'imported')
                ->get(['organization_id', 'public_id']);
            foreach ($batches as $batch) {
                $page = 1;
                do {
                    $result = $this->reviews->compareForOrganization(
                        (int) $batch->organization_id, (string) $batch->public_id,
                        ['page' => $page, 'per_page' => 100],
                    );
                    foreach ($result['items'] as $item) {
                        $depot = $item['depot_record'];
                        $date = (string) $depot['service_date'];
                        $driverId = $depot['effective_assigned_driver']['id'] ?? null;
                        $belongsToCarrier = $driverId !== null && $assignments->contains(
                            static fn ($assignment): bool => (int) $assignment->driver_id === (int) $driverId
                                && substr((string) $assignment->valid_from, 0, 10) <= $date
                                && ($assignment->valid_until === null
                                    || substr((string) $assignment->valid_until, 0, 10) >= $date),
                        );
                        if (! $belongsToCarrier) {
                            continue;
                        }
                        $reportId = $item['driver_record']['public_id'] ?? null;
                        if (is_string($reportId) && $ownReports->has($reportId)) {
                            $comparisonByReport[$reportId][] = [
                                'comparison_status' => $item['comparison_status'],
                                'difference_fields' => array_column($item['differences'], 'field'),
                                'depot_row_public_id' => $depot['row_public_id'],
                            ];
                        } else {
                            $exceptions[] = [
                                'service_date' => $date,
                                'route_number' => $depot['route_number'],
                                'comparison_status' => $item['comparison_status'],
                            ];
                        }
                    }
                    $page++;
                } while ($page <= (int) $result['pagination']['last_page']);
            }
        }
        $counts = [
            'awaiting_depot' => 0,
            'matched_pending_approval' => 0,
            'correction_required' => 0,
            'assignment_review' => 0,
            'manual_review' => 0,
        ];
        $rows = [];
        foreach ($reports as $report) {
            $comparisons = $comparisonByReport[(string) $report->public_id] ?? [];
            $comparison = count($comparisons) === 1 ? $comparisons[0] : null;
            $status = match (true) {
                count($comparisons) > 1 => 'manual_review',
                $comparison === null => 'awaiting_depot',
                $comparison['comparison_status'] === DepotDriverRecordReviewService::STATUS_MATCHING => 'matched_pending_approval',
                $comparison['comparison_status'] === DepotDriverRecordReviewService::STATUS_DIFFERENT => 'correction_required',
                $comparison['comparison_status'] === DepotDriverRecordReviewService::STATUS_DRIVER_MISMATCH => 'assignment_review',
                default => 'manual_review',
            };
            $counts[$status]++;
            $rows[] = [
                'report_public_id' => (string) $report->public_id,
                'report_version' => (int) $report->current_version,
                'service_date' => substr((string) $report->service_date, 0, 10),
                'route_number' => (string) $report->route_number,
                'status' => $status,
                'difference_fields' => $comparison['difference_fields'] ?? [],
            ];
        }

        return [
            'source' => 'live_depot_driver_comparison',
            'approval_recorded' => false,
            'summary' => $counts + ['depot_exceptions' => count($exceptions)],
            'routes' => $rows,
            'depot_exceptions' => $exceptions,
        ];
    }
}
