<?php

declare(strict_types=1);

namespace App\Http\Resources;


use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;



class NotificationResource extends JsonResource
{


    public function toArray(
        Request $request
    ): array
    {


        return [


            'id' =>

                $this->id,



            'type' =>

                class_basename($this->type),



            'read' =>

                $this->read_at !== null,



            'data' =>

                $this->data ?? [],



            'created_at' =>

                $this->created_at,


        ];

    }


}