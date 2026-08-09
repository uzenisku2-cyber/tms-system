<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Controllers;

use App\Core\Http\BaseController;
use App\Models\User;
use App\Modules\DailyReports\Requests\UpdateDailyReportPerformancePolicyRequest;
use App\Modules\DailyReports\Services\DailyReportPerformancePolicyService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DailyReportPerformancePolicyController extends BaseController
{
    public function index(
        DailyReportPerformancePolicyService $policies,
    ): JsonResponse {
        return $this->success(
            $policies->configuration(),
        );
    }

    public function effective(
        Request $request,
        DailyReportPerformancePolicyService $policies,
    ): JsonResponse {
        $validated = $request->validate([
            'route_number' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        return $this->success(
            $policies->effective(
                $validated['route_number'],
            ),
        );
    }

    public function updateOrganization(
        UpdateDailyReportPerformancePolicyRequest $request,
        DailyReportPerformancePolicyService $policies,
    ): JsonResponse {
        return $this->success(
            $policies->updateOrganizationDefaults(
                $this->actor($request),
                $request->validated(),
            ),
        );
    }

    public function updateRoute(
        UpdateDailyReportPerformancePolicyRequest $request,
        string $routeNumber,
        DailyReportPerformancePolicyService $policies,
    ): JsonResponse {
        return $this->success(
            $policies->updateRouteOverride(
                $this->actor($request),
                $routeNumber,
                $request->validated(),
            ),
        );
    }

    public function deleteRoute(
        Request $request,
        string $routeNumber,
        DailyReportPerformancePolicyService $policies,
    ): JsonResponse {
        $this->actor($request);

        return $this->success(
            $policies->deleteRouteOverride(
                $routeNumber,
            ),
        );
    }

    private function actor(
        Request $request,
    ): User {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new AuthenticationException();
        }

        return $actor;
    }
}