<?php

declare(strict_types=1);

namespace App\Http\Resources;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;



class AlertResource extends JsonResource
{


    public function toArray(
        Request $request
    ): array
    {


        return [


            'id' =>

                $this->id,



            'type' =>

                $this->type,



            'severity' =>

                $this->severity,



            'message' =>

                $this->message,



            'read' =>

                $this->read_at !== null,



            'resolved' =>

                $this->resolved_at !== null,



            'created_at' =>

                $this->created_at,



            'resolved_at' =>

                $this->resolved_at,



            'trip' => $this->whenLoaded(

                'trip',

                [

                    'id' =>

                        $this->trip->id,



                    'status' =>

                        $this->trip->status,

                ]

            ),


        ];

    }


}