<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Controllers;

use App\Core\Http\BaseController;
use App\Modules\DailyReports\Requests\DepotDriverRecordReviewRequest;
use App\Modules\DailyReports\Services\DepotDriverRecordReviewService;
use Illuminate\Http\JsonResponse;

final class DepotDriverRecordReviewController extends BaseController
{
    public function show(
        DepotDriverRecordReviewRequest $request,
        string $batch,
        DepotDriverRecordReviewService $reviews,
    ): JsonResponse {
        return $this->success(
            $reviews->compare(
                $batch,
                $request->validated(),
            ),
        );
    }
}
