<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * AssessmentAssignment model representing a student's assignment to an assessment.
 *
 * Linked to an enrollment rather than directly to a student,
 * preserving class and academic year context.
 */
class AssessmentAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'assessment_id',
        'enrollment_id',
        'started_at',
        'submitted_at',
        'graded_at',
        'teacher_notes',
        'forced_submission',
        'security_violation',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'forced_submission' => 'boolean',
    ];

    protected $appends = [
        'status',
        'score',
        'auto_score',
        'is_virtual',
    ];

    /**
     * Get the assessment this assignment belongs to.
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * Get the enrollment this assignment is linked to.
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Convenient accessor for student_id via enrollment.
     */
    public function getStudentIdAttribute(): ?int
    {
        return $this->enrollment?->student_id;
    }

    /**
     * Convenient accessor for the student model via enrollment.
     *
     * For eager loading, use ->with('enrollment.student') instead.
     */
    public function getStudentAttribute(): ?User
    {
        return $this->enrollment?->student;
    }

    /**
     * Scope to filter assignments by student through enrollment.
     *
     * @param  Builder<self>  $query
     * @param  User|int  $student  The student or student ID
     * @return Builder<self>
     */
    public function scopeForStudent(Builder $query, User|int $student): Builder
    {
        $studentId = $student instanceof User ? $student->id : $student;

        return $query->whereHas('enrollment', fn (Builder $q) => $q->where('student_id', $studentId));
    }

    /**
     * Scope that enforces the correct eager loading for assignment listing views.
     *
     * Always use this scope when loading assignments that will render student names,
     * emails, or answer data. Prevents silent N+1 regressions as the codebase grows.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWithStudentData(Builder $query): Builder
    {
        return $query->with(['enrollment.student', 'answers']);
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
     * Compute the auto-scored total from associated answers.
     *
     * Returns the sum of all scored answers after submission, including
     * auto-corrected MCQ scores. Returns null when not yet submitted.
     * Used by the frontend to display partial scores before teacher grading.
     */
    public function getAutoScoreAttribute(): ?float
    {
        if (! $this->submitted_at) {
            return null;
        }

        if (array_key_exists('answers_sum_score', $this->attributes)) {
            $val = $this->attributes['answers_sum_score'];

            return $val !== null ? (float) $val : 0.0;
        }

        return (float) $this->answers()->sum('score');
    }

    /**
     * Compute the total score from associated answers.
     *
     * Returns null when the assignment has not been graded yet.
     * Uses the eager-loaded `answers_sum_score` when available to avoid N+1 queries.
     */
    public function getScoreAttribute(): ?float
    {
        if (! $this->graded_at) {
            return null;
        }

        if (array_key_exists('answers_sum_score', $this->attributes)) {
            $val = $this->attributes['answers_sum_score'];

            return $val !== null ? (float) $val : null;
        }

        return (float) $this->answers()->sum('score');
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

    /**
     * Indicate that persisted assignment records are never virtual.
     *
     * Virtual (not-started) placeholders are plain objects returned alongside
     * real assignments in grading views. This accessor ensures real assignments
     * always include the `is_virtual` key in JSON serialization.
     */
    public function getIsVirtualAttribute(): bool
    {
        return false;
    }
}
