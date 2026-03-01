<?php

namespace Tests\Unit\Services\Student;

use App\Models\Answer;
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

        $assignment1 = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment1->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now(),
            'graded_at' => now(),
        ]);

        Answer::factory()->create([
            'assessment_assignment_id' => $assignment1->id,
            'question_id' => $assessment1->questions()->first()->id,
            'score' => 14,
        ]);

        $assignment2 = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment2->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now()->subHour(),
            'graded_at' => now()->subMinute(),
        ]);

        Answer::factory()->create([
            'assessment_assignment_id' => $assignment2->id,
            'question_id' => $assessment2->questions()->first()->id,
            'score' => 16,
        ]);

        $result = $this->service->getDashboardStats($student);

        $this->assertEquals(2, $result['totalAssessments']);
        $this->assertEquals(2, $result['completedAssessments']);
        $this->assertEquals(0, $result['pendingAssessments']);

        $this->assertNotNull($result['averageScore']);
        $this->assertGreaterThan(0, $result['averageScore']);
        $this->assertLessThanOrEqual(20, $result['averageScore']);
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

    public function test_get_subject_radar_data_returns_grades_with_class_averages(): void
    {
        [$student, $enrollment, $classSubject] = $this->setupGradedScenario();

        $result = $this->service->getSubjectRadarData($student, null, $enrollment);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('subject', $result[0]);
        $this->assertArrayHasKey('grade', $result[0]);
        $this->assertArrayHasKey('classAverage', $result[0]);

        $graded = collect($result)->first(fn ($s) => $s['grade'] !== null);
        $this->assertNotNull($graded);
        $this->assertGreaterThan(0, $graded['grade']);
        $this->assertLessThanOrEqual(20, $graded['grade']);
    }

    public function test_get_subject_radar_data_includes_subjects_without_grades(): void
    {
        [$student, $enrollment, $classSubject] = $this->setupGradedScenario();

        $ungradedSubject = ClassSubject::factory()->create([
            'class_id' => $enrollment->class_id,
            'teacher_id' => $this->createTeacher()->id,
            'semester_id' => $classSubject->semester_id,
        ]);

        $result = $this->service->getSubjectRadarData($student, null, $enrollment);

        $this->assertCount(2, $result);
        $ungraded = collect($result)->first(fn ($s) => $s['grade'] === null);
        $this->assertNotNull($ungraded, 'Subjects without grades should appear in radar data');
    }

    public function test_get_subject_radar_data_returns_empty_without_enrollment(): void
    {
        $student = $this->createStudent();

        $result = $this->service->getSubjectRadarData($student, null);

        $this->assertEmpty($result);
    }

    public function test_get_assessment_status_chart_returns_status_breakdown(): void
    {
        [$student, $enrollment, $classSubject] = $this->setupGradedScenario();

        $assessment2 = $this->createAssessmentWithQuestions($this->createTeacher(), [
            'class_subject_id' => $classSubject->id,
            'is_published' => true,
        ], 1);

        AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment2->id,
            'enrollment_id' => $enrollment->id,
            'started_at' => now(),
            'submitted_at' => null,
        ]);

        $result = $this->service->getAssessmentStatusChart($student, null, $enrollment);

        $this->assertCount(4, $result);
        $this->assertEquals('name', array_key_first($result[0]));

        $totalCount = array_sum(array_column($result, 'value'));
        $this->assertGreaterThan(0, $totalCount);
    }

    public function test_get_assessment_status_chart_returns_empty_without_enrollment(): void
    {
        $student = $this->createStudent();

        $result = $this->service->getAssessmentStatusChart($student, null);

        $this->assertEmpty($result);
    }

    public function test_get_recent_scores_chart_returns_normalized_scores(): void
    {
        [$student, $enrollment, $classSubject] = $this->setupGradedScenario();

        $result = $this->service->getRecentScoresChart($student, null);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('value', $result[0]);

        foreach ($result as $item) {
            if ($item['value'] !== null) {
                $this->assertGreaterThanOrEqual(0, $item['value']);
                $this->assertLessThanOrEqual(20, $item['value']);
            }
        }
    }

    public function test_get_recent_scores_chart_respects_limit(): void
    {
        [$student, $enrollment, $classSubject] = $this->setupGradedScenario(assessmentCount: 5);

        $result = $this->service->getRecentScoresChart($student, null, 3);

        $this->assertLessThanOrEqual(3, count($result));
    }

    public function test_get_grade_trend_returns_monthly_averages(): void
    {
        [$student, $enrollment, $classSubject] = $this->setupGradedScenario();

        $result = $this->service->getGradeTrend($student, null);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('name', $result[0]);
        $this->assertArrayHasKey('value', $result[0]);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}$/', $result[0]['name']);
    }

    public function test_get_grade_trend_returns_empty_without_graded_assignments(): void
    {
        $student = $this->createStudent();

        $result = $this->service->getGradeTrend($student, null);

        $this->assertEmpty($result);
    }

    public function test_get_chart_data_returns_all_chart_datasets(): void
    {
        [$student, $enrollment, $classSubject] = $this->setupGradedScenario();

        $result = $this->service->getChartData($student, null, $enrollment);

        $this->assertArrayHasKey('subjectRadar', $result);
        $this->assertArrayHasKey('assessmentStatus', $result);
        $this->assertArrayHasKey('recentScores', $result);
        $this->assertArrayHasKey('gradeTrend', $result);
        $this->assertIsArray($result['subjectRadar']);
        $this->assertIsArray($result['assessmentStatus']);
        $this->assertIsArray($result['recentScores']);
        $this->assertIsArray($result['gradeTrend']);
    }

    public function test_get_chart_data_returns_empty_arrays_without_enrollment(): void
    {
        $student = $this->createStudent();

        $result = $this->service->getChartData($student, null);

        $this->assertEmpty($result['subjectRadar']);
        $this->assertEmpty($result['assessmentStatus']);
        $this->assertEmpty($result['recentScores']);
        $this->assertEmpty($result['gradeTrend']);
    }

    /**
     * Create a complete graded scenario for chart tests.
     *
     * @return array{0: \App\Models\User, 1: \App\Models\Enrollment, 2: ClassSubject}
     */
    private function setupGradedScenario(int $assessmentCount = 1): array
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

        for ($i = 0; $i < $assessmentCount; $i++) {
            $assessment = $this->createAssessmentWithQuestions($teacher, [
                'class_subject_id' => $classSubject->id,
                'is_published' => true,
            ], 2);

            $question = $assessment->questions()->first();

            $assignment = AssessmentAssignment::factory()->create([
                'assessment_id' => $assessment->id,
                'enrollment_id' => $enrollment->id,
                'started_at' => now()->subHours(2 + $i),
                'submitted_at' => now()->subHours(1 + $i),
                'graded_at' => now()->subMinutes(30 + $i * 10),
            ]);

            Answer::factory()->create([
                'assessment_assignment_id' => $assignment->id,
                'question_id' => $question->id,
                'score' => 15,
            ]);
        }

        return [$student, $enrollment->fresh(), $classSubject];
    }
}
