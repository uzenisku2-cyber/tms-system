<?php

declare(strict_types=1);

namespace App\Modules\Pricing\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Pricing\Requests\StoreProviderManagedPriceListRequest;
use App\Modules\Pricing\Resources\PriceListResource;
use App\Modules\Pricing\Services\PriceListWriteService;
use Illuminate\Http\JsonResponse;

final class CustomerBillingPriceListController extends Controller
{
    public function __construct(
        private readonly PriceListWriteService $writeService,
    ) {}

    public function store(
        StoreProviderManagedPriceListRequest $request,
        int $relationship,
    ): JsonResponse {
        $actor = $request->user();

        abort_unless(
            $actor instanceof User,
            401,
        );

        $priceList =
            $this->writeService->createProviderManagedDraft(
                $actor,
                $relationship,
                $request->validated(),
            );

        return response()->json(
            [
                'data' => (new PriceListResource(
                    $priceList,
                ))->resolve($request),
            ],
            201,
        );
    }
}