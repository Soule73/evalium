<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Question;
use Spatie\Permission\Models\Role;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les rôles
        Role::create(['name' => 'teacher']);
        Role::create(['name' => 'student']);

        // Créer un enseignant
        $this->teacher = User::factory()->create([
            'email' => 'teacher@test.com',
        ]);
        $this->teacher->assignRole('teacher');

        // Créer un étudiant
        $this->student = User::factory()->create([
            'email' => 'student@test.com',
        ]);
        $this->student->assignRole('student');

        // Créer un examen
        $this->exam = Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Test Exam',
            'is_active' => true
        ]);
    }

    // ==================== INDEX ====================

    #[Test]
    public function teacher_can_access_exam_index()
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('exams.index'));

        $response->assertOk();
        $response->assertInertia(
            fn(AssertableInertia $page) => $page
                ->component('Exam/Index', false)
                ->has('exams')
        );
    }

    #[Test]
    public function teacher_can_view_own_exams_only()
    {
        // Créer un autre enseignant avec son examen
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');

        Exam::factory()->create([
            'teacher_id' => $otherTeacher->id,
            'title' => 'Other Teacher Exam'
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('exams.index'));

        $response->assertOk();
        $response->assertInertia(
            fn(AssertableInertia $page) => $page
                ->component('Exam/Index', false)
                ->where('exams.data', function ($exams) {
                    // Vérifier que tous les examens appartiennent au teacher connecté
                    return collect($exams)->every(fn($exam) => $exam['teacher_id'] === $this->teacher->id);
                })
        );
    }

    // ==================== CREATE ====================

    #[Test]
    public function teacher_can_view_create_form()
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('exams.create'));

        $response->assertOk();
        $response->assertInertia(
            fn(AssertableInertia $page) => $page
                ->component('Exam/Create', false)
        );
    }

    // ==================== STORE ====================

    #[Test]
    public function teacher_can_create_exam()
    {
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
                    'order_index' => 1
                ]
            ]
        ];

        $response = $this->actingAs($this->teacher)
            ->post(route('exams.store'), $examData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('exams', [
            'title' => 'New Exam',
            'teacher_id' => $this->teacher->id
        ]);
    }

    #[Test]
    public function teacher_cannot_create_exam_with_invalid_data()
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('exams.store'), [
                'title' => '', // Titre vide
                'duration' => -10 // Duration négative
            ]);

        $response->assertSessionHasErrors(['title', 'duration']);
    }

    // ==================== SHOW ====================

    #[Test]
    public function teacher_can_view_exam_details()
    {
        // Créer des questions pour l'examen
        Question::factory()->count(3)->create([
            'exam_id' => $this->exam->id
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('exams.show', $this->exam));

        $response->assertOk();
        $response->assertInertia(
            fn(AssertableInertia $page) => $page
                ->component('Exam/Show', false)
                ->has('exam')
                ->has('exam.questions', 3)
        );
    }

    #[Test]
    public function teacher_cannot_view_other_teacher_exam()
    {
        // Créer un autre enseignant et son examen
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('exams.show', $otherExam));

        $response->assertForbidden();
    }

    // ==================== EDIT ====================

    #[Test]
    public function teacher_can_view_edit_form()
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('exams.edit', $this->exam));

        $response->assertOk();
        $response->assertInertia(
            fn(AssertableInertia $page) => $page
                ->component('Exam/Edit', false)
                ->has('exam')
        );
    }

    #[Test]
    public function teacher_cannot_edit_other_teacher_exam()
    {
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('exams.edit', $otherExam));

        $response->assertForbidden();
    }

    // ==================== UPDATE ====================

    #[Test]
    public function teacher_can_update_exam()
    {
        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'duration' => 90,
            'start_date' => $this->exam->start_date,
            'end_date' => $this->exam->end_date,
            'is_active' => false,
            'questions' => [
                [
                    'content' => 'Updated Question?',
                    'type' => 'text',
                    'points' => 15,
                    'order_index' => 1
                ]
            ]
        ];

        $response = $this->actingAs($this->teacher)
            ->put(route('exams.update', $this->exam), $updateData);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('exams', [
            'id' => $this->exam->id,
            'title' => 'Updated Title',
            'duration' => 90
        ]);
    }

    #[Test]
    public function teacher_cannot_update_other_teacher_exam()
    {
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id
        ]);

        $response = $this->actingAs($this->teacher)
            ->put(route('exams.update', $otherExam), [
                'title' => 'Hacked Title'
            ]);

        $response->assertForbidden();
    }

    // ==================== DESTROY ====================

    #[Test]
    public function teacher_can_delete_exam()
    {
        $response = $this->actingAs($this->teacher)
            ->delete(route('exams.destroy', $this->exam));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('exams', [
            'id' => $this->exam->id
        ]);
    }

    #[Test]
    public function teacher_cannot_delete_other_teacher_exam()
    {
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id
        ]);

        $response = $this->actingAs($this->teacher)
            ->delete(route('exams.destroy', $otherExam));

        $response->assertForbidden();

        // Vérifier que l'examen n'a PAS été supprimé (il doit toujours exister)
        $this->assertNotSoftDeleted('exams', [
            'id' => $otherExam->id
        ]);
    }

    // ==================== DUPLICATE ====================

    #[Test]
    public function teacher_can_duplicate_exam()
    {
        // Créer des questions pour l'examen original
        Question::factory()->count(3)->create([
            'exam_id' => $this->exam->id
        ]);

        $response = $this->actingAs($this->teacher)
            ->post(route('exams.duplicate', $this->exam));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Vérifier qu'un nouvel examen a été créé
        $this->assertDatabaseCount('exams', 2);

        // Vérifier que les questions ont été dupliquées
        $this->assertDatabaseCount('questions', 6);
    }

    #[Test]
    public function teacher_cannot_duplicate_other_teacher_exam()
    {
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id
        ]);

        $response = $this->actingAs($this->teacher)
            ->post(route('exams.duplicate', $otherExam));

        $response->assertForbidden();
    }

    // ==================== TOGGLE ACTIVE ====================

    #[Test]
    public function teacher_can_toggle_exam_active_status()
    {
        $this->assertTrue($this->exam->is_active);

        $response = $this->actingAs($this->teacher)
            ->patch(route('exams.toggle-active', $this->exam));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->exam->refresh();
        $this->assertFalse($this->exam->is_active);
    }

    #[Test]
    public function teacher_cannot_toggle_other_teacher_exam()
    {
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id,
            'is_active' => true
        ]);

        $response = $this->actingAs($this->teacher)
            ->patch(route('exams.toggle-active', $otherExam));

        $response->assertForbidden();

        $otherExam->refresh();
        $this->assertTrue($otherExam->is_active);
    }

    // ==================== AUTHORIZATION ====================

    #[Test]
    public function student_cannot_access_teacher_exam_routes()
    {
        $response = $this->actingAs($this->student)
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
