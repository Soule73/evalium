<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Enrollment model representing a student's enrollment in a class.
 * Replaces the old group_student pivot table.
 */
class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'student_id',
        'enrolled_at',
        'withdrawn_at',
        'status',
    ];

    protected $casts = [
        'enrolled_at' => 'date',
        'withdrawn_at' => 'date',
    ];

    /**
     * Get the class this enrollment belongs to.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Get the student enrolled.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Scope to get only active enrollments.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter enrollments by academic year (through class).
     */
    public function scopeForAcademicYear($query, int $academicYearId)
    {
        return $query->whereHas('class', function ($q) use ($academicYearId) {
            $q->where('academic_year_id', $academicYearId);
        });
    }
}
