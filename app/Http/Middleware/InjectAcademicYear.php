<?php

namespace App\Http\Middleware;

use App\Models\AcademicYear;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectAcademicYear
{
    /**
     * Handle an incoming request and inject the selected academic year.
     *
     * Priority order:
     * 1. Query parameter (?academic_year_id=X)
     * 2. Session value
     * 3. Current academic year (is_current = true)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $academicYearId = $this->determineAcademicYearId($request);

        if ($academicYearId) {
            $request->merge(['selected_academic_year_id' => $academicYearId]);

            if ($request->query('academic_year_id')) {
                $request->session()->put('academic_year_id', $academicYearId);
            }
        }

        return $next($request);
    }

    protected function determineAcademicYearId(Request $request): ?int
    {
        if ($queryYearId = $request->query('academic_year_id')) {
            if ($this->academicYearExists((int) $queryYearId)) {
                return (int) $queryYearId;
            }
        }

        if ($sessionYearId = $request->session()->get('academic_year_id')) {
            if ($this->academicYearExists($sessionYearId)) {
                return $sessionYearId;
            }
        }

        $currentYear = AcademicYear::where('is_current', true)->first();
        if ($currentYear) {
            $request->session()->put('academic_year_id', $currentYear->id);

            return $currentYear->id;
        }

        return null;
    }

    protected function academicYearExists(int $id): bool
    {
        return AcademicYear::where('id', $id)->exists();
    }
}
