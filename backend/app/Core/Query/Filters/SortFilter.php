<?php

declare(strict_types=1);

namespace App\Core\Query\Filters;

use Illuminate\Database\Eloquent\Builder;

class SortFilter
{
    /**
     * Whitelisted sorting to prevent unsafe SQL injection.
     */
    public function __invoke(Builder $query, ?string $sort): Builder
    {
        if (empty($sort)) {
            return $query->latest();
        }

        return match ($sort) {
            'name' => $query->orderBy('name'),
            'plate' => $query->orderBy('plate'),
            'created_at' => $query->orderBy('created_at', 'desc'),
            default => $query->latest(),
        };
    }
}