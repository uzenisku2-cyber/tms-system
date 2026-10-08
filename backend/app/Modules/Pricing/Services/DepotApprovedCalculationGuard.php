<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Services;

use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DepotImportBatch;
use App\Modules\DailyReports\Models\DepotImportRow;
use App\Modules\DailyReports\Models\DepotRouteApproval;
use App\Modules\DailyReports\Services\DepotDriverRecordReviewService;
use App\Modules\Pricing\Models\FinancialCalculation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class DepotApprovedCalculationGuard
{
    public function __construct(private readonly DepotDriverRecordReviewService $reviews) {}

    public function assertApproved(FinancialCalculation $calculation): void
    {
        $report = DailyReport::query()->whereKey($calculation->daily_report_id)
            ->whereNull('deleted_at')->lockForUpdate()->first();
        if (! $report instanceof DailyReport ||
            (int) $report->current_version !== (int) $calculation->daily_report_version) {
            $this->blocked('The calculation must use the current driver report version.');
        }
        $approvals = DepotRouteApproval::query()
            ->where('daily_report_id', $report->id)
            ->where('daily_report_version', $report->current_version)
            ->where('organization_id', $report->organization_id)->get();
        if ($approvals->count() !== 1) {
            $this->blocked('Exactly one current depot route approval is required.');
        }
        $approval = $approvals->first();
        if (! $approval instanceof DepotRouteApproval) {
            $this->blocked('The approved depot evidence is unavailable.');
        }
        $batch = DepotImportBatch::query()->whereKey($approval->depot_import_batch_id)
            ->where('organization_id', $report->organization_id)
            ->where('status', DepotImportBatch::STATUS_IMPORTED)->first();
        $row = DepotImportRow::query()->whereKey($approval->depot_import_row_id)
            ->where('depot_import_batch_id', $approval->depot_import_batch_id)
            ->where('status', DepotImportRow::STATUS_READY)->first();
        if (! $batch instanceof DepotImportBatch || ! $row instanceof DepotImportRow ||
            ! hash_equals((string) $approval->depot_values_sha256, (string) $row->protected_values_sha256)) {
            $this->blocked('The approved depot evidence has changed.');
        }
        $date = CarbonImmutable::parse($row->service_date)->toDateString();
        $sourceCount = DB::table('depot_import_rows as source')
            ->join('depot_import_batches as source_batch', 'source_batch.id', '=', 'source.depot_import_batch_id')
            ->where('source_batch.organization_id', $report->organization_id)
            ->where('source_batch.status', DepotImportBatch::STATUS_IMPORTED)
            ->where('source.status', DepotImportRow::STATUS_READY)
            ->whereDate('source.service_date', $date)
            ->where('source.route_number_normalized', $row->route_number_normalized)->count();
        if ($sourceCount !== 1) {
            $this->blocked('The depot route has ambiguous source records.');
        }
        $comparison = $this->reviews->compareForOrganization((int) $report->organization_id, (string) $batch->public_id, [
            'service_date_from' => $date, 'service_date_to' => $date,
            'route_number' => (string) $row->route_number, 'per_page' => 100,
        ]);
        $matching = array_values(array_filter($comparison['items'], static fn (array $item): bool => ($item['depot_record']['row_public_id'] ?? null) === $row->public_id &&
            ($item['driver_record']['public_id'] ?? null) === $report->public_id &&
            ($item['comparison_status'] ?? null) === DepotDriverRecordReviewService::STATUS_MATCHING
        ));
        if (count($matching) !== 1) {
            $this->blocked('The current driver report no longer agrees with the approved depot route.');
        }
    }

    private function blocked(string $message): never
    {
        throw ValidationException::withMessages(['depot_route_approval' => [$message]]);
    }
}
