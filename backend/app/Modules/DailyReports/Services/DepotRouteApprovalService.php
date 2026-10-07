<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Core\Organizations\OrganizationContext;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Models\DepotImportBatch;
use App\Modules\DailyReports\Models\DepotImportRow;
use App\Modules\DailyReports\Models\DepotRouteApproval;
use App\Modules\Organizations\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Approval of operational agreement only; this does not approve money or create an invoice. */
final class DepotRouteApprovalService
{
    public function __construct(
        private readonly OrganizationContext $context,
        private readonly DepotDriverRecordReviewService $reviews,
    ) {}

    /** @return array<string, mixed> */
    public function approve(string $batchId, string $rowId, string $reportId, int $version, int $actorId, string $reason): array
    {
        $organizationId = $this->context->requireId();
        if (! Organization::query()->whereKey($organizationId)->where('type', Organization::TYPE_MASTER)->exists()) {
            abort(403);
        }

        return DB::transaction(function () use ($batchId, $rowId, $reportId, $version, $actorId, $reason, $organizationId): array {
            $batch = DepotImportBatch::query()->where('organization_id', $organizationId)
                ->where('status', DepotImportBatch::STATUS_IMPORTED)->where('public_id', $batchId)
                ->lockForUpdate()->firstOrFail();
            $row = DepotImportRow::query()->where('depot_import_batch_id', $batch->getKey())
                ->where('status', DepotImportRow::STATUS_READY)->where('public_id', $rowId)
                ->lockForUpdate()->firstOrFail();
            $report = DailyReport::query()->where('organization_id', $organizationId)
                ->where('public_id', $reportId)->lockForUpdate()->firstOrFail();
            if ((int) $report->getAttribute('current_version') !== $version) {
                $this->invalid('expected_report_version', 'The driver report changed. Compare its current version again.');
            }
            $date = CarbonImmutable::parse($row->getAttribute('service_date'))->toDateString();
            $sourceCount = DB::table('depot_import_rows as source')
                ->join('depot_import_batches as source_batch', 'source_batch.id', '=', 'source.depot_import_batch_id')
                ->where('source_batch.organization_id', $organizationId)
                ->where('source_batch.status', DepotImportBatch::STATUS_IMPORTED)
                ->where('source.status', DepotImportRow::STATUS_READY)
                ->whereDate('source.service_date', $date)
                ->where('source.route_number_normalized', $row->getAttribute('route_number_normalized'))
                ->count();
            if ($sourceCount !== 1) {
                $this->invalid('depot_row', 'The route has multiple depot sources and requires manual review.');
            }
            $comparison = $this->reviews->compareForOrganization($organizationId, $batchId, [
                'service_date_from' => $date,
                'service_date_to' => $date,
                'route_number' => (string) $row->getAttribute('route_number'),
                'per_page' => 100,
            ]);
            $matches = array_values(array_filter($comparison['items'], static fn (array $item): bool => ($item['depot_record']['row_public_id'] ?? null) === $rowId
            ));
            if (count($matches) !== 1
                || $matches[0]['comparison_status'] !== DepotDriverRecordReviewService::STATUS_MATCHING
                || ($matches[0]['driver_record']['public_id'] ?? null) !== $reportId) {
                $this->invalid('depot_row', 'The current driver report and depot row must agree before approval.');
            }
            $hash = (string) $row->getAttribute('protected_values_sha256');
            $existing = DepotRouteApproval::query()->where('daily_report_id', $report->getKey())
                ->where('daily_report_version', $version)->where('depot_import_row_id', $row->getKey())->first();
            if ($existing instanceof DepotRouteApproval) {
                if (! hash_equals((string) $existing->getAttribute('depot_values_sha256'), $hash)) {
                    $this->invalid('depot_row', 'The depot source changed after the earlier approval.');
                }

                return $this->payload($existing, true);
            }
            $approval = DepotRouteApproval::query()->create([
                'organization_id' => $organizationId,
                'daily_report_id' => $report->getKey(),
                'daily_report_version' => $version,
                'depot_import_batch_id' => $batch->getKey(),
                'depot_import_row_id' => $row->getKey(),
                'depot_values_sha256' => $hash,
                'approved_by_user_id' => $actorId,
                'reason' => trim($reason),
                'approved_at' => now(),
            ]);

            return $this->payload($approval, false);
        }, 3);
    }

    /** @return array<string, mixed> */
    private function payload(DepotRouteApproval $approval, bool $replayed): array
    {
        return [
            'public_id' => (string) $approval->getAttribute('public_id'),
            'report_version' => (int) $approval->getAttribute('daily_report_version'),
            'depot_values_sha256' => (string) $approval->getAttribute('depot_values_sha256'),
            'approved_by_user_id' => (int) $approval->getAttribute('approved_by_user_id'),
            'approved_at' => $approval->getAttribute('approved_at'),
            'replayed' => $replayed,
        ];
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }
}
