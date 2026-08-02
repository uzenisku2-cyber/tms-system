<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Core\Http\BaseController;
use App\Modules\Pricing\Requests\FinancialCalculationIndexRequest;
use App\Modules\Pricing\Resources\FinancialCalculationEventResource;
use App\Modules\Pricing\Resources\FinancialCalculationResource;
use App\Modules\Pricing\Services\FinancialCalculationQueryService;
use Illuminate\Http\JsonResponse;

final class FinancialCalculationController extends BaseController
{
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
}
