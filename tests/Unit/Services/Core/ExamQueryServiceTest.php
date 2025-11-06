<?php

namespace Tests\Unit\Services\Core;

use App\Services\Core\ExamQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class ExamQueryServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private ExamQueryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        $this->service = new ExamQueryService;
    }

    public function test_get_exams_filters_by_teacher_id(): void
    {
        $teacher1 = $this->createTeacher();
        $teacher2 = $this->createTeacher();

        $this->createExamWithQuestions($teacher1, questionCount: 0, examAttributes: ['teacher_id' => $teacher1->id]);
        $this->createExamWithQuestions($teacher1, questionCount: 0, examAttributes: ['teacher_id' => $teacher1->id]);
        $this->createExamWithQuestions($teacher1, questionCount: 0, examAttributes: ['teacher_id' => $teacher1->id]);
        $this->createExamWithQuestions($teacher2, questionCount: 0, examAttributes: ['teacher_id' => $teacher2->id]);
        $this->createExamWithQuestions($teacher2, questionCount: 0, examAttributes: ['teacher_id' => $teacher2->id]);

        $result = $this->service->getExams($teacher1->id, 10);

        $this->assertCount(3, $result->items());
        $this->assertTrue(collect($result->items())->every(fn ($exam) => $exam->teacher_id === $teacher1->id));
    }

    public function test_get_exams_returns_all_when_no_teacher_filter(): void
    {
        $teacher = $this->createTeacher();
        $admin = $this->createAdmin();

        collect()->times(5, fn () => $this->createExamWithQuestions($teacher, questionCount: 0, examAttributes: ['teacher_id' => $teacher->id]));
        collect()->times(3, fn () => $this->createExamWithQuestions($admin, questionCount: 0, examAttributes: ['teacher_id' => $admin->id]));

        $result = $this->service->getExams(null, 20);

        $this->assertCount(8, $result->items());
    }

    public function test_get_exams_filters_by_status(): void
    {
        $teacher = $this->createTeacher();

        collect()->times(3, fn () => $this->createExamWithQuestions($teacher, questionCount: 0, examAttributes: ['teacher_id' => $teacher->id, 'is_active' => true]));
        collect()->times(2, fn () => $this->createExamWithQuestions($teacher, questionCount: 0, examAttributes: ['teacher_id' => $teacher->id, 'is_active' => false]));

        $activeResult = $this->service->getExams($teacher->id, 10, true);
        $inactiveResult = $this->service->getExams($teacher->id, 10, false);

        $this->assertCount(3, $activeResult->items());
        $this->assertTrue(collect($activeResult->items())->every(fn ($exam) => $exam->is_active === true));

        $this->assertCount(2, $inactiveResult->items());
        $this->assertTrue(collect($inactiveResult->items())->every(fn ($exam) => $exam->is_active === false));
    }

    public function test_get_exams_searches_title(): void
    {
        $teacher = $this->createTeacher();

        $this->createExamWithQuestions($teacher, questionCount: 0, examAttributes: ['teacher_id' => $teacher->id, 'title' => 'Mathematics Final Exam']);
        $this->createExamWithQuestions($teacher, questionCount: 0, examAttributes: ['teacher_id' => $teacher->id, 'title' => 'Physics Quiz']);
        $this->createExamWithQuestions($teacher, questionCount: 0, examAttributes: ['teacher_id' => $teacher->id, 'title' => 'Chemistry Test']);

        $result = $this->service->getExams($teacher->id, 10, null, 'Mathematics');

        $this->assertCount(1, $result->items());
        $this->assertStringContainsString('Mathematics', collect($result->items())->first()->title);
    }

    public function test_get_exams_searches_description(): void
    {
        $teacher = $this->createTeacher();

        $this->createExamWithQuestions($teacher, questionCount: 0, examAttributes: ['teacher_id' => $teacher->id, 'description' => 'Advanced calculus concepts']);
        $this->createExamWithQuestions($teacher, questionCount: 0, examAttributes: ['teacher_id' => $teacher->id, 'description' => 'Basic algebra review']);

        $result = $this->service->getExams($teacher->id, 10, null, 'calculus');

        $this->assertCount(1, $result->items());
        $this->assertStringContainsString('calculus', collect($result->items())->first()->description);
    }

    public function test_get_exams_includes_counts(): void
    {
        $teacher = $this->createTeacher();
        $exam = $this->createExamWithQuestions($teacher, questionCount: 5, examAttributes: ['teacher_id' => $teacher->id]);

        $student = $this->createStudent();

        $group = $this->createGroupWithStudents(0);
        $group->students()->attach($student->id, [
            'is_active' => true,
            'enrolled_at' => now(),
        ]);

        $exam->groups()->attach($group->id, ['assigned_by' => $teacher->id]);

        $result = $this->service->getExams($teacher->id, 10);

        $this->assertCount(1, $result->items());
        $this->assertEquals(5, collect($result->items())->first()->questions_count);
        $this->assertNotNull(collect($result->items())->first()->assignments_count);
    }

    public function test_get_exams_paginates_correctly(): void
    {
        $teacher = $this->createTeacher();

        collect()->times(25, fn () => $this->createExamWithQuestions($teacher, questionCount: 0, examAttributes: ['teacher_id' => $teacher->id]));

        $page1 = $this->service->getExams($teacher->id, 10);
        $page2 = $this->service->getExams($teacher->id, 10);

        $this->assertCount(10, $page1->items());
        $this->assertEquals(25, $page1->total());
        $this->assertEquals(3, $page1->lastPage());
    }

    public function test_get_exams_orders_by_latest(): void
    {
        $teacher = $this->createTeacher();

        $old = $this->createExamWithQuestions(
            $teacher,
            questionCount: 0,
            examAttributes: ['teacher_id' => $teacher->id, 'created_at' => now()->subDays(5)]
        );
        $recent = $this->createExamWithQuestions(
            $teacher,
            questionCount: 0,
            examAttributes: ['teacher_id' => $teacher->id, 'created_at' => now()->subDay()]
        );
        $newest = $this->createExamWithQuestions(
            $teacher,
            questionCount: 0,
            examAttributes: ['teacher_id' => $teacher->id, 'created_at' => now()]
        );

        $result = $this->service->getExams($teacher->id, 10);

        $this->assertEquals($newest->id, collect($result->items())->first()->id);
        $this->assertEquals($old->id, collect($result->items())->last()->id);
    }

    public function test_get_exams_combines_filters(): void
    {
        $teacher = $this->createTeacher();

        $activeExam = $this->createExamWithQuestions(
            $teacher,
            questionCount: 0,
            examAttributes: [
                'teacher_id' => $teacher->id,
                'title' => 'UNIQUE Calculus Exam ACTIVE',
                'description' => 'Test description',
                'is_active' => true,
            ]
        );
        $inactiveExam = $this->createExamWithQuestions(
            $teacher,
            questionCount: 0,
            examAttributes: [
                'teacher_id' => $teacher->id,
                'title' => 'UNIQUE Calculus Test INACTIVE',
                'description' => 'Test description',
                'is_active' => false,
            ]
        );
        $otherExam = $this->createExamWithQuestions(
            $teacher,
            questionCount: 0,
            examAttributes: [
                'teacher_id' => $teacher->id,
                'title' => 'Physics Exam',
                'description' => 'Other subject',
                'is_active' => true,
            ]
        );

        $result = $this->service->getExams($teacher->id, 10, true, 'UNIQUE Calculus');

        $items = $result->items();
        $this->assertCount(1, $items);
        $this->assertEquals($activeExam->id, collect($items)->first()->id);
        $this->assertStringContainsString('UNIQUE Calculus', collect($items)->first()->title);
        $this->assertTrue((bool) collect($items)->first()->is_active);
    }

    public function test_get_exams_preserves_query_string(): void
    {
        $teacher = $this->createTeacher();

        collect()->times(3, fn () => $this->createExamWithQuestions($teacher, questionCount: 0, examAttributes: ['teacher_id' => $teacher->id]));

        request()->merge(['custom_param' => 'test']);

        $result = $this->service->getExams($teacher->id, 10);

        $this->assertStringContainsString('custom_param=test', $result->url(1));
    }

    public function test_get_exams_handles_empty_results(): void
    {
        $teacher = $this->createTeacher();

        $result = $this->service->getExams($teacher->id, 10);

        $this->assertCount(0, $result->items());
        $this->assertEquals(0, $result->total());
    }
}
