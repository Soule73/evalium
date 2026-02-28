<?php

namespace Tests\Feature\Services\Teacher;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Level;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use App\Services\Teacher\TeacherClassResultsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class TeacherClassResultsChartServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private TeacherClassResultsService $service;

    private User $teacher;

    private ClassModel $class;

    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->service = app(TeacherClassResultsService::class);

        $this->teacher = $this->createTeacher();

        $academicYear = AcademicYear::factory()->create(['is_current' => true]);
        $level = Level::factory()->create();
        $semester = Semester::factory()->create(['academic_year_id' => $academicYear->id]);

        $this->class = ClassModel::factory()->create([
            'academic_year_id' => $academicYear->id,
            'level_id' => $level->id,
        ]);

        $subject = Subject::factory()->create(['level_id' => $level->id]);
        $classSubject = ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'semester_id' => $semester->id,
        ]);

        $students = User::factory()->count(3)->create();
        foreach ($students as $student) {
            $student->assignRole('student');
            $this->class->enrollments()->create([
                'student_id' => $student->id,
                'enrolled_at' => now(),
                'status' => 'active',
            ]);
        }
        $this->class->load('students');

        $this->assessment = $this->createAssessmentWithQuestions($this->teacher, [
            'class_subject_id' => $classSubject->id,
        ]);
    }

    public function test_get_chart_data_returns_expected_keys(): void
    {
        $result = $this->service->getChartData($this->class, $this->teacher->id);

        $this->assertArrayHasKey('scoreDistribution', $result);
        $this->assertArrayHasKey('assessmentTrend', $result);
    }

    public function test_get_score_distribution_for_class_returns_all_ranges(): void
    {
        $result = $this->service->getScoreDistributionForClass($this->class->id, $this->teacher->id);

        $this->assertCount(5, $result);
        $ranges = array_column($result, 'range');
        $this->assertEquals(['0-4', '5-8', '9-12', '13-16', '17-20'], $ranges);
    }

    public function test_get_score_distribution_for_class_counts_graded_only(): void
    {
        $students = $this->class->students;

        $this->createGradedAssignment($this->assessment, $students[0], 8);
        $this->createGradedAssignment($this->assessment, $students[1], 15);
        $this->createSubmittedAssignment($this->assessment, $students[2]);

        $result = $this->service->getScoreDistributionForClass($this->class->id, $this->teacher->id);

        $totalCount = array_sum(array_column($result, 'count'));
        $this->assertEquals(2, $totalCount);
    }

    public function test_get_score_distribution_for_empty_class(): void
    {
        $emptyClass = $this->createEmptyClass();

        $result = $this->service->getScoreDistributionForClass($emptyClass->id, $this->teacher->id);

        $totalCount = array_sum(array_column($result, 'count'));
        $this->assertEquals(0, $totalCount);
    }

    public function test_get_assessment_average_trend_returns_graded_assessments(): void
    {
        $students = $this->class->students;
        $this->createGradedAssignment($this->assessment, $students[0], 15);

        $result = $this->service->getAssessmentAverageTrend($this->class->id, $this->teacher->id);

        $this->assertCount(1, $result);
        $this->assertEquals($this->assessment->title, $result[0]['name']);
        $this->assertNotNull($result[0]['value']);
    }

    public function test_get_assessment_average_trend_shows_null_for_ungraded(): void
    {
        $result = $this->service->getAssessmentAverageTrend($this->class->id, $this->teacher->id);

        $this->assertCount(1, $result);
        $this->assertEquals($this->assessment->title, $result[0]['name']);
        $this->assertNull($result[0]['value']);
    }

    public function test_get_assessment_average_trend_normalizes_to_20(): void
    {
        $students = $this->class->students;
        $this->createGradedAssignment($this->assessment, $students[0], 15);
        $this->createGradedAssignment($this->assessment, $students[1], 15);

        $result = $this->service->getAssessmentAverageTrend($this->class->id, $this->teacher->id);

        $this->assertNotNull($result[0]['value']);
        $this->assertLessThanOrEqual(20, $result[0]['value']);
        $this->assertGreaterThanOrEqual(0, $result[0]['value']);
    }
}
