<?php

namespace App\Traits;

/**
 * Trait for models that are scoped by academic year through a class relationship.
 *
 * Models using this trait must have a 'class' relationship that links to ClassModel,
 * which in turn has an 'academic_year_id' column.
 *
 * Usage:
 *   use HasAcademicYearThroughClass;
 *
 * Then you can filter by academic year:
 *   Model::forAcademicYear($academicYearId)->get();
 *
 * By default, this trait filters through a 'class' relationship. If your model uses
 * a different relationship name, override the `academicYearRelation()` method.
 */
trait HasAcademicYearThroughClass
{
    /**
     * Scope to filter records by academic year through class relationship.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAcademicYear($query, int $academicYearId)
    {
        $relation = $this->academicYearRelation();

        return $query->whereHas($relation, function ($q) use ($academicYearId) {
            $q->where('academic_year_id', $academicYearId);
        });
    }

    /**
     * Get the relationship path to reach the academic year.
     * Override this method if your model uses a different relationship name.
     */
    protected function academicYearRelation(): string
    {
        return 'class';
    }
}
