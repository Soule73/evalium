<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * Subject model representing a course/subject (e.g., Math, Physics).
 * Subjects are linked to levels (Math L1 is different from Math M1).
 *
 * Performance: Auto-invalidates caches on changes
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

    protected static function booted(): void
    {
        static::created(fn () => Cache::forget('subjects:all'));
        static::updated(fn () => Cache::forget('subjects:all'));
        static::deleted(fn () => Cache::forget('subjects:all'));
    }

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

    /**
     * Check if subject can be deleted (no class assignments).
     */
    public function canBeDeleted(): bool
    {
        return ! $this->classSubjects()->exists();
    }
}
