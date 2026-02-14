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
        'started_at',
        'submitted_at',
        'graded_at',
        'score',
        'teacher_notes',
        'forced_submission',
        'security_violation',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'score' => 'decimal:2',
        'forced_submission' => 'boolean',
    ];

    protected $appends = [
        'status',
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
     * Get the file attachments for this assignment.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(AssignmentAttachment::class);
    }

    /**
     * Scope to get submitted assignments (graded or not).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    /**
     * Scope to get graded assignments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeGraded($query)
    {
        return $query->whereNotNull('graded_at');
    }

    /**
     * Scope to get not submitted assignments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeNotSubmitted($query)
    {
        return $query->whereNull('submitted_at');
    }

    /**
     * Scope to get in-progress assignments (started but not submitted).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<self>  $query
     * @return \Illuminate\Database\Eloquent\Builder<self>
     */
    public function scopeInProgress($query)
    {
        return $query->whereNotNull('started_at')->whereNull('submitted_at');
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

        if ($this->started_at) {
            return 'in_progress';
        }

        return 'not_submitted';
    }
}
