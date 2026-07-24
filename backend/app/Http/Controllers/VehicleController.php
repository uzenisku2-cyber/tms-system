<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Fleet\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_if(
            $user === null,
            401
        );

        $vehicles = Vehicle::query()
            ->where(
                'user_id',
                (int) $user->getAuthIdentifier()
            )
            ->where('active', true)
            ->with('latestPosition')
            ->orderBy('registration_number')
            ->get([
                'id',
                'registration_number',
                'manufacturer',
                'model',
                'year',
                'vehicle_type',
                'vehicle_size',
                'color',
                'icon',
                'manufacturer_logo',
                'body_style',
            ]);

        $response = $vehicles->map(
            static function (Vehicle $vehicle): array {
                $latestPosition = $vehicle->latestPosition;

                return [
                    'id' => $vehicle->id,
                    'registration_number' => $vehicle->registration_number,
                    'manufacturer' => $vehicle->manufacturer,
                    'model' => $vehicle->model,
                    'year' => $vehicle->year,
                    'vehicle_type' => $vehicle->vehicle_type,
                    'vehicle_size' => $vehicle->vehicle_size,
                    'color' => $vehicle->color,
                    'icon' => $vehicle->icon,
                    'manufacturer_logo' => $vehicle->manufacturer_logo,
                    'body_style' => $vehicle->body_style,
                    'latest_location' => $latestPosition === null
                        ? null
                        : [
                            'trip_id' => $latestPosition->trip_id,
                            'latitude' => $latestPosition->latitude,
                            'longitude' => $latestPosition->longitude,
                            'speed' => $latestPosition->speed,
                            'heading' => $latestPosition->heading,
                            'recorded_at' => $latestPosition
                                ->created_at
                                ?->toIso8601String(),
                        ],
                ];
            }
        );

        return response()->json($response);
    }
}
