<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Priority order: User preference > Session > Default
        $locale = 'fr'; // Default locale

        // Check if user is authenticated and has a locale preference
        if ($request->user() && $request->user()->locale) {
            $locale = $request->user()->locale;
        }
        // Otherwise, check session
        elseif (Session::has('locale')) {
            $locale = Session::get('locale');
        }

        // Set the application locale
        App::setLocale($locale);

        return $next($request);
    }
}
