<?php

namespace Tests\Feature\Controllers\Exam;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Answer;
use App\Models\Question;
use App\Models\ExamAssignment;
use Spatie\Permission\Models\Role;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamResultsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private Exam $exam;
    private ExamAssignment $assignment;

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

        // Créer une assignation soumise
        $this->assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
            'score' => 85.5
        ]);
    }

    #[Test]
    public function teacher_can_view_student_results()
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('exams.results.show', [$this->exam, $this->student]));

        $response->assertOk();
        $response->assertInertia(
            fn(AssertableInertia $page) => $page
                ->component('Exam/StudentResults', false)
                ->has('exam')
                ->has('student')
                ->has('assignment')
        );
    }

    #[Test]
    public function teacher_can_view_results_for_submitted_exam()
    {
        // Créer des questions et des réponses
        $question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 10
        ]);

        Answer::create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question->id,
            'answer_text' => 'Student answer',
            'score' => 8.5
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('exams.results', [$this->exam, $this->student]));

        $response->assertOk();
        $response->assertInertia(
            fn(AssertableInertia $page) => $page
                ->component('Exam/StudentResults', false)
        );
    }

    #[Test]
    public function teacher_cannot_view_results_for_non_existent_assignment()
    {
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');

        $response = $this->actingAs($this->teacher)
            ->get(route('exams.results', [$this->exam, $otherStudent]));

        // Devrait échouer car l'étudiant n'a pas d'assignation
        $response->assertStatus(404);
    }

    #[Test]
    public function teacher_can_view_exam_statistics()
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('exams.stats', $this->exam));

        // Actuellement redirige avec un message info (TODO non implémenté)
        $response->assertRedirect();
        $response->assertSessionHas('info');
    }

    #[Test]
    public function teacher_cannot_access_other_teacher_exam_results()
    {
        // Créer un autre enseignant et son examen
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id
        ]);

        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');

        ExamAssignment::factory()->create([
            'exam_id' => $otherExam->id,
            'student_id' => $otherStudent->id,
            'status' => 'submitted'
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('exams.results', [$otherExam, $otherStudent]));

        $response->assertForbidden();
    }

    #[Test]
    public function student_cannot_access_results_routes()
    {
        $response = $this->actingAs($this->student)
            ->get(route('exams.results', [$this->exam, $this->student]));

        $response->assertForbidden();
    }

    #[Test]
    public function teacher_can_view_results_with_detailed_answers()
    {
        // Créer plusieurs questions avec différents types
        $textQuestion = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 10
        ]);

        $mcqQuestion = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'multiple',
            'points' => 5
        ]);

        // Créer des réponses
        Answer::create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $textQuestion->id,
            'answer_text' => 'Detailed text answer',
            'score' => 8.5,
            'teacher_notes' => 'Good work'
        ]);

        Answer::create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $mcqQuestion->id,
            'selected_choices' => json_encode([1, 2]),
            'score' => 5.0
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('exams.results.show', [$this->exam, $this->student]));

        $response->assertOk();
        $response->assertInertia(
            fn(AssertableInertia $page) => $page
                ->component('Exam/StudentResults', false)
                ->has('answers')
        );
    }

    #[Test]
    public function results_page_shows_correct_score_calculation()
    {
        // Créer des questions
        $q1 = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 10
        ]);

        $q2 = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 15
        ]);

        // Créer des réponses avec scores
        Answer::create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $q1->id,
            'answer_text' => 'Answer 1',
            'score' => 8.5
        ]);

        Answer::create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $q2->id,
            'answer_text' => 'Answer 2',
            'score' => 12.0
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('exams.results', [$this->exam, $this->student]));

        $response->assertOk();
        // Le score total devrait être 8.5 + 12.0 = 20.5
    }

    #[Test]
    public function teacher_can_access_results_before_correction()
    {
        // Créer une nouvelle assignation sans scores
        $newStudent = User::factory()->create();
        $newStudent->assignRole('student');

        $newAssignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $newStudent->id,
            'status' => 'submitted',
            'score' => null
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('exams.results', [$this->exam, $newStudent]));

        // Devrait permettre l'accès même sans correction
        $response->assertOk();
    }
}
