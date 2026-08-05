<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Core\Http\BaseController;
use App\Models\User;
use App\Modules\Pricing\Requests\ApproveFinancialCalculationRequest;
use App\Modules\Pricing\Requests\CloseFinancialCalculationRequest;
use App\Modules\Pricing\Requests\FinancialCalculationIndexRequest;
use App\Modules\Pricing\Requests\ReviewFinancialCalculationRequest;
use App\Modules\Pricing\Requests\StoreFinancialCalculationRequest;
use App\Modules\Pricing\Resources\FinancialCalculationEventResource;
use App\Modules\Pricing\Resources\FinancialCalculationResource;
use App\Modules\Pricing\Services\FinancialCalculationQueryService;
use App\Modules\Pricing\Services\FinancialCalculationWriteService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FinancialCalculationController extends BaseController
{
    public function store(
        StoreFinancialCalculationRequest $request,
        FinancialCalculationWriteService $writes,
    ): JsonResponse {
        return $this->success(
            new FinancialCalculationResource(
                $writes->createInitial(
                    $this->actor($request),
                    $request->validated(),
                ),
            ),
            'Financial calculation created.',
            201,
        );
    }

    public function review(
        ReviewFinancialCalculationRequest $request,
        string $financialCalculation,
        FinancialCalculationWriteService $writes,
    ): JsonResponse {
        return $this->success(
            new FinancialCalculationResource(
                $writes->startReview(
                    $this->actor($request),
                    $financialCalculation,
                    $request->validated(),
                ),
            ),
            'Financial calculation review started.',
        );
    }

    public function approve(
        ApproveFinancialCalculationRequest $request,
        string $financialCalculation,
        FinancialCalculationWriteService $writes,
    ): JsonResponse {
        return $this->success(
            new FinancialCalculationResource(
                $writes->approve(
                    $this->actor($request),
                    $financialCalculation,
                    $request->validated(),
                ),
            ),
            'Financial calculation approved.',
        );
    }

    public function close(
        CloseFinancialCalculationRequest $request,
        string $financialCalculation,
        FinancialCalculationWriteService $writes,
    ): JsonResponse {
        return $this->success(
            new FinancialCalculationResource(
                $writes->close(
                    $this->actor($request),
                    $financialCalculation,
                    $request->validated(),
                ),
            ),
            'Financial calculation closed.',
        );
    }

    public function index(
        FinancialCalculationIndexRequest $request,
        FinancialCalculationQueryService $queries,
    ): JsonResponse {
        $calculations = $queries->paginate(
            $request->validated(),
        );

        return $this->success([
            'items' => FinancialCalculationResource::collection(
                $calculations,
            ),
            'pagination' => [
                'current_page' => $calculations->currentPage(),
                'last_page' => $calculations->lastPage(),
                'per_page' => $calculations->perPage(),
                'total' => $calculations->total(),
            ],
        ]);
    }

    public function show(
        string $financialCalculation,
        FinancialCalculationQueryService $queries,
    ): JsonResponse {
        return $this->success(
            new FinancialCalculationResource(
                $queries->findByPublicId(
                    $financialCalculation,
                ),
            ),
        );
    }

    public function events(
        string $financialCalculation,
        FinancialCalculationQueryService $queries,
    ): JsonResponse {
        return $this->success([
            'items' => FinancialCalculationEventResource::collection(
                $queries->events(
                    $financialCalculation,
                ),
            ),
        ]);
    }

    /**
     * @throws AuthenticationException
     */
    private function actor(Request $request): User
    {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return $actor;
    }
}
