<?php

declare(strict_types=1);

namespace App\Http\Resources;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class DashboardResource extends JsonResource
{


    public function toArray(
        Request $request
    ): array
    {


        return [


            'trips' =>

                $this->resource['trips'] ?? [],



            'drivers' =>

                $this->resource['drivers'] ?? [],



            'vehicles' =>

                $this->resource['vehicles'] ?? [],



            'active_trips' =>

                ActiveTripResource::collection(

                    $this->resource['active_trips'] ?? []

                ),



            'alerts' => [


                'open' =>

                    $this->resource['alerts']['open'] ?? 0,



                'critical' =>

                    $this->resource['alerts']['critical'] ?? 0,



                'latest' =>

                    AlertResource::collection(

                        $this->resource['alerts']['latest'] ?? []

                    ),


            ],



            'operations' =>

                $this->resource['operations'] ?? [],



            'notifications' => [


                'unread' =>

                    $this->resource['notifications']['unread'] ?? 0,



                'latest' =>

                    NotificationResource::collection(

                        $this->resource['notifications']['latest'] ?? []

                    ),


            ],


        ];

    }


}