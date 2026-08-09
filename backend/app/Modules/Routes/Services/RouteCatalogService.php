<?php

namespace App\Modules\Routes\Services;

use App\Modules\Routes\Models\Route;
use App\Modules\Routes\Models\RouteVersion;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

class RouteCatalogService
{
    public function create(array $attributes): Route
    {
        return DB::transaction(function () use ($attributes): Route {
            $route = Route::query()->create([
                'active' => $attributes['active'] ?? true,
            ]);

            $route->versions()->create([
                'route_number' => $attributes['route_number'],
                'route_name' => $attributes['route_name'],
                'area' => $attributes['area'] ?? null,
                'valid_from' => $attributes['valid_from'],
                'valid_to' => null,
                'change_type' => 'created',
                'change_note' => $attributes['change_note'] ?? null,
            ]);

            return $route->load('currentVersion');
        });
    }

    public function addVersion(Route $route, array $attributes): RouteVersion
    {
        return DB::transaction(function () use ($route, $attributes): RouteVersion {
            $newValidFrom = CarbonImmutable::parse($attributes['valid_from'])->startOfDay();

            /** @var RouteVersion|null $currentVersion */
            $currentVersion = $route->versions()
                ->whereNull('valid_to')
                ->lockForUpdate()
                ->latest('valid_from')
                ->first();

            if ($currentVersion !== null) {
                $currentValidFrom = CarbonImmutable::parse($currentVersion->valid_from)->startOfDay();

                if ($newValidFrom->lessThanOrEqualTo($currentValidFrom)) {
                    throw new DomainException('A new route version must start after the current version.');
                }

                $currentVersion->update([
                    'valid_to' => $newValidFrom->subDay()->toDateString(),
                ]);
            }

            /** @var RouteVersion $version */
            $version = $route->versions()->create([
                'route_number' => $attributes['route_number'],
                'route_name' => $attributes['route_name'],
                'area' => $attributes['area'] ?? null,
                'valid_from' => $newValidFrom->toDateString(),
                'valid_to' => null,
                'change_type' => $attributes['change_type'] ?? 'updated',
                'change_note' => $attributes['change_note'] ?? null,
            ]);

            return $version;
        });
    }

    public function setActive(Route $route, bool $active): Route
    {
        $route->update(['active' => $active]);

        return $route->refresh();
    }
}
