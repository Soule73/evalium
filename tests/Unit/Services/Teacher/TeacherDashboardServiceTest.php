<?php

namespace Tests\Unit\Services\Teacher;

use App\Models\AcademicYear;
use App\Models\Answer;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassSubject;
use App\Models\Question;
use App\Services\Teacher\TeacherDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class TeacherDashboardServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private TeacherDashboardService $service;

    private AcademicYear $academicYear;

    private \App\Models\Level $level;

    private \App\Models\Semester $semester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        $this->service = app(TeacherDashboardService::class);
        $this->academicYear = AcademicYear::factory()->create([
            'name' => '2025-2026',
            'is_current' => true,
        ]);
        $this->level = \App\Models\Level::factory()->create([
            'name' => 'Test Level',
            'code' => 'TEST_LVL',
        ]);
        $this->semester = \App\Models\Semester::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Semestre 1',
            'order_number' => 1,
        ]);
    }

    #[Test]
    public function get_dashboard_stats_uses_sql_aggregation_not_php_counting(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher1@test.com']);

        $classes = \App\Models\ClassModel::factory()->count(3)->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);

        $subjects = \App\Models\Subject::factory()->count(4)->sequence(
            ['code' => 'MATH01', 'name' => 'Mathematics'],
            ['code' => 'PHYS01', 'name' => 'Physics'],
            ['code' => 'CHEM01', 'name' => 'Chemistry'],
            ['code' => 'BIO01', 'name' => 'Biology']
        )->create();

        foreach ($classes as $class) {
            foreach ($subjects->take(2) as $subject) {
                $classSubject = ClassSubject::factory()->create([
                    'semester_id' => $this->semester->id,
                    'teacher_id' => $teacher->id,
                    'class_id' => $class->id,
                    'subject_id' => $subject->id,
                ]);

                Assessment::factory()->count(2)->create([
                    'class_subject_id' => $classSubject->id,
                ]);
            }
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $stats = $this->service->getDashboardStats(
            $teacher->id,
            $this->academicYear->id
        );

        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);

        $this->assertLessThanOrEqual(3, $queryCount, "Expected <= 3 queries (COUNT DISTINCT + assessments count + in_progress count), but got {$queryCount}");

        $firstQuery = $queryLog[0]['query'] ?? '';
        $this->assertStringContainsString('COUNT(DISTINCT', $firstQuery, 'Should use COUNT(DISTINCT) in SQL, not PHP counting');

        $this->assertEquals(3, $stats['total_classes']);
        $this->assertEquals(2, $stats['total_subjects']);
        $this->assertEquals(12, $stats['total_assessments']);
        $this->assertArrayHasKey('in_progress_assessments', $stats);
    }

    #[Test]
    public function get_dashboard_stats_handles_empty_assignments(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher2@test.com']);

        $stats = $this->service->getDashboardStats(
            $teacher->id,
            $this->academicYear->id
        );

        $this->assertEquals(0, $stats['total_classes']);
        $this->assertEquals(0, $stats['total_subjects']);
        $this->assertEquals(0, $stats['total_assessments']);
    }

    #[Test]
    public function get_dashboard_stats_counts_unique_classes_and_subjects(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher3@test.com']);

        $class1 = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);

        $subject1 = \App\Models\Subject::factory()->create(['code' => 'MATH02', 'name' => 'Math']);

        ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class1->id,
            'subject_id' => $subject1->id,
        ]);

        ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class1->id,
            'subject_id' => $subject1->id,
        ]);

        $stats = $this->service->getDashboardStats(
            $teacher->id,
            $this->academicYear->id
        );

        $this->assertEquals(1, $stats['total_classes'], 'Should count unique classes only');
        $this->assertEquals(1, $stats['total_subjects'], 'Should count unique subjects only');
    }

    #[Test]
    public function get_dashboard_stats_filters_by_academic_year(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher4@test.com']);

        $anotherYear = AcademicYear::factory()->create([
            'name' => '2024-2025',
            'is_current' => false,
        ]);

        $classCurrentYear = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);

        $classOtherYear = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $anotherYear->id,
            'level_id' => $this->level->id,
        ]);

        $subject = \App\Models\Subject::factory()->create(['code' => 'MATH03', 'name' => 'Math']);

        ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $classCurrentYear->id,
            'subject_id' => $subject->id,
        ]);

        $otherSemester = \App\Models\Semester::factory()->create([
            'academic_year_id' => $anotherYear->id,
            'name' => 'Semestre 1',
            'order_number' => 1,
        ]);

        ClassSubject::factory()->create([
            'semester_id' => $otherSemester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $classOtherYear->id,
            'subject_id' => $subject->id,
        ]);

        $stats = $this->service->getDashboardStats(
            $teacher->id,
            $this->academicYear->id
        );

        $this->assertEquals(1, $stats['total_classes'], 'Should only count classes from current academic year');
    }

    #[Test]
    public function get_dashboard_stats_only_counts_active_assignments(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher5@test.com']);

        $class = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);

        $subject = \App\Models\Subject::factory()->create(['code' => 'MATH04', 'name' => 'Math']);

        ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'valid_to' => null,
        ]);

        ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'valid_to' => now()->subDay(),
        ]);

        $stats = $this->service->getDashboardStats(
            $teacher->id,
            $this->academicYear->id
        );

        $this->assertEquals(1, $stats['total_classes'], 'Should only count active assignments (valid_to = null)');
    }

    #[Test]
    public function get_overall_average_score_returns_normalized_average(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher_avg@test.com']);
        $class = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);
        $subject = \App\Models\Subject::factory()->create(['code' => 'MATH_AVG']);
        $cs = ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
        ]);

        $assessment = Assessment::factory()->create([
            'class_subject_id' => $cs->id,
        ]);

        $question1 = Question::factory()->create(['assessment_id' => $assessment->id, 'points' => 20]);
        $question2 = Question::factory()->create(['assessment_id' => $assessment->id, 'points' => 20]);

        $students = $this->createMultipleStudents(2);
        $scores = [30, 20];
        foreach ($students as $i => $student) {
            $enrollment = \App\Models\Enrollment::factory()->create([
                'class_id' => $class->id,
                'student_id' => $student->id,
                'status' => 'active',
            ]);
            $assignment = AssessmentAssignment::factory()->create([
                'assessment_id' => $assessment->id,
                'enrollment_id' => $enrollment->id,
                'graded_at' => now(),
                'submitted_at' => now(),
                'started_at' => now()->subHour(),
            ]);
            Answer::factory()->create([
                'assessment_assignment_id' => $assignment->id,
                'question_id' => $question1->id,
                'score' => $scores[$i] * 0.5,
            ]);
            Answer::factory()->create([
                'assessment_assignment_id' => $assignment->id,
                'question_id' => $question2->id,
                'score' => $scores[$i] * 0.5,
            ]);
        }

        $avg = $this->service->getOverallAverageScore($teacher->id, $this->academicYear->id);

        $this->assertNotNull($avg);
        $this->assertEquals(12.5, $avg);
    }

    #[Test]
    public function get_overall_average_score_returns_null_when_no_grades(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher_noavg@test.com']);

        $avg = $this->service->getOverallAverageScore($teacher->id, $this->academicYear->id);

        $this->assertNull($avg);
    }

    #[Test]
    public function get_assessment_completion_overview_returns_status_counts(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher_comp@test.com']);
        $class = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);
        $subject = \App\Models\Subject::factory()->create(['code' => 'MATH_COMP']);
        $cs = ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
        ]);

        $assessment = Assessment::factory()->create([
            'class_subject_id' => $cs->id,
        ]);

        $students = $this->createMultipleStudents(4);
        $states = [
            ['started_at' => null, 'submitted_at' => null, 'graded_at' => null],
            ['started_at' => now()->subHour(), 'submitted_at' => null, 'graded_at' => null],
            ['started_at' => now()->subHours(2), 'submitted_at' => now()->subHour(), 'graded_at' => null],
            ['started_at' => now()->subHours(3), 'submitted_at' => now()->subHours(2), 'graded_at' => now()->subHour()],
        ];

        foreach ($students as $i => $student) {
            $enrollment = \App\Models\Enrollment::factory()->create([
                'class_id' => $class->id,
                'student_id' => $student->id,
                'status' => 'active',
            ]);
            AssessmentAssignment::factory()->create(array_merge([
                'assessment_id' => $assessment->id,
                'enrollment_id' => $enrollment->id,
            ], $states[$i]));
        }

        $overview = $this->service->getAssessmentCompletionOverview($teacher->id, $this->academicYear->id);

        $this->assertEquals(1, $overview['graded']);
        $this->assertEquals(1, $overview['submitted']);
        $this->assertEquals(1, $overview['in_progress']);
        $this->assertEquals(1, $overview['not_started']);
    }

    #[Test]
    public function get_score_distribution_returns_all_five_ranges(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher_dist@test.com']);
        $class = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);
        $subject = \App\Models\Subject::factory()->create(['code' => 'MATH_DIST']);
        $cs = ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
        ]);

        $assessment = Assessment::factory()->create([
            'class_subject_id' => $cs->id,
        ]);

        $question = Question::factory()->create(['assessment_id' => $assessment->id, 'points' => 20]);

        $answerScores = [3, 7, 11, 15, 19];
        foreach ($answerScores as $answerScore) {
            $student = $this->createStudent();
            $enrollment = \App\Models\Enrollment::factory()->create([
                'class_id' => $class->id,
                'student_id' => $student->id,
                'status' => 'active',
            ]);
            $assignment = AssessmentAssignment::factory()->create([
                'assessment_id' => $assessment->id,
                'enrollment_id' => $enrollment->id,
                'graded_at' => now(),
                'submitted_at' => now(),
                'started_at' => now()->subHour(),
            ]);
            Answer::factory()->create([
                'assessment_assignment_id' => $assignment->id,
                'question_id' => $question->id,
                'score' => $answerScore,
            ]);
        }

        $distribution = $this->service->getScoreDistribution($teacher->id, $this->academicYear->id);

        $this->assertCount(5, $distribution);
        $this->assertEquals('0-4', $distribution[0]['range']);
        $this->assertEquals(1, $distribution[0]['count']);
        $this->assertEquals('17-20', $distribution[4]['range']);
        $this->assertEquals(1, $distribution[4]['count']);
    }

    #[Test]
    public function get_class_performance_chart_returns_average_per_class(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher_perf@test.com']);

        $class1 = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'name' => 'Class A',
        ]);
        $class2 = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'name' => 'Class B',
        ]);

        $subject = \App\Models\Subject::factory()->create(['code' => 'MATH_PERF']);

        foreach ([$class1, $class2] as $class) {
            $cs = ClassSubject::factory()->create([
                'semester_id' => $this->semester->id,
                'teacher_id' => $teacher->id,
                'class_id' => $class->id,
                'subject_id' => $subject->id,
            ]);
            $assessment = Assessment::factory()->create([
                'class_subject_id' => $cs->id,
            ]);
            $question = Question::factory()->create(['assessment_id' => $assessment->id, 'points' => 20]);
            $student = $this->createStudent();
            $enrollment = \App\Models\Enrollment::factory()->create([
                'class_id' => $class->id,
                'student_id' => $student->id,
                'status' => 'active',
            ]);
            $assignment = AssessmentAssignment::factory()->create([
                'assessment_id' => $assessment->id,
                'enrollment_id' => $enrollment->id,
                'graded_at' => now(),
                'submitted_at' => now(),
                'started_at' => now()->subHour(),
            ]);
            Answer::factory()->create([
                'assessment_assignment_id' => $assignment->id,
                'question_id' => $question->id,
                'score' => $class === $class1 ? 16 : 10,
            ]);
        }

        $data = $this->service->getClassPerformanceChart($teacher->id, $this->academicYear->id);

        $this->assertCount(2, $data);
        $names = $data->pluck('name')->toArray();
        $this->assertContains('Class A (Test Level)', $names);
        $this->assertContains('Class B (Test Level)', $names);
    }

    #[Test]
    public function get_class_performance_chart_includes_classes_without_grades(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher_perf2@test.com']);

        $classWithGrades = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'name' => 'Graded Class',
        ]);
        $classWithoutGrades = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'name' => 'No Grades Class',
        ]);

        $subject = \App\Models\Subject::factory()->create(['code' => 'MATH_PERF2']);

        ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $classWithGrades->id,
            'subject_id' => $subject->id,
        ]);
        $csWithGrades = ClassSubject::first();

        ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $classWithoutGrades->id,
            'subject_id' => $subject->id,
        ]);

        $assessment = Assessment::factory()->create(['class_subject_id' => $csWithGrades->id]);
        $question = Question::factory()->create(['assessment_id' => $assessment->id, 'points' => 20]);
        $student = $this->createStudent();
        $enrollment = \App\Models\Enrollment::factory()->create([
            'class_id' => $classWithGrades->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);
        $assignment = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'graded_at' => now(),
            'submitted_at' => now(),
            'started_at' => now()->subHour(),
        ]);
        Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'score' => 14,
        ]);

        $data = $this->service->getClassPerformanceChart($teacher->id, $this->academicYear->id);

        $this->assertCount(2, $data);

        $graded = $data->firstWhere('name', 'Graded Class (Test Level)');
        $noGrades = $data->firstWhere('name', 'No Grades Class (Test Level)');

        $this->assertNotNull($graded);
        $this->assertEquals(14.0, $graded->value);
        $this->assertNotNull($noGrades);
        $this->assertNull($noGrades->value);
    }

    #[Test]
    public function get_chart_data_returns_all_chart_datasets(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher_chart@test.com']);

        $data = $this->service->getChartData($teacher->id, $this->academicYear->id);

        $this->assertArrayHasKey('completionOverview', $data);
        $this->assertArrayHasKey('scoreDistribution', $data);
        $this->assertArrayHasKey('classPerformance', $data);
    }
}
