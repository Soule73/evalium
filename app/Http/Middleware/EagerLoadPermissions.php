<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to eager load user permissions and roles to prevent N+1 queries.
 * 
 * This middleware loads all permissions and roles for the authenticated user
 * in a single query, preventing multiple duplicate queries throughout the request lifecycle.
 * 
 * This is particularly useful because:
 * - HandleInertiaRequests needs permissions for frontend
 * - Role/Permission middlewares need to check permissions
 * - Policies may need to check permissions
 * 
 * By loading them once here, all subsequent checks use cached data.
 */
class EagerLoadPermissions
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = $request->user()) {
            // Eager load permissions and roles in one query
            // This prevents the same queries from running multiple times:
            // - Once in HandleInertiaRequests::share()
            // - Again in role/permission middlewares
            // - Again in policies
            $user->load(['permissions', 'roles.permissions']);
        }

        return $next($request);
    }
}
