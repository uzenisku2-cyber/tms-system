<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        if (! $user->hasRole($role)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Forbidden - insufficient role',
            ], 403);
        }

        return $next($request);
    }
}