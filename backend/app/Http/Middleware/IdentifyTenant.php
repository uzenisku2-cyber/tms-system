<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Core\Tenancy\TenantContext;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next)
    {
        // SIMPLE STRATEGY (HEADER BASED)
        $tenantId = $request->header('X-Tenant-ID');

        if ($tenantId) {
            TenantContext::set((int) $tenantId);
        }

        return $next($request);
    }
}