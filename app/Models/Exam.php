<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Exam
 *
 * Represents an exam entity in the application.
 *
 * @property int $id The unique identifier for the exam.
 * @property string $title The title of the exam.
 * @property string|null $description A description of the exam.
 * @property int|null $duration The duration of the exam in minutes.
 * @property \Illuminate\Support\Carbon|null $start_time The start time of the exam.
 * @property \Illuminate\Support\Carbon|null $end_time The end time of the exam.
 * @property bool $is_active Indicates if the exam is active.
 * @property int $teacher_id The ID of the teacher who created the exam.
 * @property \Illuminate\Support\Carbon|null $created_at The date and time when the exam was created.
 * @property \Illuminate\Support\Carbon|null $updated_at The date and time when the exam was last updated.
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Question newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Question newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Question query()
 *
 * @property-read \App\Models\User $teacher The teacher who created the exam.
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Question> $questions The questions associated with the exam.
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ExamAssignment> $assignments The assignments associated with the exam.
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Answer> $answers The answers associated with the exam (via questions).
 * @property-read int $unique_participants_count The count of unique participants who have taken the exam.
 * @property-read int $total_points The total points possible for the exam.
 *
 * @mixin \Eloquent
 */
class Exam extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'duration',
        'start_time',
        'end_time',
        'is_active',
        'teacher_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the teacher that owns the exam.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, self>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the questions associated with the exam.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Question>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order_index');
    }

    /**
     * Get the assignments associated with the exam.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\ExamAssignment>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(ExamAssignment::class);
    }

    /**
     * Get all answers associated with the exam through questions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough<\App\Models\Answer>
     */
    public function answers(): HasManyThrough
    {
        return $this->hasManyThrough(Answer::class, Question::class);
    }

    /**
     * Get the groups to which this exam is assigned.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Group>
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'exam_group')
            ->withPivot(['assigned_at', 'assigned_by'])
            ->withTimestamps();
    }

    /**
     * Get the count of unique participants for the exam.
     *
     * This is an accessor for the `unique_participants_count` attribute.
     *
     * @return int The number of unique participants.
     */
    public function getUniqueParticipantsCountAttribute(): int
    {
        return $this->assignments()->distinct('student_id')->count('student_id');
    }

    /**
     * Get the total points for the exam.
     *
     * This is an accessor for the `total_points` attribute.
     *
     * @return int The sum of all points assigned to the exam.
     */
    public function getTotalPointsAttribute(): int
    {
        return $this->questions
            ->whereNotNull('points')
            ->sum('points');
    }
}
