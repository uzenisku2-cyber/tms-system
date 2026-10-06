<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Modules\DailyReports\Models\DailyReport;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

/** Locks a carrier's imported draft once the current record matches a depot row. */
final class CarrierImportedReportDepotEditGuard
{
    public function __construct(private readonly DepotDriverRecordReviewService $reviews) {}

    public function assertEditable(DailyReport $report): void
    {
        $ownerId = (int) $report->getAttribute('organization_id');
        $date = CarbonImmutable::parse($report->getAttribute('service_date'))->toDateString();
        $batches = DB::table('depot_import_batches')
            ->where('organization_id', $ownerId)
            ->where('status', 'imported')
            ->whereDate('period_from', '<=', $date)
            ->whereDate('period_until', '>=', $date)
            ->get(['public_id']);
        foreach ($batches as $batch) {
            $page = 1;
            do {
                $comparison = $this->reviews->compareForOrganization(
                    $ownerId, (string) $batch->public_id,
                    [
                        'service_date_from' => $date,
                        'service_date_to' => $date,
                        'route_number' => (string) $report->getAttribute('route_number_normalized'),
                        'page' => $page,
                        'per_page' => 100,
                    ],
                );
                foreach ($comparison['items'] as $item) {
                    if (($item['driver_record']['public_id'] ?? null) !== (string) $report->getAttribute('public_id')) {
                        continue;
                    }
                    if ($item['comparison_status'] !== DepotDriverRecordReviewService::STATUS_DIFFERENT) {
                        throw new DomainException('The imported report is not editable while its depot comparison requires no driver correction.');
                    }
                }
                $page++;
            } while ($page <= (int) $comparison['pagination']['last_page']);
        }
    }
}
