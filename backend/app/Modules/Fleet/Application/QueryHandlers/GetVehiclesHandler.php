<?php

namespace App\Modules\Fleet\Application\QueryHandlers;

use App\Modules\Fleet\Domain\Models\Vehicle;
use Illuminate\Support\Facades\Cache;

class GetVehiclesHandler
{
    public function handle($query)
    {
        $cacheKey = $this->cacheKey($query);

        return Cache::remember($cacheKey, 60, function () use ($query) {

            $q = Vehicle::query()
                ->where('user_id', $query->userId);

            if (!empty($query->filters['search'])) {
                $q->where('name', 'ilike', '%' . $query->filters['search'] . '%');
            }

            if (!empty($query->filters['sort'])) {
                $sort = $query->filters['sort'];

                $q->orderBy(
                    ltrim($sort, '-'),
                    str_starts_with($sort, '-') ? 'desc' : 'asc'
                );
            }

            return $q->paginate($query->perPage);
        });
    }

    private function cacheKey($query): string
    {
        return 'vehicles:list:' . md5(json_encode([
            'filters' => $query->filters,
            'perPage' => $query->perPage,
            'userId' => $query->userId,
        ]));
    }
}