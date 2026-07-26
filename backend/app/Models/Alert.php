<?php

declare(strict_types=1);


namespace App\Models;


use App\Modules\Trips\Models\Trip;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;



class Alert extends Model
{


    protected $fillable = [


        'trip_id',

        'user_id',

        'type',

        'severity',

        'message',

        'read_at',

        'resolved_at',

        'resolved_by',


    ];





    protected $casts = [


        'read_at' => 'datetime',

        'resolved_at' => 'datetime',


    ];





    public function trip(): BelongsTo
    {

        return $this->belongsTo(
            Trip::class
        );

    }





    public function user(): BelongsTo
    {

        return $this->belongsTo(
            User::class
        );

    }





    public function resolver(): BelongsTo
    {

        return $this->belongsTo(
            User::class,
            'resolved_by'
        );

    }


}