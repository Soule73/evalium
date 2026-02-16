# Score Validation Strategies

This directory contains validation strategies for exam score validation using the Strategy Pattern.

## Architecture

The Score Validation Strategy pattern provides a flexible way to validate different aspects of exam scoring without cluttering the Request classes with complex validation logic.

## Components

### 1. `ScoreValidationStrategy` (Interface)
Base interface that all validation strategies must implement.

### 2. Concrete Strategies

#### `QuestionExistsInExamValidationStrategy`
- **Type**: `question_exists_in_exam`
- **Purpose**: Validates that each question in the scores array exists in the specified exam
- **Use Case**: Batch score validation (SaveStudentReviewRequest)

#### `ScoreNotExceedsMaxValidationStrategy`
- **Type**: `score_not_exceeds_max`
- **Purpose**: Validates that scores don't exceed the maximum points for each question
- **Use Case**: Batch score validation (SaveStudentReviewRequest)

#### `SingleQuestionExistsValidationStrategy`
- **Type**: `single_question_exists`
- **Purpose**: Validates that a single question exists in an exam and score doesn't exceed max
- **Use Case**: Single score update (UpdateScoreRequest)

#### `StudentAssignmentValidationStrategy`
- **Type**: `student_assignment`
- **Purpose**: Validates that:
  - The user is a student
  - The student is assigned to the exam
  - The student has answered the question
- **Use Case**: Single score update (UpdateScoreRequest)

### 3. `ScoreValidationContext`
Context class that manages and executes validation strategies.

## Usage

### In SaveStudentReviewRequest

```php
public function withValidator($validator): void
{
    $validator->after(function ($validator) {
        $data = $validator->getData();
        $exam = request()->route()->parameter('exam');

        if (!$exam || !isset($data['scores'])) {
            return;
        }

        $validationContext = new ScoreValidationContext();
        $validationContext->validate(
            $validator,
            $data,
            ['question_exists_in_exam', 'score_not_exceeds_max'],
            ['exam' => $exam]
        );
    });
}
```

### In UpdateScoreRequest

```php
public function withValidator($validator): void
{
    $validator->after(function ($validator) {
        $data = $validator->getData();

        $validationContext = new ScoreValidationContext();
        $validationContext->validate(
            $validator,
            $data,
            ['single_question_exists', 'student_assignment']
        );
    });
}
```

## Benefits

1. **Single Responsibility**: Each strategy handles one specific validation concern
2. **Open/Closed**: Easy to add new validation strategies without modifying existing code
3. **Reusability**: Strategies can be reused across different Request classes
4. **Testability**: Each strategy can be tested independently
5. **Readability**: Request classes are cleaner and easier to understand

## Adding New Strategies

1. Create a new class implementing `ScoreValidationStrategy`
2. Implement the `validate()` and `supports()` methods
3. Register it in `ScoreValidationContext::registerDefaultStrategies()`

Example:

```php
class MyCustomValidationStrategy implements ScoreValidationStrategy
{
    public function validate(Validator $validator, array $data, array $context = []): void
    {
        // Your validation logic
    }

    public function supports(string $validationType): bool
    {
        return $validationType === 'my_custom_validation';
    }
}
```

Then register it:

```php
private function registerDefaultStrategies(): void
{
    $this->registerStrategy(new QuestionExistsInExamValidationStrategy());
    $this->registerStrategy(new ScoreNotExceedsMaxValidationStrategy());
    $this->registerStrategy(new SingleQuestionExistsValidationStrategy());
    $this->registerStrategy(new StudentAssignmentValidationStrategy());
    $this->registerStrategy(new MyCustomValidationStrategy()); // New strategy
}
```
