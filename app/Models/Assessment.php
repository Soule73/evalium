<?php

namespace App\Models;

use App\Traits\HasAcademicYearThroughClass;
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
    use HasAcademicYearThroughClass, HasFactory, SoftDeletes;

    protected $fillable = [
        'class_subject_id',
        'teacher_id',
        'title',
        'description',
        'type',
        'coefficient',
        'duration_minutes',
        'scheduled_at',
        'settings',
    ];

    protected $casts = [
        'coefficient' => 'decimal:2',
        'duration_minutes' => 'integer',
        'scheduled_at' => 'datetime',
        'settings' => 'array',
    ];

    protected $appends = [
        'duration',
        'is_published',
        'shuffle_questions',
        'show_results_immediately',
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
     * Get the answers for this assessment.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class, 'assessment_id');
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
     * Get whether the assessment is published.
     */
    public function getIsPublishedAttribute(): bool
    {
        $settings = $this->settings ?? [];

        return $settings['is_published'] ?? false;
    }

    /**
     * Set whether the assessment is published.
     */
    public function setIsPublishedAttribute(bool $value): void
    {
        $settings = $this->settings ?? [];
        $settings['is_published'] = $value;
        $this->settings = $settings;
    }

    /**
     * Get whether questions should be shuffled.
     */
    public function getShuffleQuestionsAttribute(): bool
    {
        $settings = $this->settings ?? [];

        return $settings['shuffle_questions'] ?? false;
    }

    /**
     * Set whether questions should be shuffled.
     */
    public function setShuffleQuestionsAttribute(bool $value): void
    {
        $settings = $this->settings ?? [];
        $settings['shuffle_questions'] = $value;
        $this->settings = $settings;
    }

    /**
     * Get whether results should be shown immediately after submission.
     */
    public function getShowResultsImmediatelyAttribute(): bool
    {
        $settings = $this->settings ?? [];

        return $settings['show_results_immediately'] ?? true;
    }

    /**
     * Set whether results should be shown immediately after submission.
     */
    public function setShowResultsImmediatelyAttribute(bool $value): void
    {
        $settings = $this->settings ?? [];
        $settings['show_results_immediately'] = $value;
        $this->settings = $settings;
    }

    /**
     * Get whether late submission is allowed.
     */
    public function getAllowLateSubmissionAttribute(): bool
    {
        $settings = $this->settings ?? [];

        return $settings['allow_late_submission'] ?? false;
    }

    /**
     * Set whether late submission is allowed.
     */
    public function setAllowLateSubmissionAttribute(bool $value): void
    {
        $settings = $this->settings ?? [];
        $settings['allow_late_submission'] = $value;
        $this->settings = $settings;
    }

    /**
     * Get whether to show one question per page.
     */
    public function getOneQuestionPerPageAttribute(): bool
    {
        $settings = $this->settings ?? [];

        return $settings['one_question_per_page'] ?? false;
    }

    /**
     * Set whether to show one question per page.
     */
    public function setOneQuestionPerPageAttribute(bool $value): void
    {
        $settings = $this->settings ?? [];
        $settings['one_question_per_page'] = $value;
        $this->settings = $settings;
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
     * Get the end time for this assessment.
     */
    public function getEndsAtAttribute(): ?\Carbon\Carbon
    {
        if (! $this->scheduled_at || ! $this->duration_minutes) {
            return null;
        }

        return $this->scheduled_at->copy()->addMinutes($this->duration_minutes);
    }
}
