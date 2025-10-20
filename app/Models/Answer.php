<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Answer
 *
 * Represents an answer entity in the application.
 *
 * @property int $id The unique identifier for the answer.
 * @property int $assignment_id The ID of the related exam assignment(student).
 * @property int $question_id The ID of the related question.
 * @property int|null $choice_id The ID of the selected choice (if applicable).
 * @property string|null $answer_text The text of the answer (for text-based questions).
 * @property float|null $score The score obtained for this answer, if applicable.
 * @property string|null $feedback The teacher's feedback for this answer.
 * @property \Carbon\Carbon|null $created_at The date and time when the answer was created.
 * @property \Carbon\Carbon|null $updated_at The date and time when the answer was last updated.
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Answer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Answer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Answer query()
 *
 * @mixin \Eloquent
 */
class Answer extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'assignment_id',
        'question_id',
        'choice_id',
        'answer_text',
        'score',
        'feedback',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function cast(): array
    {
        return [
            'score' => 'float',
        ];
    }

    /**
     * Get the assignment that this answer belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Assignment, self>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ExamAssignment::class);
    }

    /**
     * Get the question that this answer belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Question, self>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Get the choice that this answer belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Choice, self>
     */
    public function choice(): BelongsTo
    {
        return $this->belongsTo(Choice::class);
    }
}
