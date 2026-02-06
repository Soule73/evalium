<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Traits;

use App\Http\Requests\Traits\ClassValidationRules;
use App\Models\AcademicYear;
use App\Models\Level;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ClassValidationRulesTest extends TestCase
{
    use RefreshDatabase;

    private FormRequest $request;

    private AcademicYear $academicYear;

    private Level $level;

    protected function setUp(): void
    {
        parent::setUp();

        $this->academicYear = AcademicYear::factory()->create();
        $this->level = Level::factory()->create();

        $this->request = new class($this->academicYear->id, $this->level->id) extends FormRequest
        {
            use ClassValidationRules;

            public function __construct(
                private int $academicYearId,
                private int $levelId
            ) {
                parent::__construct();
            }

            public function input($key = null, $default = null)
            {
                return match ($key) {
                    'academic_year_id' => $this->academicYearId,
                    'level_id' => $this->levelId,
                    default => $default,
                };
            }

            public function rules(): array
            {
                return $this->getClassValidationRules();
            }

            public function messages(): array
            {
                return $this->getClassValidationMessages();
            }
        };
    }

    public function test_get_class_validation_rules_returns_base_rules(): void
    {
        $rules = $this->request->rules();

        $this->assertArrayHasKey('academic_year_id', $rules);
        $this->assertArrayHasKey('level_id', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('max_students', $rules);
    }

    public function test_get_class_validation_rules_includes_unique_constraint(): void
    {
        $rules = $this->request->rules();

        $this->assertIsArray($rules['name']);
        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('max:255', $rules['name']);

        $hasUniqueRule = false;
        foreach ($rules['name'] as $rule) {
            if (is_object($rule) && method_exists($rule, '__toString')) {
                $ruleString = (string) $rule;
                if (str_contains($ruleString, 'unique')) {
                    $hasUniqueRule = true;
                    break;
                }
            }
        }

        $this->assertTrue($hasUniqueRule, 'Name should have unique validation rule');
    }

    public function test_get_class_validation_rules_with_class_id_adds_ignore(): void
    {
        $requestWithId = new class($this->academicYear->id, $this->level->id) extends FormRequest
        {
            use ClassValidationRules;

            public function __construct(
                private int $academicYearId,
                private int $levelId
            ) {
                parent::__construct();
            }

            public function input($key = null, $default = null)
            {
                return match ($key) {
                    'academic_year_id' => $this->academicYearId,
                    'level_id' => $this->levelId,
                    default => $default,
                };
            }

            public function rules(): array
            {
                return $this->getClassValidationRules(classId: 5);
            }
        };

        $rules = $requestWithId->rules();

        $this->assertIsArray($rules['name']);

        $hasIgnore = false;
        foreach ($rules['name'] as $rule) {
            if (is_object($rule) && method_exists($rule, '__toString')) {
                $ruleString = (string) $rule;
                if (str_contains($ruleString, 'unique') && str_contains($ruleString, '5')) {
                    $hasIgnore = true;
                    break;
                }
            }
        }

        $this->assertTrue($hasIgnore, 'Unique rule should ignore class ID 5');
    }

    public function test_get_class_validation_messages_returns_translated_messages(): void
    {
        $messages = $this->request->messages();

        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);

        $this->assertArrayHasKey('academic_year_id.required', $messages);
        $this->assertArrayHasKey('level_id.required', $messages);
        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('name.unique', $messages);
        $this->assertArrayHasKey('max_students.min', $messages);
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $rules = $this->request->rules();

        $validator = Validator::make([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'name' => 'Test Class',
            'description' => 'A test class',
            'max_students' => 30,
        ], $rules);

        $this->assertFalse($validator->fails());
    }

    public function test_validation_fails_without_required_fields(): void
    {
        $rules = $this->request->rules();

        $validator = Validator::make([], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('academic_year_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('level_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_invalid_max_students(): void
    {
        $rules = $this->request->rules();

        $validator = Validator::make([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'name' => 'Test Class',
            'max_students' => 0,
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('max_students', $validator->errors()->toArray());
    }

    public function test_validation_passes_with_nullable_fields(): void
    {
        $rules = $this->request->rules();

        $validator = Validator::make([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'name' => 'Test Class',
        ], $rules);

        $this->assertFalse($validator->fails());
    }
}
