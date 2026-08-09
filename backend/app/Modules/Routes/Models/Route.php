<?php

namespace App\Modules\Routes\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Route extends Model
{
    protected $fillable = [
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Route $route): void {
            if (! $route->route_uid) {
                $route->route_uid = (string) Str::uuid();
            }
        });
    }

    public function versions(): HasMany
    {
        return $this->hasMany(RouteVersion::class);
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(RouteVersion::class)
            ->whereNull('valid_to')
            ->latestOfMany('valid_from');
    }
}
