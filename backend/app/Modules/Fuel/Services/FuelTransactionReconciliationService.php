<?php

declare(strict_types=1);

namespace App\Modules\Fuel\Services;

use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\Drivers\Models\DriverOrganizationAssignment;
use App\Modules\Drivers\Services\DriverSupervisoryAuthorizationService;
use App\Modules\Fuel\Models\FuelTransaction;
use App\Modules\Fuel\Models\FuelTransactionReconciliation;
use App\Modules\Fuel\Models\FuelTransactionReconciliationDecision;
use App\Modules\Fuel\Models\FuelTransactionReconciliationEvaluation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class FuelTransactionReconciliationService
{
    private const EVALUATION_VERSION = 'v1';

    private const PERMISSION = 'compensation.manage';

    private const OPERATIONAL_STATUSES = ['submitted', 'under_review', 'correction_requested', 'corrected', 'approved', 'closed'];

    public function __construct(private readonly DriverSupervisoryAuthorizationService $authorization) {}

    public function show(FuelTransaction $transaction, int $organizationId): array
    {
        $this->assertVisible($transaction, $organizationId);
        $reconciliation = FuelTransactionReconciliation::query()->where('fuel_transaction_id', $transaction->getKey())->first();
        if (! $reconciliation instanceof FuelTransactionReconciliation) {
            return ['status' => FuelTransactionReconciliation::STATUS_PENDING, 'result_code' => null, 'revision' => 0, 'evaluations' => [], 'decisions' => []];
        }

        return $this->payload($reconciliation);
    }

    public function evaluate(FuelTransaction $transaction, int $organizationId, User $actor, int $expectedRevision): array
    {
        $this->assertVisible($transaction, $organizationId);

        return DB::transaction(function () use ($transaction, $organizationId, $actor, $expectedRevision): array {
            $lockedTransaction = FuelTransaction::query()->whereKey($transaction->getKey())->where('owner_organization_id', $organizationId)->lockForUpdate()->firstOrFail();
            $serviceDate = $lockedTransaction->occurred_at->toDateString();
            FuelTransactionReconciliation::query()->firstOrCreate(
                ['fuel_transaction_id' => $lockedTransaction->getKey()],
                ['public_id' => (string) Str::uuid(), 'owner_organization_id' => $organizationId, 'status' => FuelTransactionReconciliation::STATUS_PENDING, 'service_date' => $serviceDate, 'revision' => 0],
            );
            $reconciliation = FuelTransactionReconciliation::query()->where('fuel_transaction_id', $lockedTransaction->getKey())->lockForUpdate()->firstOrFail();
            if ((int) $reconciliation->revision !== $expectedRevision) {
                throw ValidationException::withMessages(['expected_revision' => ['The reconciliation revision is stale.']]);
            }
            if ($reconciliation->status === FuelTransactionReconciliation::STATUS_RESOLVED) {
                throw ValidationException::withMessages(['reconciliation' => ['A manual reconciliation decision must not be overwritten by automatic evaluation.']]);
            }

            $result = $this->evaluateTransaction($lockedTransaction, $organizationId, $actor, $serviceDate);
            $nextRevision = $expectedRevision + 1;
            $evaluatedAt = now();
            FuelTransactionReconciliationEvaluation::query()->create([
                'public_id' => (string) Str::uuid(), 'fuel_transaction_reconciliation_id' => $reconciliation->getKey(), 'revision' => $nextRevision,
                'evaluation_version' => self::EVALUATION_VERSION, 'result_code' => $result['result_code'], 'effective_driver_id' => $result['effective_driver_id'],
                'driver_organization_assignment_id' => $result['assignment_id'], 'candidate_count' => $result['candidate_count'],
                'matched_daily_report_id' => $result['matched_daily_report_id'], 'evidence' => $result['evidence'],
                'evaluated_by_user_id' => $actor->getAuthIdentifier(), 'evaluated_at' => $evaluatedAt,
            ]);
            $reconciliation->forceFill([
                'status' => $result['status'], 'result_code' => $result['result_code'], 'effective_driver_id' => $result['effective_driver_id'],
                'driver_organization_assignment_id' => $result['assignment_id'], 'candidate_count' => $result['candidate_count'],
                'matched_daily_report_id' => $result['matched_daily_report_id'], 'revision' => $nextRevision, 'evaluated_at' => $evaluatedAt, 'resolved_at' => null,
            ])->save();

            return $this->payload($reconciliation->refresh());
        });
    }

    public function decide(FuelTransaction $transaction, int $organizationId, User $actor, int $expectedRevision, string $decisionCode, ?int $dailyReportId, string $reason): array
    {
        $this->assertVisible($transaction, $organizationId);

        return DB::transaction(function () use ($transaction, $organizationId, $actor, $expectedRevision, $decisionCode, $dailyReportId, $reason): array {
            $reconciliation = FuelTransactionReconciliation::query()->where('fuel_transaction_id', $transaction->getKey())->where('owner_organization_id', $organizationId)->lockForUpdate()->firstOrFail();
            if ((int) $reconciliation->revision !== $expectedRevision) {
                throw ValidationException::withMessages(['expected_revision' => ['The reconciliation revision is stale.']]);
            }
            if ($reconciliation->status === FuelTransactionReconciliation::STATUS_RESOLVED && $decisionCode !== 'return_to_review') {
                throw ValidationException::withMessages(['reconciliation' => ['The reconciliation is already resolved.']]);
            }

            $selectedReport = null;
            if ($decisionCode === 'select_daily_report') {
                $selectedReport = DailyReport::query()->whereKey($dailyReportId)->where('performed_by_driver_id', $reconciliation->effective_driver_id)->whereDate('service_date', $reconciliation->service_date)->first();
                if (! $selectedReport instanceof DailyReport || (int) $selectedReport->organization_id !== (int) $reconciliation->driverOrganizationAssignment?->organization_id) {
                    throw ValidationException::withMessages(['daily_report_id' => ['The daily report is not an eligible reconciliation candidate.']]);
                }
            }
            if ($decisionCode === 'confirm_driver_day' && (int) $reconciliation->candidate_count < 1) {
                throw ValidationException::withMessages(['decision_code' => ['Driver-day confirmation requires operational activity.']]);
            }

            $previousStatus = (string) $reconciliation->status;
            $newStatus = $decisionCode === 'return_to_review' ? FuelTransactionReconciliation::STATUS_REVIEW_REQUIRED : FuelTransactionReconciliation::STATUS_RESOLVED;
            $nextRevision = $expectedRevision + 1;
            $decidedAt = now();
            FuelTransactionReconciliationDecision::query()->create([
                'public_id' => (string) Str::uuid(), 'fuel_transaction_reconciliation_id' => $reconciliation->getKey(), 'revision' => $nextRevision,
                'previous_status' => $previousStatus, 'new_status' => $newStatus, 'decision_code' => $decisionCode,
                'selected_daily_report_id' => $selectedReport?->getKey(), 'reason' => $reason,
                'decided_by_user_id' => $actor->getAuthIdentifier(), 'decided_at' => $decidedAt,
            ]);
            $reconciliation->forceFill([
                'status' => $newStatus, 'result_code' => 'manual_'.$decisionCode,
                'matched_daily_report_id' => $selectedReport?->getKey() ?? $reconciliation->matched_daily_report_id,
                'revision' => $nextRevision, 'resolved_at' => $newStatus === FuelTransactionReconciliation::STATUS_RESOLVED ? $decidedAt : null,
            ])->save();

            return $this->payload($reconciliation->refresh());
        });
    }

    private function evaluateTransaction(FuelTransaction $transaction, int $organizationId, User $actor, string $serviceDate): array
    {
        $effectiveDriverId = $transaction->effectiveDriverId();
        $base = ['effective_driver_id' => $effectiveDriverId, 'assignment_id' => null, 'candidate_count' => 0, 'matched_daily_report_id' => null, 'evidence' => ['service_date' => $serviceDate, 'transaction_match_status' => $transaction->match_status]];
        if ($transaction->match_status === 'review') {
            return [...$base, 'status' => FuelTransactionReconciliation::STATUS_REVIEW_REQUIRED, 'result_code' => 'import_requires_review'];
        }
        if ($effectiveDriverId === null) {
            return [...$base, 'status' => FuelTransactionReconciliation::STATUS_REVIEW_REQUIRED, 'result_code' => 'missing_effective_driver'];
        }

        $moment = Carbon::parse($serviceDate)->endOfDay();
        $visibleIds = $this->authorization->visibleDriverOrganizationAssignmentIds($actor, $organizationId, self::PERMISSION, $moment);
        $assignmentQuery = DriverOrganizationAssignment::query()->whereIn('id', $visibleIds)->where('driver_id', $effectiveDriverId)->whereDate('valid_from', '<=', $serviceDate)
            ->where(fn (Builder $query) => $query->whereNull('valid_until')->orWhereDate('valid_until', '>=', $serviceDate));
        if ($transaction->actual_driver_id !== null && $transaction->actual_driver_organization_assignment_id !== null) {
            $assignmentQuery->whereKey($transaction->actual_driver_organization_assignment_id);
        }
        $assignment = $assignmentQuery->orderByDesc('valid_from')->first();
        if (! $assignment instanceof DriverOrganizationAssignment) {
            return [...$base, 'status' => FuelTransactionReconciliation::STATUS_REVIEW_REQUIRED, 'result_code' => 'missing_driver_organization_assignment'];
        }

        $candidates = DailyReport::query()->where('organization_id', $assignment->organization_id)->where('performed_by_driver_id', $effectiveDriverId)
            ->whereDate('service_date', $serviceDate)->whereIn('status', self::OPERATIONAL_STATUSES)->orderBy('id')->get(['id', 'public_id', 'route_number', 'status', 'vehicle_id']);
        $candidateCount = $candidates->count();
        $evidence = ['service_date' => $serviceDate, 'transaction_match_status' => $transaction->match_status, 'assignment_organization_id' => (int) $assignment->organization_id, 'transaction_vehicle_id' => $transaction->vehicle_id, 'candidates' => $candidates->map(fn (DailyReport $report): array => ['id' => (int) $report->getKey(), 'public_id' => $report->public_id, 'route_number' => $report->route_number, 'status' => $report->status, 'vehicle_id' => $report->vehicle_id === null ? null : (int) $report->vehicle_id])->all()];
        $base = [...$base, 'assignment_id' => (int) $assignment->getKey(), 'candidate_count' => $candidateCount, 'evidence' => $evidence];
        if ($candidateCount === 0) {
            return [...$base, 'status' => FuelTransactionReconciliation::STATUS_REVIEW_REQUIRED, 'result_code' => 'no_operational_activity'];
        }
        if ($transaction->vehicle_id === null) {
            return [...$base, 'status' => FuelTransactionReconciliation::STATUS_MATCHED, 'result_code' => 'driver_day_matched'];
        }

        $vehicleMatches = $candidates->where('vehicle_id', (int) $transaction->vehicle_id)->values();
        if ($vehicleMatches->count() === 1) {
            return [...$base, 'status' => FuelTransactionReconciliation::STATUS_MATCHED, 'result_code' => 'vehicle_matched', 'matched_daily_report_id' => (int) $vehicleMatches->first()->getKey()];
        }
        if ($vehicleMatches->count() > 1) {
            return [...$base, 'status' => FuelTransactionReconciliation::STATUS_MATCHED, 'result_code' => 'driver_day_matched'];
        }
        if ($candidates->contains(fn (DailyReport $report): bool => $report->vehicle_id === null)) {
            return [...$base, 'status' => FuelTransactionReconciliation::STATUS_REVIEW_REQUIRED, 'result_code' => 'vehicle_unconfirmed'];
        }

        return [...$base, 'status' => FuelTransactionReconciliation::STATUS_REVIEW_REQUIRED, 'result_code' => 'vehicle_mismatch'];
    }

    private function assertVisible(FuelTransaction $transaction, int $organizationId): void
    {
        abort_unless((int) $transaction->owner_organization_id === $organizationId, 404);
    }

    private function payload(FuelTransactionReconciliation $reconciliation): array
    {
        $reconciliation->load(['evaluations', 'decisions']);

        return [
            'public_id' => $reconciliation->public_id, 'status' => $reconciliation->status, 'result_code' => $reconciliation->result_code,
            'effective_driver_id' => $reconciliation->effective_driver_id === null ? null : (int) $reconciliation->effective_driver_id,
            'service_date' => Carbon::parse((string) $reconciliation->service_date)->toDateString(), 'candidate_count' => (int) $reconciliation->candidate_count,
            'matched_daily_report_id' => $reconciliation->matched_daily_report_id === null ? null : (int) $reconciliation->matched_daily_report_id,
            'revision' => (int) $reconciliation->revision,
            'evaluations' => $reconciliation->evaluations->map(fn ($evaluation): array => ['revision' => (int) $evaluation->revision, 'result_code' => $evaluation->result_code, 'evidence' => $evaluation->evidence, 'evaluated_at' => $evaluation->evaluated_at === null ? null : Carbon::parse((string) $evaluation->evaluated_at)->toAtomString()])->all(),
            'decisions' => $reconciliation->decisions->map(fn ($decision): array => ['revision' => (int) $decision->revision, 'decision_code' => $decision->decision_code, 'reason' => $decision->reason, 'selected_daily_report_id' => $decision->selected_daily_report_id === null ? null : (int) $decision->selected_daily_report_id, 'decided_at' => $decision->decided_at === null ? null : Carbon::parse((string) $decision->decided_at)->toAtomString()])->all(),
        ];
    }
}
