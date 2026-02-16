<?php

namespace App\Traits;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait FiltersAcademicYear
{
    /**
     * Get the selected academic year ID from request.
     */
    protected function getSelectedAcademicYearId(Request $request): ?int
    {
        return $request->input('selected_academic_year_id')
          ?? $request->session()->get('academic_year_id')
          ?? AcademicYear::where('is_current', true)->value('id');
    }

    /**
     * Scope query to filter by academic year.
     */
    protected function scopeForAcademicYear(Builder $query, int $academicYearId): Builder
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    /**
     * Validate that a model belongs to the selected academic year.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function validateAcademicYearAccess(Model $model, int $academicYearId): void
    {
        if ($model->academic_year_id !== $academicYearId) {
            abort(403, __('messages.resource_wrong_academic_year'));
        }
    }
}
