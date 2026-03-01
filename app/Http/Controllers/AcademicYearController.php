<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    use AuthorizesRequests;

    /**
     * Switch the user's active academic year context in session.
     * Does NOT change the is_current flag in the database.
     */
    public function setCurrent(Request $request): RedirectResponse
    {
        $request->validate([
            'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
        ]);

        $request->session()->put('academic_year_id', (int) $request->academic_year_id);

        return back();
    }
}
