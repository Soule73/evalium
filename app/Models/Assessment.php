<?php

namespace App\Models;

use App\Enums\AssessmentType;
use App\Enums\DeliveryMode;
use App\Traits\HasAcademicYearThroughClass;
use App\Traits\HasJsonSettings;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Assessment model representing an evaluation (devoir, examen, tp, controle, projet).
 */
class Assessment extends Model
{
    use HasAcademicYearThroughClass, HasFactory, HasJsonSettings, SoftDeletes;

    protected $fillable = [
        'class_subject_id',
        'teacher_id',
        'title',
        'description',
        'type',
        'delivery_mode',
        'coefficient',
        'duration_minutes',
        'scheduled_at',
        'due_date',
        'is_published',
        'reminder_sent_at',
        'settings',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AssessmentType::class,
            'delivery_mode' => DeliveryMode::class,
            'coefficient' => 'decimal:2',
            'duration_minutes' => 'integer',
            'scheduled_at' => 'datetime',
            'due_date' => 'datetime',
            'is_published' => 'boolean',
            'reminder_sent_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    protected $appends = [
        'duration',
        'has_ended',
        'shuffle_questions',
        'release_results_after_grading',
        'show_correct_answers',
        'allow_late_submission',
        'results_available_at',
    ];

    /**
     * Get the class subject (teaching) this assessment belongs to.
     */
    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
    }

    /**
     * Get the class this assessment is assigned to (through classSubject).
     * Returns the ClassModel instance or null if no classSubject is set.
     */
    public function getClassAttribute(): ?ClassModel
    {
        return $this->classSubject?->class;
    }

    /**
     * Get the teacher who created this assessment.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the questions for this assessment.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Override to filter through classSubject.class relationship.
     */
    protected function academicYearRelation(): string
    {
        return 'classSubject.class';
    }

    /**
     * Get the assignments for this assessment.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(AssessmentAssignment::class);
    }

    /**
     * Get the total points for this assessment.
     */
    public function getTotalPointsAttribute(): float
    {
        return $this->questions()->sum('points');
    }

    /**
     * Get the duration attribute (alias for duration_minutes).
     */
    public function getDurationAttribute(): ?int
    {
        return $this->duration_minutes;
    }

    /**
     * Get whether questions should be shuffled.
     */
    public function getShuffleQuestionsAttribute(): bool
    {
        return $this->getBooleanSetting('shuffle_questions', false);
    }

    /**
     * Set whether questions should be shuffled.
     */
    public function setShuffleQuestionsAttribute(bool $value): void
    {
        $this->setSettingValue('shuffle_questions', $value);
    }

    /**
     * Get whether results require manual grading before being released to students.
     * When true, results are withheld until the teacher completes grading.
     * When false (default), automatically scored results are shown immediately.
     */
    public function getReleaseResultsAfterGradingAttribute(): bool
    {
        return ! $this->getBooleanSetting('show_results_immediately', true);
    }

    /**
     * Set whether results require manual grading before release.
     */
    public function setReleaseResultsAfterGradingAttribute(bool $value): void
    {
        $this->setSettingValue('show_results_immediately', ! $value);
    }

    /**
     * Get whether correct answers should be revealed to students.
     * Defaults to true: graded results show correct/incorrect unless explicitly disabled.
     */
    public function getShowCorrectAnswersAttribute(): bool
    {
        return $this->getBooleanSetting('show_correct_answers', true);
    }

    /**
     * Set whether correct answers should be revealed to students.
     */
    public function setShowCorrectAnswersAttribute(bool $value): void
    {
        $this->setSettingValue('show_correct_answers', $value);
    }

    /**
     * Get whether late submission is allowed.
     */
    public function getAllowLateSubmissionAttribute(): bool
    {
        return $this->getBooleanSetting('allow_late_submission', false);
    }

    /**
     * Set whether late submission is allowed.
     */
    public function setAllowLateSubmissionAttribute(bool $value): void
    {
        $this->setSettingValue('allow_late_submission', $value);
    }

    /**
     * Check if the assessment is available for students to take.
     *
     * @return array{available: bool, reason: string|null}
     */
    public function getAvailabilityStatus(): array
    {
        if (! $this->is_published) {
            return ['available' => false, 'reason' => 'assessment_not_published'];
        }

        $now = now();

        if ($this->isHomeworkMode()) {
            if ($this->due_date && $now->gt($this->due_date) && ! $this->allow_late_submission) {
                return ['available' => false, 'reason' => 'assessment_due_date_passed'];
            }

            return ['available' => true, 'reason' => null];
        }

        if ($this->scheduled_at && $now->lt($this->scheduled_at)) {
            return ['available' => false, 'reason' => 'assessment_not_started'];
        }

        if ($this->scheduled_at && $this->duration_minutes) {
            $endsAt = $this->scheduled_at->copy()->addMinutes($this->duration_minutes);

            if ($now->gt($endsAt) && ! $this->allow_late_submission) {
                return ['available' => false, 'reason' => 'assessment_ended'];
            }
        }

        return ['available' => true, 'reason' => null];
    }

    /**
     * Check if the assessment is available to take.
     */
    public function isAvailableToTake(): bool
    {
        return $this->getAvailabilityStatus()['available'];
    }

    /**
     * Determine whether the assessment window has fully closed.
     *
     * For homework: due_date has passed.
     * For supervised: scheduled_at + duration_minutes has passed.
     * Edge case: if scheduled_at is set but duration_minutes is null,
     * the assessment is treated as instantaneous — ended when scheduled_at passed.
     */
    public function hasEnded(): bool
    {
        $now = now();

        if ($this->isHomeworkMode()) {
            return $this->due_date !== null && $now->gt($this->due_date);
        }

        if ($this->scheduled_at !== null && $this->duration_minutes === null) {
            return $now->gt($this->scheduled_at);
        }

        $endsAt = $this->ends_at;

        return $endsAt !== null && $now->gt($endsAt);
    }

    /**
     * Appended accessor for serialization to frontend.
     */
    public function getHasEndedAttribute(): bool
    {
        return $this->hasEnded();
    }

    /**
     * Get the end time for this assessment.
     *
     * When duration_minutes is null but scheduled_at is set, the assessment is
     * treated as instantaneous and ends exactly at scheduled_at.
     */
    public function getEndsAtAttribute(): ?\Carbon\Carbon
    {
        if (! $this->scheduled_at) {
            return null;
        }

        if (! $this->duration_minutes) {
            return $this->scheduled_at->copy();
        }

        return $this->scheduled_at->copy()->addMinutes($this->duration_minutes);
    }

    /**
     * Determine from which point in time results can be revealed to students.
     *
     * For supervised assessments an embargo of one hour after the session ends
     * is enforced so that early finishers cannot leak questions to peers who
     * are still taking the exam.
     * For homework assessments there is no embargo: results follow the
     * show_results_immediately / graded_at rules only.
     */
    public function getResultsAvailableAtAttribute(): ?\Carbon\Carbon
    {
        if ($this->isHomeworkMode()) {
            return null;
        }

        $endsAt = $this->ends_at;

        if ($endsAt === null) {
            return null;
        }

        return $endsAt->copy()->addHour();
    }

    /**
     * Check whether the results embargo has lifted for this assessment.
     *
     * For supervised assessments: true only after scheduled_at + duration + 1 hour.
     * For homework assessments: always true (no embargo applies).
     */
    public function isResultsEmbargoLifted(): bool
    {
        if ($this->isHomeworkMode()) {
            return true;
        }

        $availableAt = $this->results_available_at;

        return $availableAt !== null && now()->gte($availableAt);
    }

    /**
     * Check if this assessment uses supervised delivery mode.
     */
    public function isSupervisedMode(): bool
    {
        return $this->delivery_mode === DeliveryMode::Supervised;
    }

    /**
     * Check if this assessment uses homework delivery mode.
     */
    public function isHomeworkMode(): bool
    {
        return $this->delivery_mode === DeliveryMode::Homework;
    }
}
