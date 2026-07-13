<?php

declare(strict_types=1);

namespace App\Models;


use App\Modules\Trips\Models\Trip;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;



class TripLocation extends Model
{


    /*
    |--------------------------------------------------------------------------
    | Mass assignment
    |--------------------------------------------------------------------------
    */


    protected $fillable = [

        'trip_id',

        'latitude',

        'longitude',

        'speed',

        'heading',

    ];



    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */


    protected $casts = [

        'latitude' =>
            'decimal:7',

        'longitude' =>
            'decimal:7',

        'speed' =>
            'integer',

        'heading' =>
            'integer',

    ];



    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */


    public function trip(): BelongsTo
    {

        return $this->belongsTo(
            Trip::class
        );

    }



}