<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class ExamAssignment
 *
 * Represents an assignment of an exam to a user or group.
 *
 * @property int $id The primary key of the exam assignment.
 * @property int $exam_id The ID of the related exam.
 * @property int $student_id The ID of the assigned student.
 * @property \Illuminate\Support\Carbon|null $started_at The date and time when the exam was started.
 * @property \Illuminate\Support\Carbon|null $submitted_at The date and time when the exam was submitted.
 * @property float|null $score The score obtained in the exam.
 * @property float|null $auto_score The automatically calculated score for the exam.
 * @property \Illuminate\Support\Carbon|null $assigned_at The date and time when the exam was assigned.
 * @property \Illuminate\Support\Carbon|null $completed_at The date and time when the exam was completed.
 * @property string|null $status The current status of the assignment.
 * @property \Illuminate\Support\Carbon|null $created_at The date and time when the record was created.
 * @property \Illuminate\Support\Carbon|null $updated_at The date and time when the record was last updated.
 *
 * @method static \Illuminate\Database\Eloquent\Builder|ExamAssignment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ExamAssignment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ExamAssignment query()
 *
 * @property-read \App\Models\Exam $exam The related exam.
 * @property-read \App\Models\User $student The assigned student.
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Answer> $answers The answers associated with this assignment.
 *
 * @mixin \Eloquent
 */
class ExamAssignment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'exam_id',
        'student_id',
        'assigned_at',
        'started_at',
        'submitted_at',
        'score',
        'auto_score',
        'status',
        'teacher_notes',
        'security_violation',
        'forced_submission',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => null,
        'forced_submission' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'score' => 'decimal:2',
            'auto_score' => 'decimal:2',
            'forced_submission' => 'boolean',
        ];
    }

    /**
     * Get the exam that is associated with this assignment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Exam, self>
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the student that is associated with this assignment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, self>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the answers that are associated with this assignment.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Answer>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class, 'assignment_id');
    }

    /**
     * Scope a query to only include assignments not yet started (no started_at timestamp).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * @noinspection PhpUnused
     */
    public function scopeNotStarted($query)
    {
        return $query->whereNull('started_at');
    }

    /**
     * Scope a query to only include exam assignments that have been started but not submitted.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInProgress($query)
    {
        return $query->whereNotNull('started_at')->whereNull('submitted_at');
    }

    /**
     * Scope a query to only include submitted exam assignments.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    /**
     * Determine if the exam assignment is currently in progress.
     *
     * @return bool True if the exam assignment is in progress, false otherwise.
     */
    public function isInProgress(): bool
    {
        return $this->started_at !== null && $this->submitted_at === null;
    }

    /**
     * Determine if the exam assignment has been completed.
     *
     * @return bool True if the exam assignment is completed, false otherwise.
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['submitted', 'graded']);
    }
}
