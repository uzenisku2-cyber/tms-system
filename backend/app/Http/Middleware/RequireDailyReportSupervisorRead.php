<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireDailyReportSupervisorRead
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $actor = $request->user();
        if (! $actor instanceof User) {
            abort(401);
        }
        if ($actor->hasRole('driver')
            && ! $actor->can('daily-reports.review')
            && ! $actor->can('daily-reports.enter-for-driver')) {
            abort(403);
        }

        return $next($request);
    }
}
