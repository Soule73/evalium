<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ClassSubject model - THE MOST IMPORTANT TABLE.
 * Links class + subject + teacher + semester with coefficient.
 * Supports teacher historization via valid_from/valid_to.
 */
class ClassSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'subject_id',
        'teacher_id',
        'semester_id',
        'coefficient',
        'valid_from',
        'valid_to',
    ];

    protected $casts = [
        'coefficient' => 'decimal:2',
        'valid_from' => 'date',
        'valid_to' => 'date',
    ];

    /**
     * Get the class this teaching belongs to.
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Get the subject being taught.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Scope to filter class subjects by academic year (through class).
     */
    public function scopeForAcademicYear($query, int $academicYearId)
    {
        return $query->whereHas('class', function ($q) use ($academicYearId) {
            $q->where('academic_year_id', $academicYearId);
        });
    }

    /**
     * Get the teacher teaching this subject.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the semester this teaching belongs to.
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * Get the assessments for this teaching.
     */
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class);
    }

    /**
     * Scope to get only active teaching assignments (valid_to is NULL).
     */
    public function scopeActive($query)
    {
        return $query->whereNull('valid_to');
    }

    /**
     * Scope to get current teaching assignments (active in current academic year).
     */
    public function scopeCurrent($query)
    {
        return $query->whereNull('valid_to')
            ->whereHas('class.academicYear', function ($q) {
                $q->where('is_current', true);
            });
    }
}
