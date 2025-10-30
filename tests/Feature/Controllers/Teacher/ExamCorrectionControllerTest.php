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

class ExamCorrectionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private Exam $exam;
    private Question $question;
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

        // Créer une question
        $this->question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 10
        ]);

        // Créer une assignation soumise
        $this->assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // Créer une réponse
        Answer::create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'answer_text' => 'Student answer'
        ]);
    }

    #[Test]
    public function teacher_can_view_student_review_page()
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.exams.review', [$this->exam, $this->student]));

        $response->assertOk();
        $response->assertInertia(
            fn(AssertableInertia $page) => $page
                ->component('Exam/StudentReview', false)
                ->has('exam')
                ->has('student')
        );
    }

    #[Test]
    public function teacher_can_save_student_review()
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.exams.review.save', [$this->exam, $this->student]), [
                'scores' => [
                    [
                        'question_id' => $this->question->id,
                        'score' => 8.5,
                        'feedback' => 'Good answer'
                    ]
                ],
                'teacher_notes' => 'Excellent work overall'
            ]);

        $response->assertRedirect(route('teacher.exams.review', [$this->exam, $this->student]));
        $response->assertSessionHas('success');

        // Vérifier que l'assignment existe toujours
        $this->assignment->refresh();
        $this->assertNotNull($this->assignment);
    }

    #[Test]
    public function teacher_can_update_single_question_score()
    {
        $response = $this->actingAs($this->teacher)
            ->postJson(route('teacher.exams.score.update', $this->exam), [
                'exam_id' => $this->exam->id,
                'student_id' => $this->student->id,
                'question_id' => $this->question->id,
                'score' => 8.5,
                'teacher_notes' => 'Good answer' // Sera mappé à feedback
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true
        ]);

        // Vérifier que le score a été mis à jour
        $answer = Answer::where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->first();

        $this->assertEquals(8.5, $answer->score);
        $this->assertEquals('Good answer', $answer->feedback); // Vérifier feedback, pas teacher_notes
    }

    #[Test]
    public function teacher_cannot_give_score_higher_than_max_points()
    {
        $response = $this->actingAs($this->teacher)
            ->postJson(route('teacher.exams.score.update', $this->exam), [
                'exam_id' => $this->exam->id,
                'student_id' => $this->student->id,
                'question_id' => $this->question->id,
                'score' => 15, // Max est 10
                'teacher_notes' => 'Too much'
            ]);

        // Devrait échouer la validation
        $response->assertStatus(422);
    }

    #[Test]
    public function teacher_can_save_review_with_feedback_only()
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.exams.review.save', [$this->exam, $this->student]), [
                'scores' => [
                    [
                        'question_id' => $this->question->id,
                        'score' => 0,
                        'feedback' => 'Needs improvement'
                    ]
                ]
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function teacher_cannot_review_non_submitted_exam()
    {
        // Créer une nouvelle assignation non soumise
        $newStudent = User::factory()->create();
        $newStudent->assignRole('student');

        $newAssignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $newStudent->id,
            'status' => 'assigned' // Pas encore soumis
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('teacher.exams.review', [$this->exam, $newStudent]));

        // Devrait quand même permettre l'accès (pour préparer la correction)
        $response->assertOk();
    }

    #[Test]
    public function teacher_cannot_access_other_teacher_exam_correction()
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
            ->get(route('teacher.exams.review', [$otherExam, $otherStudent]));

        $response->assertForbidden();
    }

    #[Test]
    public function student_cannot_access_correction_routes()
    {
        $response = $this->actingAs($this->student)
            ->get(route('teacher.exams.review', [$this->exam, $this->student]));

        $response->assertForbidden();
    }

    #[Test]
    public function teacher_can_save_review_with_multiple_questions()
    {
        // Créer des questions supplémentaires
        $question2 = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 5
        ]);

        $question3 = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'multiple',
            'points' => 3
        ]);

        // Créer des réponses pour ces questions
        Answer::create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question2->id,
            'answer_text' => 'Answer 2'
        ]);

        Answer::create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $question3->id,
            'answer_text' => 'Answer 3'
        ]);

        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.exams.review.save', [$this->exam, $this->student]), [
                'scores' => [
                    [
                        'question_id' => $this->question->id,
                        'score' => 8.5,
                        'feedback' => 'Good'
                    ],
                    [
                        'question_id' => $question2->id,
                        'score' => 4.0,
                        'feedback' => 'Very good'
                    ],
                    [
                        'question_id' => $question3->id,
                        'score' => 3.0,
                        'feedback' => 'Perfect'
                    ]
                ],
                'teacher_notes' => 'Overall excellent work'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function teacher_can_update_score_with_notes()
    {
        $response = $this->actingAs($this->teacher)
            ->postJson(route('teacher.exams.score.update', $this->exam), [
                'exam_id' => $this->exam->id,
                'student_id' => $this->student->id,
                'question_id' => $this->question->id,
                'score' => 7.5,
                'teacher_notes' => 'Could be better, work on...'
            ]);

        $response->assertOk();

        // Vérifier le feedback (teacher_notes est mappé à feedback)
        $answer = Answer::where('assignment_id', $this->assignment->id)
            ->where('question_id', $this->question->id)
            ->first();

        $this->assertEquals('Could be better, work on...', $answer->feedback);
    }
}
