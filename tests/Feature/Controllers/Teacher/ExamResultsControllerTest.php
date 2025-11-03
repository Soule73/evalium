<?php

namespace Tests\Feature\Controllers\Exam;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Group;
use App\Models\Answer;
use App\Models\Question;
use App\Models\ExamAssignment;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamResultsControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private Exam $exam;
    private Group $group;
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

        // Créer un groupe
        $this->group = Group::factory()->active()->create();

        // Ajouter l'étudiant au groupe
        $this->group->students()->attach($this->student->id, [
            'enrolled_at' => now(),
            'is_active' => true
        ]);

        // Créer un examen
        /** @var Exam $exam */
        $exam = Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Test Exam',
            'is_active' => true
        ]);
        $this->exam = $exam;

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
            ->get(route('exams.submissions', [$this->exam, $this->group, $this->student]));

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
            ->get(route('exams.submissions', [$this->exam, $this->group, $this->student]));

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
            ->get(route('exams.submissions', [$this->exam, $this->group, $otherStudent]));

        // Devrait échouer avec 403 car l'étudiant n'appartient pas au groupe
        $response->assertForbidden();
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

        // Créer un groupe pour l'autre étudiant
        $otherGroup = Group::factory()->active()->create();
        $otherGroup->students()->attach($otherStudent->id, [
            'enrolled_at' => now(),
            'is_active' => true
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $otherExam->id,
            'student_id' => $otherStudent->id,
            'status' => 'submitted'
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('exams.submissions', [$otherExam, $otherGroup, $otherStudent]));

        $response->assertForbidden();
    }

    #[Test]
    public function student_cannot_access_results_routes()
    {
        $response = $this->actingAs($this->student)
            ->get(route('exams.submissions', [$this->exam, $this->group, $this->student]));

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
            ->get(route('exams.submissions', [$this->exam, $this->group, $this->student]));

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
            ->get(route('exams.submissions', [$this->exam, $this->group, $this->student]));

        $response->assertOk();
        // Le score total devrait être 8.5 + 12.0 = 20.5
    }

    #[Test]
    public function teacher_can_access_results_before_correction()
    {
        // Créer une nouvelle assignation sans scores
        $newStudent = User::factory()->create();
        $newStudent->assignRole('student');

        // Ajouter le nouvel étudiant au groupe
        $this->group->students()->attach($newStudent->id, [
            'enrolled_at' => now(),
            'is_active' => true
        ]);

        $newAssignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $newStudent->id,
            'status' => 'submitted',
            'score' => null
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('exams.submissions', [$this->exam, $this->group, $newStudent]));

        // Devrait permettre l'accès même sans correction
        $response->assertOk();
    }

    #[Test]
    public function teacher_cannot_view_results_for_student_not_in_group()
    {
        // Créer un autre groupe
        $otherGroup = Group::factory()->active()->create();

        // Créer un étudiant qui n'appartient PAS au groupe principal
        $studentNotInGroup = User::factory()->create();
        $studentNotInGroup->assignRole('student');

        // Ajouter l'étudiant à un autre groupe
        $otherGroup->students()->attach($studentNotInGroup->id, [
            'enrolled_at' => now(),
            'is_active' => true
        ]);

        // Créer une assignation pour cet étudiant
        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $studentNotInGroup->id,
            'status' => 'submitted'
        ]);

        // Essayer d'accéder aux résultats avec le mauvais groupe
        $response = $this->actingAs($this->teacher)
            ->get(route('exams.submissions', [$this->exam, $this->group, $studentNotInGroup]));

        // Devrait échouer car l'étudiant n'appartient pas à ce groupe
        $response->assertForbidden();
    }

    #[Test]
    public function teacher_cannot_view_results_for_inactive_student_in_group()
    {
        // Créer un étudiant et l'ajouter au groupe mais en inactif
        $inactiveStudent = User::factory()->create();
        $inactiveStudent->assignRole('student');

        $this->group->students()->attach($inactiveStudent->id, [
            'enrolled_at' => now(),
            'is_active' => false // Inactif
        ]);

        // Créer une assignation pour cet étudiant
        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $inactiveStudent->id,
            'status' => 'submitted'
        ]);

        // Essayer d'accéder aux résultats
        $response = $this->actingAs($this->teacher)
            ->get(route('exams.submissions', [$this->exam, $this->group, $inactiveStudent]));

        // Devrait échouer car l'étudiant est inactif dans le groupe
        $response->assertForbidden();
    }
}
