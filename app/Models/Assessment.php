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
            'settings' => 'array',
        ];
    }

    protected $appends = [
        'duration',
        'has_ended',
        'shuffle_questions',
        'show_results_immediately',
        'show_correct_answers',
        'allow_late_submission',
        'one_question_per_page',
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
     * Get whether results should be shown immediately after submission.
     */
    public function getShowResultsImmediatelyAttribute(): bool
    {
        return $this->getBooleanSetting('show_results_immediately', true);
    }

    /**
     * Set whether results should be shown immediately after submission.
     */
    public function setShowResultsImmediatelyAttribute(bool $value): void
    {
        $this->setSettingValue('show_results_immediately', $value);
    }

    /**
     * Get whether correct answers should be revealed to students.
     */
    public function getShowCorrectAnswersAttribute(): bool
    {
        return $this->getBooleanSetting('show_correct_answers', false);
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
     * Get whether to show one question per page.
     */
    public function getOneQuestionPerPageAttribute(): bool
    {
        return $this->getBooleanSetting('one_question_per_page', false);
    }

    /**
     * Set whether to show one question per page.
     */
    public function setOneQuestionPerPageAttribute(bool $value): void
    {
        $this->setSettingValue('one_question_per_page', $value);
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
            $endsAt = $this->scheduled_at->addMinutes($this->duration_minutes);

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
     */
    public function hasEnded(): bool
    {
        $now = now();

        if ($this->isHomeworkMode()) {
            return $this->due_date !== null && $now->gt($this->due_date);
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
     * Determine whether grading is allowed for a given assignment.
     *
     * - submitted_at set           → allowed (normal grading flow)
     * - submitted_at null + ended  → allowed with warning (exception: give 0 or final score)
     * - submitted_at null + active → blocked (student still has time)
     *
     * @return array{allowed: bool, reason: string, warning: string|null}
     */
    public function getGradingState(AssessmentAssignment $assignment): array
    {
        if ($assignment->submitted_at !== null) {
            return [
                'allowed' => true,
                'reason' => 'submitted',
                'warning' => null,
            ];
        }

        if ($this->hasEnded()) {
            return [
                'allowed' => true,
                'reason' => 'not_submitted_assessment_ended',
                'warning' => 'grading_without_submission',
            ];
        }

        return [
            'allowed' => false,
            'reason' => 'assessment_still_running',
            'warning' => null,
        ];
    }

    /**
     * Get the end time for this assessment.
     */
    public function getEndsAtAttribute(): ?\Carbon\Carbon
    {
        if (! $this->scheduled_at || ! $this->duration_minutes) {
            return null;
        }

        return $this->scheduled_at->copy()->addMinutes($this->duration_minutes);
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
