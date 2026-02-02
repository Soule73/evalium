<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Assessment model representing an evaluation (devoir, examen, tp, controle, projet).
 * Replaces the old Exam model with enhanced type and coefficient support.
 */
class Assessment extends Model
{
    use HasFactory, SoftDeletes;

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

    /**
     * Get the class subject (teaching) this assessment belongs to.
     */
    public function classSubject(): BelongsTo
    {
        return $this->belongsTo(ClassSubject::class);
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
}
