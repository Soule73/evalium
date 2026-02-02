<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AssessmentAssignment model representing a student's assignment to an assessment.
 * Replaces the old ExamAssignment model.
 */
class AssessmentAssignment extends Model
{
    use HasFactory;
    protected $fillable = [
        'assessment_id',
        'student_id',
        'assigned_at',
        'started_at',
        'submitted_at',
        'score',
        'feedback',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'score' => 'decimal:2',
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
     * Scope to get completed assignments.
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    /**
     * Scope to get in-progress assignments.
     */
    public function scopeInProgress($query)
    {
        return $query->whereNotNull('started_at')
            ->whereNull('submitted_at');
    }

    /**
     * Scope to get not-started assignments.
     */
    public function scopeNotStarted($query)
    {
        return $query->whereNull('started_at')
            ->whereNull('submitted_at');
    }
}
