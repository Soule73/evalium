<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Traits;

use App\Http\Requests\Traits\ClassValidationRules;
use App\Models\Level;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ClassValidationRulesTest extends TestCase
{
    use RefreshDatabase;

    private FormRequest $request;

    private Level $level;

    protected function setUp(): void
    {
        parent::setUp();

        $this->level = Level::factory()->create();

        $this->request = new class extends FormRequest
        {
            use ClassValidationRules;

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

        $this->assertArrayHasKey('level_id', $rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('description', $rules);
        $this->assertArrayHasKey('max_students', $rules);
        $this->assertArrayNotHasKey('academic_year_id', $rules);
    }

    public function test_get_class_validation_rules_name_has_base_constraints(): void
    {
        $rules = $this->request->rules();

        $this->assertIsArray($rules['name']);
        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('max:255', $rules['name']);
    }

    public function test_get_class_validation_rules_description_has_max_length(): void
    {
        $rules = $this->request->rules();

        $this->assertIsArray($rules['description']);
        $this->assertContains('max:1000', $rules['description']);
    }

    public function test_get_class_validation_messages_returns_translated_messages(): void
    {
        $messages = $this->request->messages();

        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);

        $this->assertArrayHasKey('level_id.required', $messages);
        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('name.unique', $messages);
        $this->assertArrayHasKey('max_students.min', $messages);
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $rules = $this->request->rules();

        $validator = Validator::make([
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
        $this->assertArrayHasKey('level_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_validation_fails_with_invalid_max_students(): void
    {
        $rules = $this->request->rules();

        $validator = Validator::make([
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
            'level_id' => $this->level->id,
            'name' => 'Test Class',
        ], $rules);

        $this->assertFalse($validator->fails());
    }
}
