<?php

declare(strict_types=1);

namespace App\Http\Resources;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;



class ActiveTripResource extends JsonResource
{


    public function toArray(
        Request $request
    ): array
    {


        return [


            'id' =>

                $this->id,



            'route' => [


                'from' =>

                    $this->origin ?? null,



                'to' =>

                    $this->destination ?? null,


            ],



            'status' =>

                $this->status,



            'started_at' =>

                $this->started_at ?? null,



            'driver' => $this->whenLoaded(

                'driver',

                [

                    'id' =>

                        $this->driver->id,



                    'name' =>

                        trim(

                            ($this->driver->first_name ?? '')
                            . ' '
                            . ($this->driver->last_name ?? '')

                        ),

                ]

            ),



            'vehicle' => $this->whenLoaded(

                'vehicle',

                [

                    'id' =>

                        $this->vehicle->id,



                    'registration' =>

                        $this->vehicle->registration_number ?? null,



                    'name' =>

                        trim(

                            ($this->vehicle->manufacturer ?? '')
                            . ' '
                            . ($this->vehicle->model ?? '')

                        ),

                ]

            ),


        ];

    }


}