<?php

namespace App\Http\Middleware;

use App\Settings\GeneralSettings;
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
     * Priority: user->locale > session locale > GeneralSettings->default_locale > config.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = ['fr', 'en'];
        $fallback = config('app.locale', 'en');

        try {
            $locale = app(GeneralSettings::class)->default_locale ?? $fallback;
        } catch (\Throwable) {
            $locale = $fallback;
        }

        if ($request->user() && $request->user()->locale) {
            $locale = $request->user()->locale;
        } elseif (Session::has('locale')) {
            $locale = Session::get('locale');
        }

        if (! in_array($locale, $supportedLocales)) {
            $locale = $fallback;
        }

        App::setLocale($locale);

        return $next($request);
    }
}
