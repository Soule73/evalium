<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to check if the authenticated user has the required permission(s).
 *
 * This middleware verifies the user's permissions before allowing access to certain routes.
 * Unlike RoleMiddleware, this checks actual permissions which makes it flexible for
 * custom roles created by administrators.
 *
 * Usage:
 * Route::middleware('permission:create exams')->group(...);
 * Route::middleware('permission:create exams,edit exams')->group(...);
 *
 * @package App\Http\Middleware
 */
class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @param string ...$permissions One or more permission names (separated by comma in route definition)
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!Auth::check()) {
            abort(401, 'Non autorisé');
        }

        $user = Auth::user();

        // Vérifier si l'utilisateur a au moins une des permissions requises
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $next($request);
            }
        }

        abort(403, "Accès refusé. Vous n'avez pas les permissions nécessaires pour accéder à cette ressource.");
    }
}
