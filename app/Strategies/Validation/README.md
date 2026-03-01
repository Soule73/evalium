# Question Validation Strategy Pattern

## Overview

This directory contains the implementation of the **Strategy Pattern** for exam question validation. This architecture allows flexible, maintainable, and extensible validation by separating the validation logic for each question type.

## Goals

- **Separation of concerns**: Each question type has its own validation strategy
- **Extensibility**: Easy to add new question types without modifying existing code
- **Maintainability**: Cleaner, organized, and testable code
- **Reusability**: Strategies can be used in different contexts

## File Structure

```
app/Strategies/Validation/
├── QuestionValidationStrategy.php           # Interface defining the contract
├── QuestionValidationContext.php            # Context/Factory that manages strategies
├── MultipleChoiceValidationStrategy.php     # Strategy for multiple choice questions
├── SingleChoiceValidationStrategy.php       # Strategy for single choice/boolean questions
└── TextQuestionValidationStrategy.php       # Strategy for text-type questions
```

## How It Works

### 1. `QuestionValidationStrategy` Interface

Defines the contract that all strategies must implement:

```php
interface QuestionValidationStrategy
{
    public function validate(Validator $validator, array $question, int $index): void;
    public function supports(string $questionType): bool;
}
```

### 2. Concrete Strategies

Each strategy implements validation logic specific to a question type:

#### **MultipleChoiceValidationStrategy**
- Verifies there are at least 2 choices
- Verifies at least 2 choices are marked as correct

#### **SingleChoiceValidationStrategy**
- Verifies there are at least 2 choices
- Verifies exactly 1 choice is marked as correct
- Supports `one_choice` and `boolean` types

#### **TextQuestionValidationStrategy**
- No additional validation (free text questions)
- Included for completeness and future extensibility

### 3. `QuestionValidationContext`

Acts as a **Factory** and a **Facade**:
- Registers all available strategies
- Selects the appropriate strategy for a question type
- Delegates validation to the selected strategy

## Usage

### In Form Requests

```php
use App\Strategies\Validation\QuestionValidationContext;

public function withValidator(Validator $validator): void
{
    $validator->after(function ($validator) {
        $data = $validator->getData();
        $questions = $data['questions'] ?? [];

        $validationContext = new QuestionValidationContext();
        $validationContext->validateQuestions($validator, $questions);
    });
}
```

## Adding a New Question Type

To add a new question type (e.g., `rating`, `file_upload`), follow these steps:

### 1. Create a new strategy

```php
<?php

namespace App\Strategies\Validation;

use Illuminate\Validation\Validator;

class RatingQuestionValidationStrategy implements QuestionValidationStrategy
{
    public function validate(Validator $validator, array $question, int $index): void
    {
        if (!isset($question['min_rating']) || !isset($question['max_rating'])) {
            $validator->errors()->add(
                "questions.{$index}.rating",
                "Min and max values are required for rating questions."
            );
        }
    }

    public function supports(string $questionType): bool
    {
        return $questionType === 'rating';
    }
}
```

### 2. Register the strategy

In `QuestionValidationContext::registerDefaultStrategies()`:

```php
private function registerDefaultStrategies(): void
{
    $this->registerStrategy(new MultipleChoiceValidationStrategy());
    $this->registerStrategy(new SingleChoiceValidationStrategy());
    $this->registerStrategy(new TextQuestionValidationStrategy());
    $this->registerStrategy(new RatingQuestionValidationStrategy()); // New strategy
}
```

**That's it!** No changes needed in existing Form Requests.

## Tests

Each strategy can be tested independently:

```php
public function test_multiple_choice_validates_minimum_correct_answers()
{
    $strategy = new MultipleChoiceValidationStrategy();
    $validator = Validator::make([], []);

    $question = [
        'type' => 'multiple',
        'choices' => [
            ['is_correct' => true],
            ['is_correct' => false]
        ]
    ];

    $strategy->validate($validator, $question, 0);

    $this->assertTrue($validator->errors()->has('questions.0.choices'));
}
```

## UML Diagram

```
+---------------------------------+
|  QuestionValidationStrategy     |
|  <<interface>>                  |
+---------------------------------+
| + validate(...)                 |
| + supports(string): bool        |
+---------------------------------+
           ^
           | implements
           |
    +------+------+--------------+-----------------+
    |             |              |                 |
+---+-------+ +---+-------+ +---+-------+ +------+------+
| Multiple  | |  Single   | |   Text    | |   Future    |
|  Choice   | |  Choice   | |  Question | |  Strategies |
| Strategy  | | Strategy  | | Strategy  | |    ...      |
+-----------+ +-----------+ +-----------+ +-------------+
```

## Benefits of This Implementation

1. **Open/Closed Principle**: Open for extension, closed for modification
2. **Single Responsibility**: Each class has a single responsibility
3. **Dependency Inversion**: Depends on abstractions, not concrete implementations
4. **Testability**: Each strategy can be tested independently
5. **Readability**: Clearer, self-documenting code
6. **Reusability**: Strategies reusable in other contexts

## Technical Notes

- Strategies are **stateless**: no shared state between validations
- The context is **lightweight**: inexpensive to create per validation
- Error messages are **internationalized** via `__()`
- Compatible with the existing Laravel validation system

## References

- [Design Patterns: Strategy](https://refactoring.guru/design-patterns/strategy)
- [Laravel Validation Documentation](https://laravel.com/docs/validation)
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID)
