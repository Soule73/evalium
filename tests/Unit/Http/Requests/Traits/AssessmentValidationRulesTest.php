<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Traits;

use App\Http\Requests\Traits\AssessmentValidationRules;
use App\Models\ClassSubject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssessmentValidationRulesTest extends TestCase
{
    use RefreshDatabase;

    private FormRequest $createRequest;

    private FormRequest $updateRequest;

    private ClassSubject $classSubject;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'teacher']);

        $academicYear = \App\Models\AcademicYear::factory()->create(['name' => '2025/2026']);
        $level = \App\Models\Level::factory()->create();
        $subject = \App\Models\Subject::factory()->create();
        $semester = \App\Models\Semester::factory()->create(['academic_year_id' => $academicYear->id]);
        $class = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $academicYear->id,
            'level_id' => $level->id,
        ]);
        $teacher = \App\Models\User::factory()->teacher()->create();

        $this->classSubject = \App\Models\ClassSubject::factory()->create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $semester->id,
        ]);

        $this->createRequest = new class extends FormRequest
        {
            use AssessmentValidationRules;

            public function rules(): array
            {
                return $this->getAssessmentValidationRules(isUpdate: false);
            }

            public function messages(): array
            {
                return $this->getAssessmentValidationMessages(isUpdate: false);
            }
        };

        $this->updateRequest = new class extends FormRequest
        {
            use AssessmentValidationRules;

            public function rules(): array
            {
                return $this->getAssessmentValidationRules(isUpdate: true);
            }

            public function messages(): array
            {
                return $this->getAssessmentValidationMessages(isUpdate: true);
            }
        };
    }

    public function test_get_assessment_validation_rules_for_create_requires_class_subject_id(): void
    {
        $rules = $this->createRequest->rules();

        $this->assertArrayHasKey('class_subject_id', $rules);
        $this->assertContains('required', $rules['class_subject_id']);
    }

    public function test_get_assessment_validation_rules_for_update_no_class_subject_id(): void
    {
        $rules = $this->updateRequest->rules();

        $this->assertArrayNotHasKey('class_subject_id', $rules);
    }

    public function test_get_assessment_validation_rules_for_create_has_required_fields(): void
    {
        $this->createRequest->merge(['delivery_mode' => 'supervised']);
        $rules = $this->createRequest->rules();

        $this->assertContains('required', $rules['title']);
        $this->assertContains('required', $rules['type']);
        $this->assertContains('required', $rules['delivery_mode']);
        $this->assertContains('required', $rules['scheduled_at']);
        $this->assertContains('required', $rules['duration_minutes']);
        $this->assertContains('required', $rules['coefficient']);
    }

    public function test_get_assessment_validation_rules_for_update_has_sometimes_fields(): void
    {
        $this->updateRequest->merge(['delivery_mode' => 'supervised']);
        $rules = $this->updateRequest->rules();

        $this->assertContains('sometimes', $rules['title']);
        $this->assertContains('sometimes', $rules['type']);
        $this->assertContains('sometimes', $rules['delivery_mode']);
        $this->assertContains('sometimes', $rules['scheduled_at']);
        $this->assertContains('sometimes', $rules['duration_minutes']);
        $this->assertContains('sometimes', $rules['coefficient']);
    }

    public function test_get_assessment_validation_rules_includes_question_structure(): void
    {
        $rules = $this->createRequest->rules();

        $this->assertArrayHasKey('questions', $rules);
        $this->assertArrayHasKey('questions.*.content', $rules);
        $this->assertArrayHasKey('questions.*.type', $rules);
        $this->assertArrayHasKey('questions.*.points', $rules);
        $this->assertArrayHasKey('questions.*.order_index', $rules);
        $this->assertArrayHasKey('questions.*.choices', $rules);
        $this->assertArrayHasKey('questions.*.choices.*.content', $rules);
        $this->assertArrayHasKey('questions.*.choices.*.is_correct', $rules);
        $this->assertArrayHasKey('questions.*.choices.*.order_index', $rules);
    }

    public function test_get_assessment_validation_rules_for_update_includes_id_fields(): void
    {
        $rules = $this->updateRequest->rules();

        $this->assertArrayHasKey('questions.*.id', $rules);
        $this->assertArrayHasKey('questions.*.choices.*.id', $rules);
        $this->assertArrayHasKey('deletedQuestionIds', $rules);
        $this->assertArrayHasKey('deletedChoiceIds', $rules);
    }

    public function test_get_assessment_validation_rules_for_create_no_id_fields(): void
    {
        $rules = $this->createRequest->rules();

        $this->assertArrayNotHasKey('questions.*.id', $rules);
        $this->assertArrayNotHasKey('deletedQuestionIds', $rules);
    }

    public function test_get_assessment_validation_messages_for_create_includes_required_messages(): void
    {
        $messages = $this->createRequest->messages();

        $this->assertArrayHasKey('class_subject_id.required', $messages);
        $this->assertArrayHasKey('title.required', $messages);
        $this->assertArrayHasKey('type.required', $messages);
        $this->assertArrayHasKey('scheduled_at.required', $messages);
        $this->assertArrayHasKey('duration_minutes.required', $messages);
        $this->assertArrayHasKey('coefficient.required', $messages);
    }

    public function test_get_assessment_validation_messages_for_update_no_required_messages(): void
    {
        $messages = $this->updateRequest->messages();

        $this->assertArrayNotHasKey('class_subject_id.required', $messages);
        $this->assertArrayNotHasKey('title.required', $messages);
        $this->assertArrayNotHasKey('type.required', $messages);
    }

    public function test_get_assessment_validation_messages_includes_common_messages(): void
    {
        $messages = $this->createRequest->messages();

        $this->assertArrayHasKey('title.string', $messages);
        $this->assertArrayHasKey('type.in', $messages);
        $this->assertArrayHasKey('duration_minutes.min', $messages);
        $this->assertArrayHasKey('coefficient.min', $messages);
    }

    public function test_prepare_assessment_for_validation_transforms_scheduled_date(): void
    {
        $request = new class extends FormRequest
        {
            use AssessmentValidationRules;

            public array $data = [
                'scheduled_date' => '2026-03-15',
            ];

            public function has($key)
            {
                return array_key_exists($key, $this->data);
            }

            public function __get($key)
            {
                return $this->data[$key] ?? null;
            }

            public function merge(array $data)
            {
                $this->data = array_merge($this->data, $data);
            }

            public function exposePrepareForValidation()
            {
                $this->prepareAssessmentForValidation();
            }
        };

        $request->exposePrepareForValidation();

        $this->assertEquals('2026-03-15', $request->scheduled_at);
    }

    public function test_prepare_assessment_for_validation_transforms_duration(): void
    {
        $request = new class extends FormRequest
        {
            use AssessmentValidationRules;

            public array $data = [
                'duration' => 60,
            ];

            public function has($key)
            {
                return array_key_exists($key, $this->data);
            }

            public function __get($key)
            {
                return $this->data[$key] ?? null;
            }

            public function merge(array $data)
            {
                $this->data = array_merge($this->data, $data);
            }

            public function exposePrepare()
            {
                $this->prepareAssessmentForValidation();
            }
        };

        $request->exposePrepare();

        $this->assertEquals(60, $request->duration_minutes);
    }

    public function test_prepare_assessment_for_validation_sets_default_coefficient(): void
    {
        $request = new class extends FormRequest
        {
            use AssessmentValidationRules;

            public array $data = [];

            public function has($key)
            {
                return array_key_exists($key, $this->data);
            }

            public function __get($key)
            {
                return $this->data[$key] ?? null;
            }

            public function merge(array $data)
            {
                $this->data = array_merge($this->data, $data);
            }

            public function exposePrepareAssessmentForValidation()
            {
                return $this->prepareAssessmentForValidation();
            }
        };

        $request->exposePrepareAssessmentForValidation();

        $this->assertEquals(1.0, $request->coefficient);
    }

    public function test_validation_passes_with_valid_create_data(): void
    {
        $this->createRequest->merge(['delivery_mode' => 'supervised']);
        $rules = $this->createRequest->rules();

        $validator = Validator::make([
            'class_subject_id' => $this->classSubject->id,
            'title' => 'Test Assessment',
            'type' => 'examen',
            'delivery_mode' => 'supervised',
            'scheduled_at' => '2026-03-15',
            'duration_minutes' => 60,
            'coefficient' => 2.0,
        ], $rules);

        $this->assertFalse($validator->fails());
    }

    public function test_validation_fails_without_required_create_fields(): void
    {
        $rules = $this->createRequest->rules();

        $validator = Validator::make([], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('class_subject_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('title', $validator->errors()->toArray());
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
    }

    public function test_validation_passes_with_partial_update_data(): void
    {
        $rules = $this->updateRequest->rules();

        $validator = Validator::make([
            'title' => 'Updated Title',
        ], $rules);

        $this->assertFalse($validator->fails());
    }

    public function test_validation_fails_with_invalid_type(): void
    {
        $rules = $this->createRequest->rules();

        $validator = Validator::make([
            'class_subject_id' => $this->classSubject->id,
            'title' => 'Test',
            'type' => 'invalid_type',
            'scheduled_at' => '2026-03-15',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('type', $validator->errors()->toArray());
    }
}
