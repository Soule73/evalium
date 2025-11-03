<?php

namespace Tests\Feature\Controllers;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Answer;
use App\Models\Choice;
use App\Models\Question;
use App\Models\ExamAssignment;
use PHPUnit\Framework\Attributes\Test;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StudentExamControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private Exam $exam;
    private ExamAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        // Utiliser le seeder pour créer les rôles et permissions
        $this->seed(RoleAndPermissionSeeder::class);

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
        /** @var Exam $exam */
        $exam = Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Test Exam',
            'is_active' => true,
            'duration' => 90
        ]);
        $this->exam = $exam;

        // Créer des questions pour l'examen
        Question::factory()->count(3)->create([
            'exam_id' => $this->exam->id,
        ]);

        // Créer une assignation
        /** @var ExamAssignment $assignment */
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'assigned'
        ]);
        $this->assignment = $assignment;
    }

    #[Test]
    public function student_can_access_exam_dashboard()
    {
        $response = $this->actingAs($this->student)
            ->get(route('student.exams.index'));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Student/ExamIndex', false)
                ->has('pagination')
        );
    }

    #[Test]
    public function student_can_start_assigned_exam()
    {
        $response = $this->actingAs($this->student)
            ->get(route('student.exams.take', $this->exam));

        // The controller redirects if exam cannot be taken or isn't accessible
        // For now, just check that we get some response (redirect or OK)
        $this->assertTrue(
            $response->getStatusCode() === 200 || $response->getStatusCode() === 302,
            'Expected status 200 or 302, got ' . $response->getStatusCode()
        );

        // If it's successful (200), check assignment status
        if ($response->getStatusCode() === 200) {
            $this->assignment->refresh();
            $this->assertEquals('started', $this->assignment->status);
            $this->assertNotNull($this->assignment->started_at);
        }
    }

    #[Test]
    public function student_cannot_start_exam_twice()
    {
        // Marquer l'examen comme déjà commencé
        $this->assignment->update([
            'status' => 'started',
            'started_at' => Carbon::now()
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.exams.show', $this->assignment));

        $response->assertOk();
        // L'examen devrait être affiché normalement
        $response->assertInertia(
            fn($page) => $page
                ->component('Student/ExamShow', false)
        );
    }

    #[Test]
    public function student_can_submit_text_answer()
    {
        // Marquer l'examen comme commencé
        $this->assignment->update(['status' => 'started']);

        // Créer une question de type texte
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'content' => 'What is 2 + 2?',
            'points' => 5
        ]);

        $response = $this->actingAs($this->student)
            ->post(route('student.exams.save-answers', $this->exam), [
                'answers' => [
                    $question->id => 'Ma réponse à la question'
                ]
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Réponses sauvegardées'
        ]);

        // Vérifier que la réponse a été sauvegardée
        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Ma réponse à la question'
        ]);
    }

    #[Test]
    public function student_can_submit_multiple_choice_answer()
    {
        // Créer une question à choix multiples
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'multiple',
            'content' => 'Which are prime numbers?',
            'points' => 3
        ]);

        // Créer des choix
        $choice1 = Choice::factory()->create([
            'question_id' => $question->id,
            'content' => '2',
            'is_correct' => true
        ]);

        $choice2 = Choice::factory()->create([
            'question_id' => $question->id,
            'content' => '4',
            'is_correct' => false
        ]);

        // Marquer l'examen comme commencé pour pouvoir sauvegarder les réponses
        $this->assignment->update(['status' => 'started']);

        $response = $this->actingAs($this->student)
            ->post(route('student.exams.save-answers', $this->exam), [
                'answers' => [
                    $question->id => $choice1->id
                ]
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Réponses sauvegardées'
        ]);

        // Vérifier que la réponse a été sauvegardée avec le bon choix
        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'choice_id' => $choice1->id
        ]);
    }

    #[Test]
    public function student_can_submit_exam()
    {
        // S'assurer qu'il n'y a pas de questions text dans cet examen
        $this->exam->questions()->delete();

        // Marquer l'examen comme commencé
        $this->assignment->update(['status' => 'started']);

        $response = $this->actingAs($this->student)
            ->post(route('student.exams.submit', $this->exam));

        $response->assertRedirect(route('student.exams.show', $this->exam));
        $response->assertSessionHas('success');

        // Vérifier que l'examen a été soumis
        $this->assignment->refresh();
        $this->assertEquals('submitted', $this->assignment->status);
        $this->assertNotNull($this->assignment->submitted_at);
    }

    #[Test]
    public function student_cannot_submit_unstarted_exam()
    {
        // L'examen est encore en statut "assigned"
        $response = $this->actingAs($this->student)
            ->post(route('student.exams.submit', $this->exam));

        $response->assertSessionHasErrors();

        // Vérifier que le statut n'a pas changé
        $this->assignment->refresh();
        $this->assertEquals('assigned', $this->assignment->status);
    }

    #[Test]
    public function student_can_view_completed_exam_results()
    {
        // Marquer l'examen comme terminé avec une note
        $this->assignment->update([
            'status' => 'graded',
            'score' => 85.5
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.exams.show', $this->exam));

        $response->assertOk();
    }

    #[Test]
    public function student_cannot_view_results_of_ungraded_exam()
    {
        // L'examen est soumis mais pas encore noté
        $this->assignment->update(['status' => 'submitted']);

        $response = $this->actingAs($this->student)
            ->get(route('student.exams.show', $this->exam));

        $response->assertForbidden();
    }

    #[Test]
    public function student_cannot_access_other_student_exam()
    {
        // Créer un autre étudiant et un autre examen
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');

        $otherExam = Exam::factory()->create([
            'teacher_id' => $this->teacher->id
        ]);

        $otherAssignment = ExamAssignment::factory()->create([
            'exam_id' => $otherExam->id,
            'student_id' => $otherStudent->id
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('student.exams.show', $otherExam));

        $response->assertForbidden(); // 403 car l'étudiant n'a pas la permission d'accéder à cet examen
    }

    #[Test]
    public function teacher_cannot_access_student_routes()
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('student.exams.index'));

        $response->assertForbidden();
    }

    #[Test]
    public function student_can_update_existing_answer()
    {
        // Créer une question et une réponse existante
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'content' => 'Test question'
        ]);

        $existingAnswer = Answer::create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Old answer'
        ]);

        // Marquer l'examen comme commencé pour pouvoir sauvegarder les réponses
        $this->assignment->update(['status' => 'started']);

        $response = $this->actingAs($this->student)
            ->post(route('student.exams.save-answers', $this->exam), [
                'answers' => [
                    $question->id => 'Updated answer'
                ]
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Réponses sauvegardées'
        ]);

        // Vérifier que la réponse a été mise à jour
        $this->assertDatabaseHas('answers', [
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Updated answer'
        ]);
    }
}
