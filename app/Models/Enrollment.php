<?php

namespace App\Models;

use App\Traits\HasAcademicYearThroughClass;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Enrollment model representing a student's enrollment in a class.
 * Replaces the old group_student pivot table.
 */
class Enrollment extends Model
{
    use HasAcademicYearThroughClass, HasFactory;

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
     * Get the assessment assignments for this enrollment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\AssessmentAssignment>
     */
    public function assessmentAssignments(): HasMany
    {
        return $this->hasMany(AssessmentAssignment::class);
    }

    /**
     * Scope to get only active enrollments.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
