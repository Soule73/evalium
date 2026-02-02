<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ClassModel representing a specific class instance for an academic year.
 * Replaces the old 'groups' concept with proper academic year binding.
 */
class ClassModel extends Model
{
    use HasFactory;
    protected $table = 'classes';

    protected $fillable = [
        'academic_year_id',
        'level_id',
        'name',
        'description',
        'max_students',
    ];

    protected $casts = [
        'max_students' => 'integer',
    ];

    protected $appends = [
        'display_name',
    ];

    /**
     * Get the academic year this class belongs to.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the level (e.g., M1, L1) this class belongs to.
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Get the enrollments for this class.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'class_id');
    }

    /**
     * Get the class subjects (teaching assignments) for this class.
     */
    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'class_id');
    }

    /**
     * Get the students enrolled in this class.
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments', 'class_id', 'student_id')
            ->withPivot('enrolled_at', 'withdrawn_at', 'status')
            ->withTimestamps();
    }

    /**
     * Get the display name (e.g., "M1-A (2025/2026)").
     */
    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->name . ' (' . $this->academicYear->name . ')'
        );
    }
}
