<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Choice
 *
 * Represents a selectable option for a question in the application.
 *
 * @property int $id The unique identifier for the choice.
 * @property string $content The display text of the choice.
 * @property int $question_id The ID of the related question.
 * @property bool $is_correct Indicates if this choice is the correct answer.
 * @property int $order_index The display order of the choice within its question.
 * @property \Illuminate\Support\Carbon|null $created_at Timestamp when the choice was created.
 * @property \Illuminate\Support\Carbon|null $updated_at Timestamp when the choice was last updated.
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Choice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Choice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Choice query()
 *
 * @property-read \App\Models\Question $question The question to which the choice belongs.
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Answer> $answers The answers associated with this choice.
 *
 * @mixin \Eloquent
 */
class Choice extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'question_id',
        'content',
        'is_correct',
        'order_index',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_correct' => 'boolean',
            'order_index' => 'integer',
        ];
    }

    /**
     * Get the question that this choice belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Question, self>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Get the answers associated with this choice.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Answer>
     */
    public function answers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
