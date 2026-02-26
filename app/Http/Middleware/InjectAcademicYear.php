<?php

namespace App\Http\Middleware;

use App\Models\AcademicYear;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectAcademicYear
{
    /**
     * Handle an incoming request and inject the resolved academic year.
     *
     * Priority order:
     * 1. Query parameter (?academic_year_id=X)
     * 2. Session value
     * 3. Current academic year (is_current = true)
     *
     * The resolved model is stored in request attributes so downstream
     * middleware (HandleInertiaRequests) can reuse it without an extra query.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $academicYear = $this->resolveAcademicYear($request);

        if ($academicYear) {
            $request->merge(['selected_academic_year_id' => $academicYear->id]);
            $request->attributes->set('selected_academic_year', $academicYear);

            if ($request->query('academic_year_id')) {
                $request->session()->put('academic_year_id', $academicYear->id);
            }
        }

        return $next($request);
    }

    /**
     * Resolve the academic year from query param, session, or DB fallback.
     * Returns the full model so callers avoid issuing a second query.
     */
    protected function resolveAcademicYear(Request $request): ?AcademicYear
    {
        if ($queryYearId = $request->query('academic_year_id')) {
            $year = AcademicYear::find((int) $queryYearId);
            if ($year) {
                return $year;
            }
        }

        if ($sessionYearId = $request->session()->get('academic_year_id')) {
            $year = AcademicYear::find($sessionYearId);
            if ($year) {
                return $year;
            }
        }

        $currentYear = AcademicYear::where('is_current', true)->first();
        if ($currentYear) {
            $request->session()->put('academic_year_id', $currentYear->id);

            return $currentYear;
        }

        return null;
    }
}
