<?php

namespace Tests\Feature\Controllers;

use App\Models\Exam;
use App\Models\Question;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

/**
 * Tests pour Exam/ExamController (CRUD uniquement)
 *
 * Les tests pour les fonctionnalités spécialisées ont été migrés vers :
 * - ExamAssignmentControllerTest.php (assignations étudiants)
 * - ExamGroupAssignmentControllerTest.php (assignations groupes)
 * - ExamCorrectionControllerTest.php (corrections et reviews)
 * - ExamResultsControllerTest.php (résultats et statistiques)
 */
class TeacherExamControllerTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
    }

    // ==================== INDEX ====================

    #[Test]
    public function teacher_can_access_exam_index()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);

        $response = $this->actingAs($teacher)
            ->get(route('exams.index'));

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Exam/Index', false)
                ->has('exams')
        );
    }

    // ==================== CREATE ====================

    #[Test]
    public function teacher_can_view_create_form()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);

        $response = $this->actingAs($teacher)
            ->get(route('exams.create'));

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Exam/Create', false)
        );
    }

    // ==================== STORE ====================

    #[Test]
    public function teacher_can_create_exam()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);

        $examData = [
            'title' => 'New Exam',
            'description' => 'Test Description',
            'duration' => 60,
            'start_date' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'is_active' => true,
            'questions' => [
                [
                    'content' => 'Question 1?',
                    'type' => 'text',
                    'points' => 10,
                    'order_index' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($teacher)
            ->post(route('exams.store'), $examData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('exams', [
            'title' => 'New Exam',
            'teacher_id' => $teacher->id,
        ]);
    }

    #[Test]
    public function teacher_cannot_create_exam_with_invalid_data()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);

        $response = $this->actingAs($teacher)
            ->post(route('exams.store'), [
                'title' => '',
                'duration' => -10,
            ]);

        $response->assertSessionHasErrors(['title', 'duration']);
    }

    // ==================== SHOW ====================

    #[Test]
    public function teacher_can_view_exam_details()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Test Exam',
            'is_active' => true,
        ]);

        Question::factory()->count(3)->create([
            'exam_id' => $exam->id,
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('exams.show', $exam));

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Exam/Show', false)
                ->has('exam')
                ->has('exam.questions', 3)
        );
    }

    #[Test]
    public function teacher_cannot_view_other_teacher_exam()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $otherTeacher = $this->createTeacher(['email' => 'other@test.com']);

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id,
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('exams.show', $otherExam));

        $response->assertForbidden();
    }

    // ==================== EDIT ====================

    #[Test]
    public function teacher_can_view_edit_form()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Test Exam',
            'is_active' => true,
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('exams.edit', $exam));

        $response->assertOk();
        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Exam/Edit', false)
                ->has('exam')
        );
    }

    #[Test]
    public function teacher_cannot_edit_other_teacher_exam()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $otherTeacher = $this->createTeacher(['email' => 'other@test.com']);

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id,
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('exams.edit', $otherExam));

        $response->assertForbidden();
    }

    // ==================== UPDATE ====================

    #[Test]
    public function teacher_can_update_exam()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Test Exam',
            'is_active' => true,
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'duration' => 90,
            'start_date' => $exam->start_date,
            'end_date' => $exam->end_date,
            'is_active' => false,
            'questions' => [
                [
                    'content' => 'Updated Question?',
                    'type' => 'text',
                    'points' => 15,
                    'order_index' => 1,
                ],
            ],
        ];

        $response = $this->actingAs($teacher)
            ->put(route('exams.update', $exam), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'title' => 'Updated Title',
            'duration' => 90,
        ]);
    }

    #[Test]
    public function teacher_cannot_update_other_teacher_exam()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $otherTeacher = $this->createTeacher(['email' => 'other@test.com']);

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id,
        ]);

        Question::factory()->create([
            'exam_id' => $otherExam->id,
        ]);

        $response = $this->actingAs($teacher)
            ->put(route('exams.update', $otherExam), [
                'title' => 'Hacked Title',
                'duration' => 60,
                'questions' => [
                    [
                        'content' => 'Question 1',
                        'type' => 'text',
                        'points' => 10,
                        'order_index' => 1,
                    ],
                ],
            ]);

        $response->assertForbidden();
    }

    // ==================== DESTROY ====================

    #[Test]
    public function teacher_can_delete_exam()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Test Exam',
            'is_active' => true,
        ]);

        $response = $this->actingAs($teacher)
            ->delete(route('exams.destroy', $exam));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('exams', [
            'id' => $exam->id,
        ]);
    }

    #[Test]
    public function teacher_cannot_delete_other_teacher_exam()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $otherTeacher = $this->createTeacher(['email' => 'other@test.com']);

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id,
        ]);

        $response = $this->actingAs($teacher)
            ->delete(route('exams.destroy', $otherExam));

        $response->assertForbidden();

        $this->assertNotSoftDeleted('exams', [
            'id' => $otherExam->id,
        ]);
    }

    // ==================== DUPLICATE ====================

    #[Test]
    public function teacher_can_duplicate_exam()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Test Exam',
            'is_active' => true,
        ]);

        Question::factory()->count(3)->create([
            'exam_id' => $exam->id,
        ]);

        $response = $this->actingAs($teacher)
            ->post(route('exams.duplicate', $exam));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseCount('exams', 2);
        $this->assertDatabaseCount('questions', 6);
    }

    #[Test]
    public function teacher_cannot_duplicate_other_teacher_exam()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $otherTeacher = $this->createTeacher(['email' => 'other@test.com']);

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id,
        ]);

        $response = $this->actingAs($teacher)
            ->post(route('exams.duplicate', $otherExam));

        $response->assertForbidden();
    }

    // ==================== TOGGLE ACTIVE ====================

    #[Test]
    public function teacher_can_toggle_exam_active_status()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Test Exam',
            'is_active' => true,
        ]);

        $this->assertTrue($exam->is_active);

        $response = $this->actingAs($teacher)
            ->patch(route('exams.toggle-active', $exam));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $exam->refresh();
        $this->assertFalse($exam->is_active);
    }

    #[Test]
    public function teacher_cannot_toggle_other_teacher_exam()
    {
        $teacher = $this->createTeacher(['email' => 'teacher@test.com']);
        $otherTeacher = $this->createTeacher(['email' => 'other@test.com']);

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($teacher)
            ->patch(route('exams.toggle-active', $otherExam));

        $response->assertForbidden();

        $otherExam->refresh();
        $this->assertTrue($otherExam->is_active);
    }

    // ==================== AUTHORIZATION ====================

    #[Test]
    public function student_cannot_access_teacher_exam_routes()
    {
        $student = $this->createStudent(['email' => 'student@test.com']);

        $response = $this->actingAs($student)
            ->get(route('exams.index'));

        $response->assertForbidden();
    }

    #[Test]
    public function guest_cannot_access_teacher_exam_routes()
    {
        $response = $this->get(route('exams.index'));

        $response->assertRedirect(route('login'));
    }
}
