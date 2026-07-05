<?php

declare(strict_types=1);

namespace App\Core\Query;

use Illuminate\Database\Eloquent\Builder;

abstract class QueryFilter
{
    protected Builder $builder;

    protected array $filters = [];

    public function apply(Builder $builder, array $filters): Builder
    {
        $this->builder = $builder;
        $this->filters = $filters;

        foreach ($filters as $key => $value) {
            $method = $this->resolveMethod($key);

            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }

        return $this->builder;
    }

    protected function resolveMethod(string $key): string
    {
        return lcfirst(str_replace('_', '', ucwords($key, '_')));
    }
}
