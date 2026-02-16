<?php

namespace Tests\Feature\Student;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Question;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class StudentAssessmentShowTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private User $student;

    private User $teacher;

    private ClassModel $class;

    private AcademicYear $academicYear;

    private ClassSubject $classSubject;

    private Enrollment $enrollment;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->academicYear = AcademicYear::firstOrCreate(
            ['is_current' => true],
            ['name' => '2025/2026', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30']
        );

        $level = Level::factory()->create();
        $this->class = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $level->id,
        ]);

        $this->student = $this->createStudent();
        $this->enrollment = $this->class->enrollments()->create([
            'student_id' => $this->student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $this->teacher = $this->createTeacher();
        $subject = Subject::factory()->create(['level_id' => $level->id]);

        $semester = Semester::firstOrCreate(
            ['academic_year_id' => $this->academicYear->id, 'order_number' => 1],
            ['name' => 'Semester 1', 'start_date' => '2025-09-01', 'end_date' => '2026-01-31']
        );

        $this->classSubject = ClassSubject::create([
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'semester_id' => $semester->id,
            'coefficient' => 2,
            'valid_from' => now(),
        ]);

        $this->assessment = Assessment::factory()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
            'coefficient' => 1,
            'scheduled_at' => now()->subHour(),
            'settings' => ['is_published' => true],
        ]);

        Question::factory()->count(2)->create([
            'assessment_id' => $this->assessment->id,
            'points' => 10,
        ]);
    }

    public function test_enrolled_student_can_view_assessment(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('student.assessments.show', $this->assessment));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Student/Assessments/Show')
                ->has('assessment')
                ->has('assignment')
        );
    }

    public function test_unenrolled_student_cannot_view_assessment(): void
    {
        $otherStudent = $this->createStudent();

        $response = $this->actingAs($otherStudent)
            ->get(route('student.assessments.show', $this->assessment));

        $response->assertForbidden();
    }

    public function test_show_loads_assessment_relationships(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('student.assessments.show', $this->assessment));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Student/Assessments/Show')
                ->has('assessment.class_subject')
                ->has('assessment.questions', 2)
        );
    }

    public function test_show_creates_assignment_if_not_exists(): void
    {
        $this->assertDatabaseMissing('assessment_assignments', [
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        $this->actingAs($this->student)
            ->get(route('student.assessments.show', $this->assessment));

        $this->assertDatabaseHas('assessment_assignments', [
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);
    }

    public function test_non_student_role_cannot_access_student_assessment_route(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('student.assessments.show', $this->assessment));

        $response->assertForbidden();
    }

    public function test_show_passes_availability_status(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('student.assessments.show', $this->assessment));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Student/Assessments/Show')
                ->has('availability')
                ->where('availability.available', true)
                ->where('availability.reason', null)
        );
    }

    public function test_show_availability_unavailable_when_not_published(): void
    {
        $this->assessment->update(['settings' => ['is_published' => false]]);

        $response = $this->actingAs($this->student)
            ->get(route('student.assessments.show', $this->assessment));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Student/Assessments/Show')
                ->where('availability.available', false)
                ->where('availability.reason', 'assessment_not_published')
        );
    }

    public function test_show_availability_unavailable_when_homework_due_date_passed(): void
    {
        $this->assessment->update([
            'delivery_mode' => 'homework',
            'due_date' => now()->subDay(),
            'settings' => ['is_published' => true, 'allow_late_submission' => false],
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.assessments.show', $this->assessment));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->where('availability.available', false)
                ->where('availability.reason', 'assessment_due_date_passed')
        );
    }

    public function test_show_availability_unavailable_when_supervised_not_started(): void
    {
        $this->assessment->update([
            'delivery_mode' => 'supervised',
            'scheduled_at' => now()->addDay(),
            'duration_minutes' => 60,
            'settings' => ['is_published' => true],
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.assessments.show', $this->assessment));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->where('availability.available', false)
                ->where('availability.reason', 'assessment_not_started')
        );
    }

    public function test_show_does_not_set_started_at_for_supervised_assessment(): void
    {
        $this->assessment->update([
            'delivery_mode' => 'supervised',
            'scheduled_at' => now()->subMinutes(5),
            'duration_minutes' => 60,
            'settings' => ['is_published' => true],
        ]);

        $this->actingAs($this->student)
            ->get(route('student.assessments.show', $this->assessment));

        $this->assertDatabaseHas('assessment_assignments', [
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
            'started_at' => null,
        ]);
    }
}
