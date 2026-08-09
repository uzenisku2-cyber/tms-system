<?php

namespace App\Modules\Routes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteVersion extends Model
{
    protected $fillable = [
        'route_number',
        'route_name',
        'area',
        'valid_from',
        'valid_to',
        'change_type',
        'change_note',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_to' => 'date',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }
}
