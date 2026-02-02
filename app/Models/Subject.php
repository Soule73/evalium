<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Subject model representing a course/subject (e.g., Math, Physics).
 * Subjects are linked to levels (Math L1 is different from Math M1).
 */
class Subject extends Model
{
    use HasFactory;
    protected $fillable = [
        'level_id',
        'name',
        'code',
        'description',
    ];

    /**
     * Get the level this subject belongs to.
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Get the class subjects (teaching assignments) for this subject.
     */
    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class);
    }
}
