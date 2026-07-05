<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Controllers;

use App\Core\Http\BaseController;
use App\Modules\Fleet\DTO\VehicleDto;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Fleet\Requests\StoreVehicleRequest;
use App\Modules\Fleet\Requests\UpdateVehicleRequest;
use App\Modules\Fleet\Requests\VehicleIndexRequest;
use App\Modules\Fleet\Resources\VehicleResource;
use App\Modules\Fleet\Services\VehicleService;
use Illuminate\Http\JsonResponse;

class VehicleController extends BaseController
{
    public function __construct(
        protected VehicleService $service,
    ) {}

    public function index(VehicleIndexRequest $request): JsonResponse
    {
        return $this->success(
            VehicleResource::collection(
                $this->service->paginate($request->validated())
            )
        );
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $vehicle = $this->service->create(
            VehicleDto::fromArray(
                $request->validated()
            )
        );

        return $this->success(
            new VehicleResource($vehicle),
            'Created',
            201,
        );
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        return $this->success(
            new VehicleResource($vehicle)
        );
    }

    public function update(
        UpdateVehicleRequest $request,
        Vehicle $vehicle,
    ): JsonResponse {
        $vehicle = $this->service->update(
            $vehicle,
            VehicleDto::fromArray(
                $request->validated()
            )
        );

        return $this->success(
            new VehicleResource($vehicle)
        );
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $this->service->delete($vehicle);

        return $this->success(
            message: 'Deleted'
        );
    }
}
