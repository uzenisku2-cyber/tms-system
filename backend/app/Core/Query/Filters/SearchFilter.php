<?php

declare(strict_types=1);

namespace App\Core\Query\Filters;

use Illuminate\Database\Eloquent\Builder;

class SearchFilter
{
    /**
     * Basic search across vehicle fields.
     */
    public function __invoke(Builder $query, ?string $value): Builder
    {
        if (empty($value)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($value) {
            $q->where('name', 'like', "%{$value}%")
              ->orWhere('plate', 'like', "%{$value}%");
        });
    }
}