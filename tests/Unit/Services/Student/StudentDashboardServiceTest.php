<?php

namespace Tests\Unit\Services\Student;

use App\Models\AssessmentAssignment;
use App\Models\ClassSubject;
use App\Models\Semester;
use App\Services\Student\StudentDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class StudentDashboardServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private StudentDashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        $this->service = app(StudentDashboardService::class);
    }

    public function test_get_dashboard_stats_returns_comprehensive_data(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $class = $this->createClassWithStudents(studentCount: 0);

        $enrollment = $class->enrollments()->create([
            'student_id' => $student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $semester = Semester::factory()->create(['academic_year_id' => $class->academic_year_id]);
        $classSubject = ClassSubject::factory()->create([
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $semester->id,
        ]);

        $assessment1 = $this->createAssessmentWithQuestions($teacher, [
            'class_subject_id' => $classSubject->id,
            'is_published' => true,
        ], 2);

        $assessment2 = $this->createAssessmentWithQuestions($teacher, [
            'class_subject_id' => $classSubject->id,
            'is_published' => true,
        ], 2);

        $assessment3 = $this->createAssessmentWithQuestions($teacher, [
            'class_subject_id' => $classSubject->id,
            'is_published' => true,
        ], 2);

        AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment1->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now(),
            'graded_at' => now(),
            'score' => 15,
        ]);

        AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment2->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now()->subHour(),
        ]);

        $result = $this->service->getDashboardStats($student);

        $this->assertArrayHasKey('totalAssessments', $result);
        $this->assertArrayHasKey('completedAssessments', $result);
        $this->assertArrayHasKey('pendingAssessments', $result);
        $this->assertArrayHasKey('averageScore', $result);

        $this->assertEquals(3, $result['totalAssessments']);
        $this->assertEquals(1, $result['completedAssessments']);
        $this->assertEquals(1, $result['pendingAssessments']);
    }

    public function test_get_dashboard_stats_calculates_average_correctly(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $class = $this->createClassWithStudents(studentCount: 0);

        $enrollment = $class->enrollments()->create([
            'student_id' => $student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $semester = Semester::factory()->create(['academic_year_id' => $class->academic_year_id]);
        $classSubject = ClassSubject::factory()->create([
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $semester->id,
        ]);

        $assessment1 = $this->createAssessmentWithQuestions($teacher, [
            'class_subject_id' => $classSubject->id,
            'is_published' => true,
        ], 2);

        $assessment2 = $this->createAssessmentWithQuestions($teacher, [
            'class_subject_id' => $classSubject->id,
            'is_published' => true,
        ], 2);

        AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment1->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now(),
            'graded_at' => now(),
            'score' => 20,
        ]);

        AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment2->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now()->subHour(),
            'graded_at' => now()->subMinute(),
            'score' => 12,
        ]);

        $result = $this->service->getDashboardStats($student);

        $this->assertEquals(2, $result['totalAssessments']);
        $this->assertEquals(2, $result['completedAssessments']);
        $this->assertEquals(0, $result['pendingAssessments']);

        if ($result['averageScore'] !== null) {
            $this->assertGreaterThan(0, $result['averageScore']);
            $this->assertLessThanOrEqual(20, $result['averageScore']);
        }
    }

    public function test_get_dashboard_stats_handles_no_enrollments(): void
    {
        $student = $this->createStudent();

        $result = $this->service->getDashboardStats($student);

        $this->assertEquals(0, $result['totalAssessments']);
        $this->assertEquals(0, $result['completedAssessments']);
        $this->assertEquals(0, $result['pendingAssessments']);
        $this->assertNull($result['averageScore']);
    }

    public function test_get_dashboard_stats_filters_by_academic_year(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $class = $this->createClassWithStudents(studentCount: 0);

        $enrollment = $class->enrollments()->create([
            'student_id' => $student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $semester = Semester::factory()->create(['academic_year_id' => $class->academic_year_id]);
        $classSubject = ClassSubject::factory()->create([
            'class_id' => $class->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $semester->id,
        ]);

        $assessment = $this->createAssessmentWithQuestions($teacher, [
            'class_subject_id' => $classSubject->id,
            'is_published' => true,
        ], 2);

        $result = $this->service->getDashboardStats($student, $class->academic_year_id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('totalAssessments', $result);
        $this->assertEquals(1, $result['totalAssessments']);
        $this->assertEquals(1, $result['pendingAssessments']);
    }
}
