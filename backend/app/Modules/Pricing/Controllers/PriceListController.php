<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Core\Http\BaseController;
use App\Modules\Pricing\Requests\PriceListIndexRequest;
use App\Modules\Pricing\Resources\PriceListResource;
use App\Modules\Pricing\Resources\PriceListVersionResource;
use App\Modules\Pricing\Services\PriceListQueryService;
use Illuminate\Http\JsonResponse;

final class PriceListController extends BaseController
{
    public function index(
        PriceListIndexRequest $request,
        PriceListQueryService $queries,
    ): JsonResponse {
        $priceLists = $queries->paginate(
            $request->validated(),
        );

        return $this->success([
            'items' => PriceListResource::collection(
                $priceLists,
            ),
            'pagination' => [
                'current_page' => $priceLists->currentPage(),
                'last_page' => $priceLists->lastPage(),
                'per_page' => $priceLists->perPage(),
                'total' => $priceLists->total(),
            ],
        ]);
    }

    public function show(
        string $priceList,
        PriceListQueryService $queries,
    ): JsonResponse {
        return $this->success(
            new PriceListResource(
                $queries->findByPublicId($priceList),
            ),
        );
    }

    public function versions(
        string $priceList,
        PriceListQueryService $queries,
    ): JsonResponse {
        return $this->success([
            'items' => PriceListVersionResource::collection(
                $queries->versions($priceList),
            ),
        ]);
    }

    public function version(
        string $priceList,
        string $version,
        PriceListQueryService $queries,
    ): JsonResponse {
        return $this->success(
            new PriceListVersionResource(
                $queries->findVersion(
                    $priceList,
                    (int) $version,
                ),
            ),
        );
    }
}
