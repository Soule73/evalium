<?php

namespace App\Traits;

/**
 * Trait for models that are directly scoped by academic year.
 *
 * Models using this trait must have an 'academic_year_id' column
 * that directly references the academic_years table.
 *
 * Usage:
 *   use HasAcademicYearScope;
 *
 * Then you can filter by academic year:
 *   Model::forAcademicYear($academicYearId)->get();
 */
trait HasAcademicYearScope
{
    /**
     * Scope to filter records by academic year.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAcademicYear($query, int $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }
}
