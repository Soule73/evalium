<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if the authenticated user has the required role(s).
 *
 * This middleware verifies the user's role before allowing access to certain routes.
 * If the user does not have the required role, they will be redirected or denied access.
 *
 * Usage:
 * Apply this middleware to routes or controllers that require role-based authorization.
 *
 * @package App\Http\Middleware
 */
class RoleMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            abort(401, __('messages.unauthenticated'));
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->hasAnyRole($roles)) {
            abort(403, __('messages.operation_failed'));
        }

        return $next($request);
    }
}
