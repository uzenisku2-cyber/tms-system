<?php

declare(strict_types=1);

namespace App\Core\Query;

use Illuminate\Database\Eloquent\Builder;

class QueryPipeline
{
    public function __construct(
        private Builder $builder
    ) {}

    /**
     * Apply a list of filters (callables) to the query builder.
     */
    public function apply(array $filters): Builder
    {
        foreach ($filters as $filter) {
            if (is_callable($filter)) {
                $this->builder = $filter($this->builder);
            }
        }

        return $this->builder;
    }
}