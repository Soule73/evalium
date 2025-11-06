<?php

namespace Tests\Unit\Validation\Strategies;

use App\Strategies\Validation\MultipleChoiceValidationStrategy;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MultipleChoiceValidationStrategyTest extends TestCase
{
    private MultipleChoiceValidationStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->strategy = new MultipleChoiceValidationStrategy;
    }

    #[Test]
    public function it_supports_multiple_question_type()
    {
        $this->assertTrue($this->strategy->supports('multiple'));
        $this->assertFalse($this->strategy->supports('one_choice'));
        $this->assertFalse($this->strategy->supports('boolean'));
        $this->assertFalse($this->strategy->supports('text'));
    }

    #[Test]
    public function it_validates_minimum_choices_requirement()
    {
        $validator = Validator::make([], []);

        $question = [
            'type' => 'multiple',
            'choices' => [
                ['content' => 'Choice 1', 'is_correct' => false],
            ],
        ];

        $this->strategy->validate($validator, $question, 0);

        $this->assertTrue($validator->errors()->has('questions.0.choices'));
        $this->assertNotEmpty($validator->errors()->first('questions.0.choices'));
    }

    #[Test]
    public function it_validates_minimum_correct_answers()
    {
        $validator = Validator::make([], []);

        $question = [
            'type' => 'multiple',
            'choices' => [
                ['content' => 'Choice 1', 'is_correct' => true],
                ['content' => 'Choice 2', 'is_correct' => false],
            ],
        ];

        $this->strategy->validate($validator, $question, 0);

        $this->assertTrue($validator->errors()->has('questions.0.choices'));
        $this->assertNotEmpty($validator->errors()->first('questions.0.choices'));
    }

    #[Test]
    public function it_passes_validation_with_valid_data()
    {
        $validator = Validator::make([], []);

        $question = [
            'type' => 'multiple',
            'choices' => [
                ['content' => 'Choice 1', 'is_correct' => true],
                ['content' => 'Choice 2', 'is_correct' => true],
                ['content' => 'Choice 3', 'is_correct' => false],
            ],
        ];

        $this->strategy->validate($validator, $question, 0);

        $this->assertFalse($validator->errors()->has('questions.0.choices'));
    }

    #[Test]
    public function it_adds_error_when_choices_not_array()
    {
        $validator = Validator::make([], []);

        $question = [
            'type' => 'multiple',
            'choices' => 'not an array',
        ];

        $this->strategy->validate($validator, $question, 0);

        $this->assertTrue($validator->errors()->has('questions.0.choices'));
    }
}
