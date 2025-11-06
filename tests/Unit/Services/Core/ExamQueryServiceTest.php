<?php

namespace Tests\Unit\Services\Core;

use App\Models\Exam;
use App\Models\Group;
use App\Models\User;
use App\Services\Core\ExamQueryService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExamQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExamQueryService $service;
    private User $teacher;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->service = new ExamQueryService();

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_get_exams_filters_by_teacher_id(): void
    {
        $teacher1 = User::factory()->create();
        $teacher2 = User::factory()->create();

        Exam::factory()->count(3)->create(['teacher_id' => $teacher1->id]);
        Exam::factory()->count(2)->create(['teacher_id' => $teacher2->id]);

        $result = $this->service->getExams($teacher1->id, 10);

        $this->assertCount(3, $result->items());
        $this->assertTrue(collect($result->items())->every(fn($exam) => $exam->teacher_id === $teacher1->id));
    }

    public function test_get_exams_returns_all_when_no_teacher_filter(): void
    {
        Exam::factory()->count(5)->create(['teacher_id' => $this->teacher->id]);
        Exam::factory()->count(3)->create(['teacher_id' => $this->admin->id]);

        $result = $this->service->getExams(null, 20);

        $this->assertCount(8, $result->items());
    }

    public function test_get_exams_filters_by_status(): void
    {
        Exam::factory()->count(3)->create([
            'teacher_id' => $this->teacher->id,
            'is_active' => true
        ]);
        Exam::factory()->count(2)->create([
            'teacher_id' => $this->teacher->id,
            'is_active' => false
        ]);

        $activeResult = $this->service->getExams($this->teacher->id, 10, true);
        $inactiveResult = $this->service->getExams($this->teacher->id, 10, false);

        $this->assertCount(3, $activeResult->items());
        $this->assertTrue(collect($activeResult->items())->every(fn($exam) => $exam->is_active === true));

        $this->assertCount(2, $inactiveResult->items());
        $this->assertTrue(collect($inactiveResult->items())->every(fn($exam) => $exam->is_active === false));
    }

    public function test_get_exams_searches_title(): void
    {
        Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Mathematics Final Exam'
        ]);
        Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Physics Quiz'
        ]);
        Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Chemistry Test'
        ]);

        $result = $this->service->getExams($this->teacher->id, 10, null, 'Mathematics');

        $this->assertCount(1, $result->items());
        $this->assertStringContainsString('Mathematics', collect($result->items())->first()->title);
    }

    public function test_get_exams_searches_description(): void
    {
        Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
            'description' => 'Advanced calculus concepts'
        ]);
        Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
            'description' => 'Basic algebra review'
        ]);

        $result = $this->service->getExams($this->teacher->id, 10, null, 'calculus');

        $this->assertCount(1, $result->items());
        $this->assertStringContainsString('calculus', collect($result->items())->first()->description);
    }

    public function test_get_exams_includes_counts(): void
    {
        $exam = Exam::factory()
            ->hasQuestions(5)
            ->create(['teacher_id' => $this->teacher->id]);

        $student = User::factory()->create();
        $student->assignRole('student');

        $group = Group::factory()->create();
        $group->students()->attach($student->id, [
            'is_active' => true,
            'enrolled_at' => now()
        ]);

        $exam->groups()->attach($group->id, ['assigned_by' => $this->teacher->id]);

        $result = $this->service->getExams($this->teacher->id, 10);

        $this->assertCount(1, $result->items());
        $this->assertEquals(5, collect($result->items())->first()->questions_count);
        $this->assertNotNull(collect($result->items())->first()->assignments_count);
    }

    public function test_get_exams_paginates_correctly(): void
    {
        Exam::factory()->count(25)->create(['teacher_id' => $this->teacher->id]);

        $page1 = $this->service->getExams($this->teacher->id, 10);
        $page2 = $this->service->getExams($this->teacher->id, 10);

        $this->assertCount(10, $page1->items());
        $this->assertEquals(25, $page1->total());
        $this->assertEquals(3, $page1->lastPage());
    }

    public function test_get_exams_orders_by_latest(): void
    {
        $old = Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
            'created_at' => now()->subDays(5)
        ]);
        $recent = Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
            'created_at' => now()->subDay()
        ]);
        $newest = Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
            'created_at' => now()
        ]);

        $result = $this->service->getExams($this->teacher->id, 10);

        $this->assertEquals($newest->id, collect($result->items())->first()->id);
        $this->assertEquals($old->id, collect($result->items())->last()->id);
    }

    public function test_get_exams_combines_filters(): void
    {
        $activeExam = Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
            'title' => 'UNIQUE Calculus Exam ACTIVE',
            'description' => 'Test description',
            'is_active' => true
        ]);
        $inactiveExam = Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
            'title' => 'UNIQUE Calculus Test INACTIVE',
            'description' => 'Test description',
            'is_active' => false
        ]);
        $otherExam = Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Physics Exam',
            'description' => 'Other subject',
            'is_active' => true
        ]);

        $result = $this->service->getExams($this->teacher->id, 10, true, 'UNIQUE Calculus');

        $items = $result->items();
        $this->assertCount(1, $items);
        $this->assertEquals($activeExam->id, collect($items)->first()->id);
        $this->assertStringContainsString('UNIQUE Calculus', collect($items)->first()->title);
        $this->assertTrue((bool) collect($items)->first()->is_active);
    }

    public function test_get_exams_preserves_query_string(): void
    {
        Exam::factory()->count(3)->create(['teacher_id' => $this->teacher->id]);

        request()->merge(['custom_param' => 'test']);

        $result = $this->service->getExams($this->teacher->id, 10);

        $this->assertStringContainsString('custom_param=test', $result->url(1));
    }

    public function test_get_exams_handles_empty_results(): void
    {
        $result = $this->service->getExams($this->teacher->id, 10);

        $this->assertCount(0, $result->items());
        $this->assertEquals(0, $result->total());
    }
}
