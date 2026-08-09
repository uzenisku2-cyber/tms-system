<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Controllers;

use App\Core\Http\BaseController;
use App\Models\User;
use App\Modules\DailyReports\Models\DailyReport;
use App\Modules\DailyReports\Requests\DailyReportIndexRequest;
use App\Modules\DailyReports\Requests\DailyReportTransitionRequest;
use App\Modules\DailyReports\Requests\RecordDailyReportCorrectionRequest;
use App\Modules\DailyReports\Requests\StoreDailyReportRequest;
use App\Modules\DailyReports\Requests\UpdateDailyReportRequest;
use App\Modules\DailyReports\Resources\DailyReportEventResource;
use App\Modules\DailyReports\Resources\DailyReportResource;
use App\Modules\DailyReports\Resources\DailyReportVersionResource;
use App\Modules\DailyReports\Services\DailyReportQueryService;
use App\Modules\DailyReports\Services\DailyReportWriteService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DailyReportController extends BaseController
{
    public function index(
        DailyReportIndexRequest $request,
        DailyReportQueryService $queries,
    ): JsonResponse {
        $filters = $request->validated();

        $reports = $queries->paginate(
            $filters,
        );

        return $this->success([
            'items' => DailyReportResource::collection(
                $reports,
            ),
            'pagination' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ],
            'navigation' => $queries->navigation(
                $filters,
            ),
        ]);
    }

    public function store(
        StoreDailyReportRequest $request,
        DailyReportWriteService $writes,
    ): JsonResponse {
        return $this->success(
            new DailyReportResource(
                $writes->create(
                    $this->actor($request),
                    $request->validated(),
                ),
            ),
            'Daily report created.',
            201,
        );
    }

    public function show(
        string $dailyReport,
        DailyReportQueryService $queries,
    ): JsonResponse {
        return $this->success(
            new DailyReportResource(
                $queries->findByPublicId(
                    $dailyReport,
                ),
            ),
        );
    }

    public function update(
        UpdateDailyReportRequest $request,
        string $dailyReport,
        DailyReportWriteService $writes,
    ): JsonResponse {
        return $this->success(
            new DailyReportResource(
                $writes->update(
                    $this->actor($request),
                    $dailyReport,
                    $request->validated(),
                ),
            ),
            'Daily report updated.',
        );
    }

    public function submit(
        DailyReportTransitionRequest $request,
        string $dailyReport,
        DailyReportWriteService $writes,
    ): JsonResponse {
        return $this->transitionResponse(
            $writes->submit(
                $this->actor($request),
                $dailyReport,
                $request->validated(),
            ),
            'Daily report submitted.',
        );
    }

    public function review(
        DailyReportTransitionRequest $request,
        string $dailyReport,
        DailyReportWriteService $writes,
    ): JsonResponse {
        return $this->transitionResponse(
            $writes->startReview(
                $this->actor($request),
                $dailyReport,
                $request->validated(),
            ),
            'Daily report review started.',
        );
    }

    public function requestCorrection(
        DailyReportTransitionRequest $request,
        string $dailyReport,
        DailyReportWriteService $writes,
    ): JsonResponse {
        return $this->transitionResponse(
            $writes->requestCorrection(
                $this->actor($request),
                $dailyReport,
                $request->validated(),
            ),
            'Daily report correction requested.',
        );
    }

    public function correct(
        RecordDailyReportCorrectionRequest $request,
        string $dailyReport,
        DailyReportWriteService $writes,
    ): JsonResponse {
        return $this->transitionResponse(
            $writes->recordCorrection(
                $this->actor($request),
                $dailyReport,
                $request->validated(),
            ),
            'Daily report correction recorded.',
        );
    }

    public function resubmit(
        DailyReportTransitionRequest $request,
        string $dailyReport,
        DailyReportWriteService $writes,
    ): JsonResponse {
        return $this->transitionResponse(
            $writes->resubmit(
                $this->actor($request),
                $dailyReport,
                $request->validated(),
            ),
            'Daily report resubmitted.',
        );
    }

    public function approve(
        DailyReportTransitionRequest $request,
        string $dailyReport,
        DailyReportWriteService $writes,
    ): JsonResponse {
        return $this->transitionResponse(
            $writes->approve(
                $this->actor($request),
                $dailyReport,
                $request->validated(),
            ),
            'Daily report approved.',
        );
    }

    public function close(
        DailyReportTransitionRequest $request,
        string $dailyReport,
        DailyReportWriteService $writes,
    ): JsonResponse {
        return $this->transitionResponse(
            $writes->close(
                $this->actor($request),
                $dailyReport,
                $request->validated(),
            ),
            'Daily report closed.',
        );
    }

    public function versions(
        string $dailyReport,
        DailyReportQueryService $queries,
    ): JsonResponse {
        return $this->success([
            'items' => DailyReportVersionResource::collection(
                $queries->versions($dailyReport),
            ),
        ]);
    }

    public function events(
        string $dailyReport,
        DailyReportQueryService $queries,
    ): JsonResponse {
        return $this->success([
            'items' => DailyReportEventResource::collection(
                $queries->events($dailyReport),
            ),
        ]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return $actor;
    }

    private function transitionResponse(
        DailyReport $dailyReport,
        string $message,
    ): JsonResponse {
        return $this->success(
            new DailyReportResource($dailyReport),
            $message,
        );
    }
}
