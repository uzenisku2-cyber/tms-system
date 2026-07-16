<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;


class VehiclePositionController extends Controller
{

    /**
     * Historie GPS pozic vozidla
     */
    public function index(Vehicle $vehicle): JsonResponse
    {

        $positions = $vehicle
            ->positions()
            ->orderBy('created_at')
            ->get([
                'latitude',
                'longitude',
                'speed',
                'heading',
                'created_at'
            ]);


        return response()->json(
            $positions
        );

    }


}