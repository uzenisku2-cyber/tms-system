<?php

namespace App\Modules\Trips\Models;

use Illuminate\Database\Eloquent\Model;


class TripPod extends Model
{

    protected $fillable = [

        'trip_id',

        'recipient',

        'note',

        'delivered_at',

        'delivered_by',

    ];



    protected $casts = [

        'delivered_at' => 'datetime',

    ];



    public function trip()
    {

        return $this->belongsTo(
            Trip::class
        );

    }



    public function deliveredBy()
    {

        return $this->belongsTo(
            \App\Models\User::class,
            'delivered_by'
        );

    }

}