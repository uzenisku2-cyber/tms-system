<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!auth()->check()) {
            abort(401);
        }

        if (!auth()->user()->can($permission)) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}