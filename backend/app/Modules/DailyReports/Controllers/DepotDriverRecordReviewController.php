<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Controllers;

use App\Core\Http\BaseController;
use App\Core\Organizations\OrganizationContext;
use App\Modules\DailyReports\Requests\DepotDriverRecordReviewRequest;
use App\Modules\DailyReports\Services\DepotDriverRecordReviewService;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Http\JsonResponse;

final class DepotDriverRecordReviewController extends BaseController
{
    public function show(
        DepotDriverRecordReviewRequest $request,
        string $batch,
        DepotDriverRecordReviewService $reviews,
        OrganizationContext $context,
    ): JsonResponse {
        abort_unless(Organization::query()->whereKey($context->requireId())
            ->where('type', Organization::TYPE_MASTER)->exists(), 403);

        return $this->success(
            $reviews->compare(
                $batch,
                $request->validated(),
            ),
        );
    }
}
