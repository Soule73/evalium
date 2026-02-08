<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AssessmentAssignment model representing a student's assignment to an assessment.
 */
class AssessmentAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'student_id',
        'submitted_at',
        'graded_at',
        'score',
        'teacher_notes',
        'forced_submission',
        'security_violation',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'score' => 'decimal:2',
        'forced_submission' => 'boolean',
    ];

    /**
     * Get the assessment this assignment belongs to.
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * Get the student assigned.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the answers for this assignment.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class, 'assessment_assignment_id');
    }

    /**
     * Scope to get submitted assignments (graded or not).
     */
    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    /**
     * Scope to get graded assignments.
     */
    public function scopeGraded($query)
    {
        return $query->whereNotNull('graded_at');
    }

    /**
     * Scope to get not submitted assignments.
     */
    public function scopeNotSubmitted($query)
    {
        return $query->whereNull('submitted_at');
    }

    /**
     * Get the status attribute.
     */
    public function getStatusAttribute(): string
    {
        if ($this->graded_at) {
            return 'graded';
        }

        if ($this->submitted_at) {
            return 'submitted';
        }

        return 'not_submitted';
    }
}
