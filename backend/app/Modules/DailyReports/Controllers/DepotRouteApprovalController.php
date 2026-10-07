<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Controllers;

use App\Modules\DailyReports\Requests\ApproveDepotRouteRequest;
use App\Modules\DailyReports\Services\DepotRouteApprovalService;
use Illuminate\Http\JsonResponse;

final class DepotRouteApprovalController
{
    public function store(ApproveDepotRouteRequest $request, string $batch, string $row, DepotRouteApprovalService $service): JsonResponse
    {
        $data = $request->validated();

        return response()->json(['data' => $service->approve(
            $batch, $row, (string) $data['daily_report_public_id'],
            (int) $data['expected_report_version'],
            (int) $request->user()?->getAuthIdentifier(), (string) $data['reason'],
        )]);
    }
}
