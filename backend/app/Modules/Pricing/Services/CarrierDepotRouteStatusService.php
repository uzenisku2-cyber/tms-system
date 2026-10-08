<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\DailyReports\Services\DepotDriverRecordReviewService;
use App\Modules\Drivers\Models\Driver;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
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
            ->get(['r.id', 'r.public_id', 'r.organization_id', 'r.performed_by_driver_id',
                'r.service_date', 'r.route_number', 'r.current_version']);
        $assignments = DB::table('driver_organization_assignments')
            ->where('organization_id', $carrierId)
            ->get(['driver_id', 'valid_from', 'valid_until']);

        return $this->compareReports($reports, $owners, $assignments);
    }

    /** Only the authenticated driver's own reports and corresponding depot rows. */
    public function forDriver(int $userId, int $organizationId): array
    {
        $driverIds = Driver::query()->where('user_id', $userId)->pluck('id')->all();
        $type = Organization::query()->whereKey($organizationId)->value('type');
        abort_unless(in_array($type, [Organization::TYPE_MASTER, Organization::TYPE_SUBCONTRACTOR], true), 403);
        $query = DB::table('daily_reports as r')->whereNull('r.deleted_at')
            ->whereIn('r.performed_by_driver_id', $driverIds)
            ->whereExists(static function (Builder $q) use ($organizationId): void {
                $q->selectRaw('1')->from('driver_organization_assignments as a')
                    ->whereColumn('a.driver_id', 'r.performed_by_driver_id')
                    ->where('a.organization_id', $organizationId)
                    ->whereRaw('DATE(a.valid_from) <= r.service_date')
                    ->where(static function (Builder $dates): void {
                        $dates->whereNull('a.valid_until')->orWhereRaw('DATE(a.valid_until) >= r.service_date');
                    });
            });
        if ($type === Organization::TYPE_MASTER) {
            $query->where('r.organization_id', $organizationId);
            $owners = [$organizationId];
        } else {
            $query->whereExists(static function (Builder $q) use ($organizationId): void {
                $q->selectRaw('1')->from('organization_relationships as rel')
                    ->whereColumn('rel.source_organization_id', 'r.organization_id')
                    ->where('rel.target_organization_id', $organizationId)
                    ->where('rel.relationship_type', 'subcontracting')
                    ->whereIn('rel.status', ['active', 'ended'])
                    ->whereRaw('DATE(rel.valid_from) <= r.service_date')
                    ->where(static function (Builder $dates): void {
                        $dates->whereNull('rel.valid_until')->orWhereRaw('DATE(rel.valid_until) >= r.service_date');
                    });
            });
            $owners = DB::table('organization_relationships as rel')
                ->join('organizations as owner', 'owner.id', '=', 'rel.source_organization_id')
                ->where('rel.target_organization_id', $organizationId)
                ->where('rel.relationship_type', 'subcontracting')
                ->whereIn('rel.status', ['active', 'ended'])
                ->where('owner.type', Organization::TYPE_MASTER)
                ->pluck('rel.source_organization_id')->unique()->all();
        }
        $reports = $query->orderByDesc('r.service_date')->get([
            'r.id', 'r.public_id', 'r.organization_id', 'r.performed_by_driver_id',
            'r.service_date', 'r.route_number', 'r.current_version',
        ]);
        $assignments = DB::table('driver_organization_assignments')
            ->where('organization_id', $organizationId)->whereIn('driver_id', $driverIds)
            ->get(['driver_id', 'valid_from', 'valid_until']);

        return $this->compareReports($reports, $owners, $assignments, false);
    }

    /** @param Collection<int, \stdClass> $reports
     * @param  array<int, int>  $owners
     * @param  Collection<int, \stdClass>  $assignments
     * @return array<string, mixed>
     */
    private function compareReports($reports, array $owners, $assignments, bool $includeExceptions = true): array
    {
        $ownReports = $reports->keyBy('public_id');
        $approvals = DB::table('depot_route_approvals as approval')
            ->join('depot_import_rows as depot', 'depot.id', '=', 'approval.depot_import_row_id')
            ->whereIn('approval.daily_report_id', $reports->pluck('id')->all())
            ->get(['approval.daily_report_id', 'approval.daily_report_version',
                'approval.depot_values_sha256', 'depot.public_id as depot_row_public_id']);
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
                        $reportId = $item['driver_record']['public_id'] ?? null;
                        $ownedReport = is_string($reportId) && $ownReports->has($reportId);
                        if (! $belongsToCarrier && ! $ownedReport) {
                            continue;
                        }
                        if ($ownedReport) {
                            $comparisonByReport[$reportId][] = [
                                'comparison_status' => $item['comparison_status'],
                                'difference_fields' => array_column($item['differences'], 'field'),
                                'depot_row_public_id' => $depot['row_public_id'],
                                'depot_values_sha256' => $depot['protected_values_sha256'],
                                'depot_values' => $depot['values'],
                                'driver_values' => $item['driver_record']['values'] ?? [],
                            ];
                        } elseif ($includeExceptions) {
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
            'approved' => 0,
            'correction_required' => 0,
            'assignment_review' => 0,
            'manual_review' => 0,
        ];
        $rows = [];
        foreach ($reports as $report) {
            $comparisons = $comparisonByReport[(string) $report->public_id] ?? [];
            $comparison = count($comparisons) === 1 ? $comparisons[0] : null;
            $approved = $comparison !== null
                && $comparison['comparison_status'] === DepotDriverRecordReviewService::STATUS_MATCHING
                && $approvals->contains(static fn ($item): bool => (int) $item->daily_report_id === (int) $report->id
                    && (int) $item->daily_report_version === (int) $report->current_version
                    && (string) $item->depot_row_public_id === (string) $comparison['depot_row_public_id']
                    && hash_equals((string) $item->depot_values_sha256, (string) $comparison['depot_values_sha256'])
                );
            $status = match (true) {
                count($comparisons) > 1 => 'manual_review',
                $comparison === null => 'awaiting_depot',
                $approved => 'approved',
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
                'depot_values' => $comparison['depot_values'] ?? null,
                'driver_values' => $comparison['driver_values'] ?? null,
            ];
        }

        return [
            'source' => 'live_depot_driver_comparison',
            'approval_recorded' => $counts['approved'] > 0,
            'summary' => $counts + ['depot_exceptions' => count($exceptions)],
            'routes' => $rows,
            'depot_exceptions' => $exceptions,
        ];
    }
}
