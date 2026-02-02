<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Question
 *
 * Represents a question entity in the application.
 *
 * @property int $id The unique identifier for the question.
 * @property string $content The text of the question.
 * @property string $type The type of the question Available(‘multiple’, ‘boolean’, ‘one_choice’, ‘text’).
 * @property int $points The points assigned to the question.
 * @property int $exam_id The ID of the related exam.
 * @property int $order_index The order index of the question within the exam.
 * @property \Illuminate\Support\Carbon|null $created_at The date and time when the question was created.
 * @property \Illuminate\Support\Carbon|null $updated_at The date and time when the question was last updated.
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Question newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Question newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Question query()
 *
 * @property-read \App\Models\Exam $exam The exam to which the question belongs.
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Choice> $choices The choices associated with the question (for multiple choice questions).
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Answer> $answers The answers provided by students for this question.
 *
 * @mixin \Eloquent
 */
class Question extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'exam_id',
        'content',
        'type',
        'points',
        'order_index',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'points' => 1,
        'order_index' => 1,
    ];

    /**
     * Get the assessment that owns the question.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Assessment, self>
     */
    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /**
     * LEGACY: Get the exam that owns the question.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Exam, self>
     * @deprecated Use assessment() instead
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    /**
     * Get the choices associated with the question (for multiple choice questions).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Choice>
     */
    public function choices(): HasMany
    {
        return $this->hasMany(Choice::class)->orderBy('order_index');
    }

    /**
     * Get the answers provided by students for this question.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Answer>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('ordered', function ($builder) {
            $builder->orderBy('order_index');
        });
    }
}
