<?php

namespace App\Http\Controllers;

use App\Http\Resources\AcademicYearResource;
use App\Models\AcademicYear;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    use AuthorizesRequests;

    /**
     * Set the current academic year in session.
     */
    public function setCurrent(Request $request): JsonResponse
    {
        $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
        ]);

        $academicYear = AcademicYear::findOrFail($request->academic_year_id);

        $request->session()->put('academic_year_id', $academicYear->id);

        return response()->json([
            'success' => true,
            'academic_year' => new AcademicYearResource($academicYear),
        ]);
    }
}
