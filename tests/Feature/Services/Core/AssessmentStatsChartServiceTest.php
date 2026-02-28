<?php

namespace Tests\Feature\Services\Core;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Semester;
use App\Services\Core\AssessmentStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class AssessmentStatsChartServiceTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private AssessmentStatsService $service;

    private Assessment $assessment;

    private ClassModel $class;

    private Question $question;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $this->service = app(AssessmentStatsService::class);

        $academicYear = AcademicYear::factory()->create();
        $this->class = ClassModel::factory()->create(['academic_year_id' => $academicYear->id]);
        $semester = Semester::factory()->create(['academic_year_id' => $academicYear->id]);

        $classSubject = ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'semester_id' => $semester->id,
        ]);

        $this->assessment = Assessment::factory()->supervised()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'is_published' => true,
        ]);

        $this->question = $this->assessment->questions()->create([
            'content' => 'Test question',
            'type' => 'text',
            'points' => 20,
            'order_index' => 1,
        ]);
    }

    public function test_get_score_distribution_returns_all_ranges(): void
    {
        $result = $this->service->getScoreDistribution($this->assessment->id);

        $this->assertCount(5, $result);
        $this->assertEquals('0-4', $result[0]['range']);
        $this->assertEquals('5-8', $result[1]['range']);
        $this->assertEquals('9-12', $result[2]['range']);
        $this->assertEquals('13-16', $result[3]['range']);
        $this->assertEquals('17-20', $result[4]['range']);
    }

    public function test_get_score_distribution_counts_graded_assignments(): void
    {
        $this->createGradedEnrollmentWithScore(4);
        $this->createGradedEnrollmentWithScore(10);
        $this->createGradedEnrollmentWithScore(18);
        $this->createGradedEnrollmentWithScore(18);

        $result = $this->service->getScoreDistribution($this->assessment->id);

        $totalCount = array_sum(array_column($result, 'count'));
        $this->assertEquals(4, $totalCount);
    }

    public function test_get_score_distribution_ignores_non_graded(): void
    {
        $student = $this->createStudent();
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);
        AssessmentAssignment::create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $enrollment->id,
            'started_at' => now(),
        ]);

        $result = $this->service->getScoreDistribution($this->assessment->id);

        $totalCount = array_sum(array_column($result, 'count'));
        $this->assertEquals(0, $totalCount);
    }

    public function test_get_assessment_status_chart_returns_four_statuses(): void
    {
        $result = $this->service->getAssessmentStatusChart($this->assessment->id);

        $this->assertCount(4, $result);
        $names = array_column($result, 'name');
        $this->assertContains(__('charts.completion.graded'), $names);
        $this->assertContains(__('charts.completion.submitted'), $names);
        $this->assertContains(__('charts.completion.in_progress'), $names);
        $this->assertContains(__('charts.completion.not_started'), $names);
    }

    public function test_get_assessment_status_chart_with_mixed_statuses(): void
    {
        $student1 = $this->createStudent();
        $enrollment1 = Enrollment::create([
            'student_id' => $student1->id,
            'class_id' => $this->class->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $student2 = $this->createStudent();
        $enrollment2 = Enrollment::create([
            'student_id' => $student2->id,
            'class_id' => $this->class->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        AssessmentAssignment::create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $enrollment1->id,
            'started_at' => now()->subHour(),
            'submitted_at' => now()->subMinutes(30),
            'graded_at' => now(),
        ]);

        $result = $this->service->getAssessmentStatusChart($this->assessment->id);

        $values = array_combine(array_column($result, 'name'), array_column($result, 'value'));
        $this->assertEquals(1, $values[__('charts.completion.graded')]);
        $this->assertEquals(1, $values[__('charts.completion.not_started')]);
    }

    public function test_get_assessment_status_chart_has_colors(): void
    {
        $result = $this->service->getAssessmentStatusChart($this->assessment->id);

        foreach ($result as $item) {
            $this->assertArrayHasKey('color', $item);
            $this->assertMatchesRegularExpression('/^#[a-f0-9]{6}$/', $item['color']);
        }
    }

    /**
     * @param  float  $rawScore  Score out of 20 (question has 20 points)
     */
    private function createGradedEnrollmentWithScore(float $rawScore): void
    {
        $student = $this->createStudent();
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->class->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $assignment = AssessmentAssignment::create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $enrollment->id,
            'started_at' => now()->subHour(),
            'submitted_at' => now()->subMinutes(30),
            'graded_at' => now(),
        ]);

        $assignment->answers()->create([
            'question_id' => $this->question->id,
            'score' => $rawScore,
        ]);
    }
}
