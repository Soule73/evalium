<?php

namespace App\Models;

use App\Traits\HasAcademicYearScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Semester model representing a period within an academic year.
 * Typically 2 semesters per academic year.
 */
class Semester extends Model
{
    use HasAcademicYearScope, HasFactory;

    protected $fillable = [
        'academic_year_id',
        'name',
        'start_date',
        'end_date',
        'order_number',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'order_number' => 'integer',
    ];

    /**
     * Get the academic year this semester belongs to.
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the class subjects for this semester.
     */
    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
    }
}
