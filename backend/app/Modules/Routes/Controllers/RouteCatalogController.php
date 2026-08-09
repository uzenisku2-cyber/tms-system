<?php

namespace App\Modules\Routes\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Routes\Models\Route;
use App\Modules\Routes\Models\RouteVersion;
use App\Modules\Routes\Services\RouteCatalogService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RouteCatalogController extends Controller
{
    private const MANAGE_PERMISSION = 'settings.catalogs.manage';

    public function index(Request $request): View
    {
        $routes = Route::query()
            ->with([
                'currentVersion',
                'versions' => fn ($query) => $query->orderByDesc('valid_from'),
            ])
            ->orderByDesc('active')
            ->orderBy('id')
            ->get();

        return view('mvp.settings-route-catalog-crud', [
            'routes' => $routes,
            'canManage' => $request->user()?->can(self::MANAGE_PERMISSION) ?? false,
        ]);
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $routes = Route::query()
            ->with([
                'currentVersion',
                'versions' => static fn ($query) => $query
                    ->orderByDesc('valid_from')
                    ->orderByDesc('id'),
            ])
            ->orderByDesc('active')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $routes,
            'meta' => [
                'can_manage' => $request->user()?->can(
                    self::MANAGE_PERMISSION,
                ) ?? false,
            ],
        ]);
    }

    public function store(
        Request $request,
        RouteCatalogService $service,
    ): RedirectResponse {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'route_number' => ['required', 'string', 'max:64'],
            'route_name' => ['required', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'valid_from' => ['required', 'date'],
            'change_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->create([
            ...$validated,
            'active' => true,
        ]);

        return redirect()
            ->route('mvp.settings.catalogs.routes')
            ->with('status', 'Trasa byla vytvořena.');
    }

    public function update(
        Request $request,
        Route $route,
        RouteCatalogService $service,
    ): RedirectResponse {
        $this->authorizeManage($request);

        /** @var RouteVersion $current */
        $current = $route->currentVersion()->firstOrFail();

        $validated = $request->validate([
            'route_number' => ['required', 'string', 'max:64'],
            'route_name' => ['required', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
            'valid_from' => [
                'required',
                'date',
                'after:'.CarbonImmutable::parse($current->valid_from)->toDateString(),
            ],
            'change_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $numberChanged = $validated['route_number'] !== $current->route_number;
        $nameChanged = $validated['route_name'] !== $current->route_name;
        $areaChanged = ($validated['area'] ?? null) !== $current->area;

        if (! $numberChanged && ! $nameChanged && ! $areaChanged) {
            throw ValidationException::withMessages([
                'route_number' => 'Číslo, název ani oblast trasy se nezměnily.',
            ]);
        }

        $service->addVersion($route, [
            ...$validated,
            'change_type' => $this->determineChangeType(
                $numberChanged,
                $nameChanged,
                $areaChanged,
            ),
        ]);

        return redirect()
            ->route('mvp.settings.catalogs.routes')
            ->with('status', 'Byla vytvořena nová historická verze trasy.');
    }

    public function setActive(
        Request $request,
        Route $route,
        RouteCatalogService $service,
    ): RedirectResponse {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $service->setActive(
            $route,
            (bool) $validated['active'],
        );

        return redirect()
            ->route('mvp.settings.catalogs.routes')
            ->with(
                'status',
                $validated['active']
                    ? 'Trasa byla aktivována.'
                    : 'Trasa byla deaktivována.',
            );
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless(
            $request->user()?->can(self::MANAGE_PERMISSION) === true,
            403,
        );
    }

    private function determineChangeType(
        bool $numberChanged,
        bool $nameChanged,
        bool $areaChanged,
    ): string {
        $changes = array_filter([
            'number' => $numberChanged,
            'name' => $nameChanged,
            'area' => $areaChanged,
        ]);

        if (count($changes) > 1) {
            return 'combined_change';
        }

        if ($numberChanged) {
            return 'renumbered';
        }

        if ($nameChanged) {
            return 'renamed';
        }

        return 'area_changed';
    }
}
