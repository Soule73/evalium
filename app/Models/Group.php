<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

/**
 * Modèle représentant un groupe (classe) d'étudiants
 * 
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $level_id
 * @property Level|null $level
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property int $max_students
 * @property bool $is_active
 * @property string|null $academic_year
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Group extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'level_id',
        'start_date',
        'end_date',
        'max_students',
        'is_active',
        'academic_year',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'display_name',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'max_students' => 'integer',
    ];

    /**
     * Événements du modèle pour gérer le cache
     */
    protected static function booted(): void
    {
        // Invalider le cache des groupes quand un groupe change
        static::created(fn() => Cache::forget('groups_active_with_levels'));
        static::updated(fn() => Cache::forget('groups_active_with_levels'));
        static::deleted(fn() => Cache::forget('groups_active_with_levels'));
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_student', 'group_id', 'student_id')
            ->withPivot(['enrolled_at', 'left_at', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Étudiants actuellement actifs dans ce groupe
     */
    public function activeStudents(): BelongsToMany
    {
        return $this->students()->wherePivot('is_active', true);
    }

    /**
     * Get the exams assigned to this group.
     */
    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_group')
            ->withPivot(['assigned_at', 'assigned_by'])
            ->withTimestamps();
    }

    /**
     * Vérifier si le groupe est actuellement en cours
     */
    public function isCurrentlyActive(): bool
    {
        $now = Carbon::now();
        return $this->is_active
            && $this->start_date <= $now
            && $this->end_date >= $now;
    }

    /**
     * Obtenir le nombre d'étudiants actifs
     */
    public function getActiveStudentsCountAttribute(): int
    {
        return $this->activeStudents()->count();
    }

    /**
     * Vérifier s'il y a de la place pour de nouveaux étudiants
     */
    public function hasAvailableSlots(): bool
    {
        return $this->active_students_count < $this->max_students;
    }

    /**
     * Scope pour les groupes actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour filtrer les groupes de l'année académique actuelle
     */
    public function scopeCurrentAcademicYear($query)
    {
        $currentYear = Carbon::now()->year;
        $academicYear = Carbon::now()->month >= 9
            ? "{$currentYear}-" . ($currentYear + 1)
            : ($currentYear - 1) . "-{$currentYear}";

        return $query->where('academic_year', $academicYear);
    }

    protected function levelName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->level?->name ?? 'Non défini'
        );
    }

    /**
     * Obtenir le nom complet du groupe (Level + Année académique)
     */
    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->level?->name . ' - ' . $this->academic_year
        );
    }

    /**
     * Obtenir la description du groupe (depuis le niveau)
     */
    protected function description(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->level?->description
        );
    }
}
