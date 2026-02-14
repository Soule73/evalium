<?php

namespace Tests\Feature;

use App\Enums\DeliveryMode;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Semester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class DeliveryModeValidationTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private ClassSubject $classSubject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $teacher = $this->createTeacher();
        $academicYear = AcademicYear::factory()->create();
        $classModel = ClassModel::factory()->create(['academic_year_id' => $academicYear->id]);
        $semester = Semester::factory()->create(['academic_year_id' => $academicYear->id]);

        $this->classSubject = ClassSubject::factory()->create([
            'class_id' => $classModel->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
        ]);
    }

    private function validSupervisedData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Supervised Exam',
            'type' => 'examen',
            'delivery_mode' => 'supervised',
            'duration' => 60,
            'scheduled_date' => '2026-03-15T14:00',
            'coefficient' => 2.0,
            'class_subject_id' => $this->classSubject->id,
        ], $overrides);
    }

    private function validHomeworkData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Homework Assignment',
            'type' => 'devoir',
            'delivery_mode' => 'homework',
            'due_date' => '2026-03-20T23:59',
            'coefficient' => 1.0,
            'class_subject_id' => $this->classSubject->id,
        ], $overrides);
    }

    public function test_create_supervised_assessment_succeeds(): void
    {
        $teacher = $this->classSubject->teacher;

        $response = $this->actingAs($teacher)->post(
            route('teacher.assessments.store'),
            $this->validSupervisedData()
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('assessments', [
            'title' => 'Supervised Exam',
            'delivery_mode' => 'supervised',
            'duration_minutes' => 60,
        ]);
    }

    public function test_create_homework_assessment_succeeds(): void
    {
        $teacher = $this->classSubject->teacher;

        $response = $this->actingAs($teacher)->post(
            route('teacher.assessments.store'),
            $this->validHomeworkData()
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('assessments', [
            'title' => 'Homework Assignment',
            'delivery_mode' => 'homework',
        ]);
    }

    public function test_delivery_mode_defaults_from_type_when_not_provided(): void
    {
        $teacher = $this->classSubject->teacher;

        $response = $this->actingAs($teacher)->post(
            route('teacher.assessments.store'),
            [
                'title' => 'Project Assignment',
                'type' => 'projet',
                'due_date' => '2026-04-01T23:59',
                'coefficient' => 1.0,
                'class_subject_id' => $this->classSubject->id,
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('assessments', [
            'title' => 'Project Assignment',
            'delivery_mode' => 'homework',
        ]);
    }

    public function test_delivery_mode_must_be_valid_value(): void
    {
        $teacher = $this->classSubject->teacher;

        $response = $this->actingAs($teacher)->post(
            route('teacher.assessments.store'),
            $this->validSupervisedData(['delivery_mode' => 'invalid'])
        );

        $response->assertSessionHasErrors('delivery_mode');
    }

    public function test_duration_required_for_supervised_mode(): void
    {
        $teacher = $this->classSubject->teacher;

        $data = $this->validSupervisedData();
        unset($data['duration']);

        $response = $this->actingAs($teacher)->post(
            route('teacher.assessments.store'),
            $data
        );

        $response->assertSessionHasErrors('duration_minutes');
    }

    public function test_duration_not_required_for_homework_mode(): void
    {
        $teacher = $this->classSubject->teacher;

        $response = $this->actingAs($teacher)->post(
            route('teacher.assessments.store'),
            $this->validHomeworkData()
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function test_due_date_required_for_homework_mode(): void
    {
        $teacher = $this->classSubject->teacher;

        $data = $this->validHomeworkData();
        unset($data['due_date']);

        $response = $this->actingAs($teacher)->post(
            route('teacher.assessments.store'),
            $data
        );

        $response->assertSessionHasErrors('due_date');
    }

    public function test_due_date_not_required_for_supervised_mode(): void
    {
        $teacher = $this->classSubject->teacher;

        $response = $this->actingAs($teacher)->post(
            route('teacher.assessments.store'),
            $this->validSupervisedData()
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    public function test_update_delivery_mode_on_existing_assessment(): void
    {
        $teacher = $this->classSubject->teacher;

        $assessment = Assessment::factory()->supervised()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $teacher->id,
            'settings' => ['is_published' => false],
        ]);

        $response = $this->actingAs($teacher)->put(
            route('teacher.assessments.update', $assessment),
            [
                'delivery_mode' => 'homework',
                'due_date' => '2026-04-01T23:59',
            ]
        );

        $response->assertRedirect();

        $assessment->refresh();
        $this->assertEquals(DeliveryMode::Homework, $assessment->delivery_mode);
    }

    public function test_homework_availability_before_due_date(): void
    {
        $assessment = Assessment::factory()->homework()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
            'due_date' => now()->addDays(3),
            'settings' => ['is_published' => true],
        ]);

        $status = $assessment->getAvailabilityStatus();

        $this->assertTrue($status['available']);
    }

    public function test_homework_unavailable_after_due_date(): void
    {
        $assessment = Assessment::factory()->homework()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
            'due_date' => now()->subDay(),
            'settings' => ['is_published' => true, 'allow_late_submission' => false],
        ]);

        $status = $assessment->getAvailabilityStatus();

        $this->assertFalse($status['available']);
        $this->assertEquals('assessment_due_date_passed', $status['reason']);
    }

    public function test_homework_available_after_due_date_with_late_submission(): void
    {
        $assessment = Assessment::factory()->homework()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
            'due_date' => now()->subDay(),
            'settings' => ['is_published' => true, 'allow_late_submission' => true],
        ]);

        $status = $assessment->getAvailabilityStatus();

        $this->assertTrue($status['available']);
    }

    public function test_supervised_availability_uses_scheduled_at_logic(): void
    {
        $assessment = Assessment::factory()->supervised()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
            'duration_minutes' => 60,
            'scheduled_at' => now()->subMinutes(30),
            'settings' => ['is_published' => true],
        ]);

        $status = $assessment->getAvailabilityStatus();

        $this->assertTrue($status['available']);
    }

    public function test_default_delivery_mode_set_from_type_when_not_provided(): void
    {
        $teacher = $this->classSubject->teacher;

        $data = $this->validSupervisedData();
        unset($data['delivery_mode']);
        $data['type'] = 'examen';

        $this->actingAs($teacher)->post(
            route('teacher.assessments.store'),
            $data
        );

        $this->assertDatabaseHas('assessments', [
            'title' => 'Supervised Exam',
            'delivery_mode' => 'supervised',
        ]);
    }

    public function test_file_upload_fields_accepted_in_assessment(): void
    {
        $teacher = $this->classSubject->teacher;

        $response = $this->actingAs($teacher)->post(
            route('teacher.assessments.store'),
            $this->validHomeworkData([
                'max_file_size' => 5120,
                'allowed_extensions' => 'pdf,docx,zip',
                'max_files' => 3,
            ])
        );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('assessments', [
            'title' => 'Homework Assignment',
            'max_file_size' => 5120,
            'allowed_extensions' => 'pdf,docx,zip',
            'max_files' => 3,
        ]);
    }
}
