<?php

declare(strict_types=1);

namespace App\Http\Controllers;


use App\Core\Http\BaseController;
use App\Http\Resources\AlertResource;
use App\Models\Alert;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;



class AlertController extends BaseController
{


    /**
     * List alerts
     */
    public function index(
        Request $request
    ): JsonResponse {


        $query = Alert::with([
            'trip',
        ]);



        if ($request->filled('type')) {

            $query->where(
                'type',
                $request->type
            );

        }



        if ($request->filled('severity')) {

            $query->where(
                'severity',
                $request->severity
            );

        }



        $alerts = $query
            ->latest()
            ->paginate(20);



        return $this->success(

            [

                'unread_count' => Alert::whereNull('read_at')->count(),

                'items' => AlertResource::collection($alerts),

                'meta' => [

                    'current_page' => $alerts->currentPage(),

                    'last_page' => $alerts->lastPage(),

                    'total' => $alerts->total(),

                ],

            ],

            'Alerts loaded.'

        );

    }





    /**
     * Open alerts
     */
    public function open(): JsonResponse
    {


        $alerts = Alert::with([
                'trip',
            ])
            ->whereNull('resolved_at')
            ->latest()
            ->paginate(20);



        return $this->success(

            [

                'count' => $alerts->total(),

                'items' => AlertResource::collection($alerts),

                'meta' => [

                    'current_page' => $alerts->currentPage(),

                    'last_page' => $alerts->lastPage(),

                    'total' => $alerts->total(),

                ],

            ],

            'Open alerts loaded.'

        );

    }





    /**
     * Alert history
     */
    public function history(): JsonResponse
    {


        $alerts = Alert::with([

                'trip',

                'resolver',

            ])
            ->whereNotNull('resolved_at')
            ->latest('resolved_at')
            ->paginate(20);



        return $this->success(

            [

                'count' => $alerts->total(),

                'items' => AlertResource::collection($alerts),

                'meta' => [

                    'current_page' => $alerts->currentPage(),

                    'last_page' => $alerts->lastPage(),

                    'total' => $alerts->total(),

                ],

            ],

            'Alert history loaded.'

        );

    }





    /**
     * Unread alerts
     */
    public function unread(): JsonResponse
    {


        $alerts = Alert::with([
                'trip',
            ])
            ->whereNull('read_at')
            ->latest()
            ->get();



        return $this->success(

            [

                'count' => $alerts->count(),

                'items' => AlertResource::collection($alerts),

            ],

            'Unread alerts loaded.'

        );

    }





    /**
     * Dashboard summary
     */
    public function summary(): JsonResponse
    {


        return $this->success(

            [

                'total' => Alert::count(),

                'unresolved' => Alert::whereNull('resolved_at')->count(),

                'critical' => Alert::where('severity', 'critical')
                    ->whereNull('resolved_at')
                    ->count(),

                'warning' => Alert::where('severity', 'warning')
                    ->whereNull('resolved_at')
                    ->count(),

                'info' => Alert::where('severity', 'info')
                    ->whereNull('resolved_at')
                    ->count(),

            ],

            'Alert summary loaded.'

        );

    }





    /**
     * Show alert
     */
    public function show(
        Alert $alert
    ): JsonResponse {


        return $this->success(

            new AlertResource(
                $alert->load([
                    'trip',
                    'user',
                    'resolver',
                ])
            ),

            'Alert loaded.'

        );

    }





    /**
     * Mark alert as read
     */
    public function read(
        Alert $alert
    ): JsonResponse {


        $alert->update([

            'read_at' => now(),

        ]);



        return $this->success(

            new AlertResource(
                $alert->fresh()
            ),

            'Alert marked as read.'

        );

    }





    /**
     * Mark all alerts as read
     */
    public function readAll(): JsonResponse
    {


        Alert::whereNull('read_at')
            ->update([

                'read_at' => now(),

            ]);



        return $this->success(

            null,

            'All alerts marked as read.'

        );

    }





    /**
     * Resolve alert
     */
    public function resolve(
        Alert $alert
    ): JsonResponse
    {


        if ($alert->resolved_at) {

            return $this->error(
                'Alert already resolved.',
                422
            );

        }



        $alert->update([

            'resolved_at' => now(),

            'resolved_by' => auth()->id(),

        ]);



        return $this->success(

            new AlertResource(
                $alert->fresh()
            ),

            'Alert resolved.'

        );

    }


}