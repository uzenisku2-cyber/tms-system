<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Controllers;

use App\Core\Http\BaseController;
use App\Modules\DailyReports\Requests\DepotImportReviewResolutionRequest;
use App\Modules\DailyReports\Services\DepotImportReviewResolutionService;
use Illuminate\Http\JsonResponse;
use LogicException;

final class DepotImportReviewResolutionController extends BaseController
{
    public function correctDriver(DepotImportReviewResolutionRequest $request, string $batch, string $row, DepotImportReviewResolutionService $service): JsonResponse
    {
        return $this->success($service->correctDriver($batch, $row, (int) $request->validated('driver_id'), (string) $request->validated('reason'), $this->actorId($request)));
    }

    public function ignoreZeroValue(DepotImportReviewResolutionRequest $request, string $batch, string $row, DepotImportReviewResolutionService $service): JsonResponse
    {
        return $this->success($service->ignoreZeroValue($batch, $row, (string) $request->validated('reason'), $this->actorId($request)));
    }

    public function revert(DepotImportReviewResolutionRequest $request, string $batch, string $row, DepotImportReviewResolutionService $service): JsonResponse
    {
        return $this->success($service->revert($batch, $row, (string) $request->validated('reason'), $this->actorId($request)));
    }

    private function actorId(DepotImportReviewResolutionRequest $request): int
    {
        $id = (int) $request->user()?->getAuthIdentifier();

        if ($id < 1) {
            throw new LogicException('The review-resolution actor must be persisted.');
        }

        return $id;
    }
}
