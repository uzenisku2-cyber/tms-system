<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\TripRealtimeBroadcast;
use App\Http\Controllers\Controller;
use App\Modules\Fleet\Models\Vehicle;
use App\Modules\Trips\Models\Trip;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GpsController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_if(
            $user === null,
            401
        );

        $data = $request->validate([
            'vehicle_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'trip_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
            'speed' => [
                'nullable',
                'numeric',
                'min:0',
                'max:300',
            ],
            'heading' => [
                'nullable',
                'numeric',
                'min:0',
                'lt:360',
            ],
        ]);

        $userId = (int) $user->getAuthIdentifier();

        $vehicle = Vehicle::query()
            ->whereKey($data['vehicle_id'])
            ->where('user_id', $userId)
            ->firstOrFail();

        $trip = Trip::query()
            ->whereKey($data['trip_id'])
            ->where('user_id', $userId)
            ->where(
                'vehicle_id',
                $vehicle->getKey()
            )
            ->firstOrFail();

        $position = $vehicle
            ->positions()
            ->create([
                'trip_id' => $trip->getKey(),
                'latitude' => (float) $data['latitude'],
                'longitude' => (float) $data['longitude'],
                'speed' => (int) round(
                    (float) ($data['speed'] ?? 0)
                ),
                'heading' => (int) round(
                    (float) ($data['heading'] ?? 0)
                ),
            ]);

        $payload = [
            'vehicle_id' => (int) $vehicle->getKey(),
            'trip_id' => (int) $trip->getKey(),
            'latitude' => (float) $position->latitude,
            'longitude' => (float) $position->longitude,
            'speed' => (int) $position->speed,
            'heading' => (int) $position->heading,
            'recorded_at' =>
                $position->created_at?->toIso8601String(),
        ];

        event(
            new TripRealtimeBroadcast(
                'trip.' . $trip->getKey(),
                $payload
            )
        );

        return response()->json([
            'status' => 'ok',
            'data' => $payload,
        ]);
    }
}
