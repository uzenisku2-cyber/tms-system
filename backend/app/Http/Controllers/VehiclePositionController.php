<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\Fleet\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehiclePositionController extends Controller
{
    private const HISTORY_LIMIT = 500;

    public function index(
        Request $request,
        Vehicle $vehicle
    ): JsonResponse {
        $user = $request->user();

        abort_if(
            $user === null,
            401
        );

        abort_unless(
            (int) $vehicle->user_id ===
                (int) $user->getAuthIdentifier(),
            404
        );

        $positions = $vehicle
            ->positions()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::HISTORY_LIMIT)
            ->get([
                'id',
                'trip_id',
                'latitude',
                'longitude',
                'speed',
                'heading',
                'created_at',
            ])
            ->reverse()
            ->values();

        return response()->json($positions);
    }
}
