<?php

declare(strict_types=1);


namespace App\Modules\Trips\Models;


use App\Models\TripLocation;
use App\Models\User;

use App\Modules\Drivers\Models\Driver;
use App\Modules\Fleet\Models\Vehicle;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;



class Trip extends Model
{


    public const STATUS_PLANNED = 'planned';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_STARTED = 'started';

    public const STATUS_FINISHED = 'finished';

    public const STATUS_CANCELLED = 'cancelled';





    protected $fillable = [


        'user_id',

        'driver_id',

        'vehicle_id',

        'origin',

        'destination',


        /*
        |--------------------------------------------------------------------------
        | GPS coordinates / ETA
        |--------------------------------------------------------------------------
        */


        'origin_lat',

        'origin_lng',

        'destination_lat',

        'destination_lng',

        'distance_km',



        'status',

        'scheduled_at',

        'started_at',

        'finished_at',

        'cancelled_by',

        'cancelled_at',

        'cancel_reason',


    ];





    protected $casts = [


        'scheduled_at' => 'datetime',

        'started_at' => 'datetime',

        'finished_at' => 'datetime',

        'cancelled_at' => 'datetime',



        'origin_lat' => 'decimal:7',

        'origin_lng' => 'decimal:7',

        'destination_lat' => 'decimal:7',

        'destination_lng' => 'decimal:7',

        'distance_km' => 'decimal:2',


    ];





    public function user(): BelongsTo
    {

        return $this->belongsTo(
            User::class
        );

    }







    public function driver(): BelongsTo
    {

        return $this->belongsTo(
            Driver::class
        );

    }







    public function vehicle(): BelongsTo
    {

        return $this->belongsTo(
            Vehicle::class
        );

    }







    public function events(): HasMany
    {

        return $this->hasMany(
            TripEvent::class
        );

    }







    public function assignments(): HasMany
    {

        return $this->hasMany(
            TripAssignment::class
        );

    }







    public function locations(): HasMany
    {

        return $this->hasMany(
            TripLocation::class
        );

    }







    public function cancelledBy(): BelongsTo
    {

        return $this->belongsTo(
            User::class,
            'cancelled_by'
        );

    }







    /*
    |--------------------------------------------------------------------------
    | Status workflow
    |--------------------------------------------------------------------------
    */


    public function canChangeStatus(
        string $newStatus
    ): bool
    {


        return match ($this->status) {


            self::STATUS_PLANNED =>

                in_array(

                    $newStatus,

                    [

                        self::STATUS_ASSIGNED,

                        self::STATUS_CANCELLED,

                    ],

                    true

                ),





            self::STATUS_ASSIGNED =>

                in_array(

                    $newStatus,

                    [

                        self::STATUS_STARTED,

                        self::STATUS_CANCELLED,

                    ],

                    true

                ),





            self::STATUS_STARTED =>

                in_array(

                    $newStatus,

                    [

                        self::STATUS_FINISHED,

                        self::STATUS_CANCELLED,

                    ],

                    true

                ),





            self::STATUS_FINISHED =>

                false,





            self::STATUS_CANCELLED =>

                false,





            default => false,


        };


    }


}