<?php

declare(strict_types=1);

namespace App\Http\Resources;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;



class DashboardMetricsResource extends JsonResource
{


    public function toArray(
        Request $request
    ): array
    {


        return [


            'trips' =>

                $this->resource['trips'] ?? [],



            'alerts' =>

                $this->resource['alerts'] ?? [],



            'monitoring' =>

                $this->resource['monitoring'] ?? [],



            'notifications' =>

                $this->resource['notifications'] ?? [],


        ];

    }


}