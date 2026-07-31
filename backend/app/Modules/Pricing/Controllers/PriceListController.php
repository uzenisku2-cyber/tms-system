<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Core\Http\BaseController;
use App\Models\User;
use App\Modules\Pricing\Requests\ApprovePriceListVersionRequest;
use App\Modules\Pricing\Requests\PriceListIndexRequest;
use App\Modules\Pricing\Requests\StorePriceListRequest;
use App\Modules\Pricing\Requests\StorePriceListVersionRequest;
use App\Modules\Pricing\Requests\UpdatePriceListVersionRequest;
use App\Modules\Pricing\Resources\PriceListResource;
use App\Modules\Pricing\Resources\PriceListVersionResource;
use App\Modules\Pricing\Services\PriceListQueryService;
use App\Modules\Pricing\Services\PriceListWriteService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PriceListController extends BaseController
{
    public function store(
        StorePriceListRequest $request,
        PriceListWriteService $writes,
    ): JsonResponse {
        return $this->success(
            new PriceListResource(
                $writes->createDraft(
                    $this->actor($request),
                    $request->validated(),
                ),
            ),
            'Price list draft created.',
            201,
        );
    }

    public function storeVersion(
        StorePriceListVersionRequest $request,
        string $priceList,
        PriceListWriteService $writes,
    ): JsonResponse {
        return $this->success(
            new PriceListVersionResource(
                $writes->createDraftVersion(
                    $this->actor($request),
                    $priceList,
                    $request->validated(),
                ),
            ),
            'Price list draft version created.',
            201,
        );
    }

    public function updateVersion(
        UpdatePriceListVersionRequest $request,
        string $priceList,
        string $version,
        PriceListWriteService $writes,
    ): JsonResponse {
        return $this->success(
            new PriceListVersionResource(
                $writes->updateDraftVersion(
                    $this->actor($request),
                    $priceList,
                    (int) $version,
                    $request->validated(),
                ),
            ),
            'Price list draft version updated.',
        );
    }

    public function approveVersion(
        ApprovePriceListVersionRequest $request,
        string $priceList,
        string $version,
        PriceListWriteService $writes,
    ): JsonResponse {
        return $this->success(
            new PriceListVersionResource(
                $writes->approveDraftVersion(
                    $this->actor($request),
                    $priceList,
                    (int) $version,
                    $request->validated(),
                ),
            ),
            'Price list version approved.',
        );
    }

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

    private function actor(
        Request $request,
    ): User {
        $actor = $request->user();

        if (! $actor instanceof User) {
            throw new AuthenticationException;
        }

        return $actor;
    }
}
