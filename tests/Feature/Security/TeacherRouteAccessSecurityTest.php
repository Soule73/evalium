<?php

namespace Tests\Feature\Security;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Level;
use App\Models\Question;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

/**
 * Verifies that student users cannot access teacher-only routes,
 * preventing exposure of correct answer data via URL manipulation.
 */
class TeacherRouteAccessSecurityTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private User $student;

    private User $teacher;

    private Assessment $assessment;

    private AssessmentAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $academicYear = AcademicYear::firstOrCreate(
            ['is_current' => true],
            ['name' => '2025/2026', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30']
        );

        $level = Level::factory()->create();
        $class = ClassModel::factory()->create([
            'academic_year_id' => $academicYear->id,
            'level_id' => $level->id,
        ]);

        $this->student = $this->createStudent();
        $enrollment = $class->enrollments()->create([
            'student_id' => $this->student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $this->teacher = $this->createTeacher();
        $subject = Subject::factory()->create(['level_id' => $level->id]);

        $semester = Semester::firstOrCreate(
            ['academic_year_id' => $academicYear->id, 'order_number' => 1],
            ['name' => 'Semester 1', 'start_date' => '2025-09-01', 'end_date' => '2026-01-31']
        );

        $classSubject = ClassSubject::create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'semester_id' => $semester->id,
            'coefficient' => 2,
            'valid_from' => now(),
        ]);

        $this->assessment = Assessment::factory()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'coefficient' => 1,
            'scheduled_at' => now()->subHour(),
            'settings' => ['is_published' => true, 'show_correct_answers' => true],
        ]);

        Question::factory()->count(2)->create([
            'assessment_id' => $this->assessment->id,
            'points' => 10,
        ]);

        $this->assignment = AssessmentAssignment::create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now(),
            'graded_at' => now(),
            'score' => 15,
        ]);
    }

    public function test_student_cannot_access_teacher_assessment_review(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('teacher.assessments.review', [
                'assessment' => $this->assessment,
                'assignment' => $this->assignment,
            ]));

        $response->assertForbidden();
    }

    public function test_student_cannot_access_teacher_assessment_grade(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('teacher.assessments.grade', [
                'assessment' => $this->assessment,
                'assignment' => $this->assignment,
            ]));

        $response->assertForbidden();
    }

    public function test_teacher_can_access_their_own_assessment_review(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.assessments.review', [
                'assessment' => $this->assessment,
                'assignment' => $this->assignment,
            ]));

        $response->assertOk();
    }
}
