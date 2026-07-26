<?php

declare(strict_types=1);

namespace App\Core\Http;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaginatedResponse
{
    public static function make(LengthAwarePaginator $paginator): array
    {
        return [
            'items' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}