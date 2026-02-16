<?php

namespace Tests\Unit\Validation\Strategies;

use App\Strategies\Validation\QuestionValidationContext;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuestionValidationContextTest extends TestCase
{
    private QuestionValidationContext $context;

    protected function setUp(): void
    {
        parent::setUp();
        $this->context = new QuestionValidationContext;
    }

    #[Test]
    public function it_validates_multiple_choice_questions()
    {
        $validator = Validator::make([], []);

        $questions = [
            [
                'type' => 'multiple',
                'choices' => [
                    ['is_correct' => true],
                    ['is_correct' => false],
                ],
            ],
        ];

        $this->context->validateQuestions($validator, $questions);

        $this->assertTrue($validator->errors()->has('questions.0.choices'));
    }

    #[Test]
    public function it_validates_single_choice_questions()
    {
        $validator = Validator::make([], []);

        $questions = [
            [
                'type' => 'one_choice',
                'choices' => [
                    ['is_correct' => true],
                    ['is_correct' => true],
                ],
            ],
        ];

        $this->context->validateQuestions($validator, $questions);

        $this->assertTrue($validator->errors()->has('questions.0.choices'));
    }

    #[Test]
    public function it_validates_boolean_questions()
    {
        $validator = Validator::make([], []);

        $questions = [
            [
                'type' => 'boolean',
                'choices' => [
                    ['content' => 'Vrai', 'is_correct' => false],
                    ['content' => 'Faux', 'is_correct' => false],
                ],
            ],
        ];

        $this->context->validateQuestions($validator, $questions);

        $this->assertTrue($validator->errors()->has('questions.0.choices'));
    }

    #[Test]
    public function it_does_not_validate_text_questions()
    {
        $validator = Validator::make([], []);

        $questions = [
            [
                'type' => 'text',
                'content' => 'What is your answer?',
            ],
        ];

        $this->context->validateQuestions($validator, $questions);

        $this->assertFalse($validator->errors()->any());
    }

    #[Test]
    public function it_validates_multiple_questions_at_once()
    {
        $validator = Validator::make([], []);

        $questions = [
            [
                'type' => 'multiple',
                'choices' => [
                    ['is_correct' => true],
                    ['is_correct' => true],
                    ['is_correct' => false],
                ],
            ],
            [
                'type' => 'one_choice',
                'choices' => [
                    ['is_correct' => false],
                    ['is_correct' => false],
                ],
            ],
            [
                'type' => 'text',
                'content' => 'Text question',
            ],
        ];

        $this->context->validateQuestions($validator, $questions);

        // La première question est valide (2+ correct)
        $this->assertFalse($validator->errors()->has('questions.0.choices'));

        // La deuxième question est invalide (0 correct, besoin de 1)
        $this->assertTrue($validator->errors()->has('questions.1.choices'));

        // La troisième question (text) n'a pas d'erreur
        $this->assertFalse($validator->errors()->has('questions.2'));
    }

    #[Test]
    public function it_handles_questions_without_type()
    {
        $validator = Validator::make([], []);

        $questions = [
            [
                'content' => 'Question without type',
            ],
        ];

        $this->context->validateQuestions($validator, $questions);

        // Ne devrait pas générer d'erreur car aucune stratégie ne correspond
        $this->assertFalse($validator->errors()->any());
    }
}
