<?php

namespace Tests\Feature\Controllers\Exam;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Group;
use App\Models\ExamAssignment;
use Spatie\Permission\Models\Role;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamAssignmentControllerTest extends TestCase
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

    #[Test]
    public function teacher_can_view_exam_assignment_form()
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('exams.assign.show', $this->exam));

        $response->assertOk();
        $response->assertInertia(
            fn(AssertableInertia $page) => $page
                ->component('Exam/Assign', false)
                ->has('exam')
                ->has('students')
                ->has('assignedGroups')
                ->has('availableGroups')
        );
    }

    #[Test]
    public function teacher_can_assign_exam_to_students()
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('exams.assign.store', $this->exam), [
                'student_ids' => [$this->student->id]
            ]);

        $response->assertRedirect(route('exams.show', $this->exam));
        $response->assertSessionHas('success');

        // Vérifier que l'assignation a été créée
        $this->assertDatabaseHas('exam_assignments', [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'assigned'
        ]);
    }

    #[Test]
    public function teacher_cannot_assign_exam_to_invalid_students()
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('exams.assign.store', $this->exam), [
                'student_ids' => [999] // ID qui n'existe pas
            ]);

        $response->assertSessionHasErrors('student_ids.0');
    }

    #[Test]
    public function teacher_can_assign_exam_to_multiple_students()
    {
        $student2 = User::factory()->create();
        $student2->assignRole('student');

        $student3 = User::factory()->create();
        $student3->assignRole('student');

        $response = $this->actingAs($this->teacher)
            ->post(route('exams.assign.store', $this->exam), [
                'student_ids' => [$this->student->id, $student2->id, $student3->id]
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Vérifier que les 3 assignations ont été créées
        $this->assertDatabaseCount('exam_assignments', 3);
    }

    #[Test]
    public function teacher_cannot_assign_exam_twice_to_same_student()
    {
        // Première assignation
        $this->actingAs($this->teacher)
            ->post(route('exams.assign.store', $this->exam), [
                'student_ids' => [$this->student->id]
            ]);

        // Tentative de réassignation
        $response = $this->actingAs($this->teacher)
            ->post(route('exams.assign.store', $this->exam), [
                'student_ids' => [$this->student->id]
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Vérifier qu'il n'y a toujours qu'une seule assignation
        $this->assertDatabaseCount('exam_assignments', 1);
    }

    #[Test]
    public function teacher_can_view_exam_assignments_list()
    {
        // Créer quelques assignations
        ExamAssignment::factory()->count(3)->create([
            'exam_id' => $this->exam->id
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('exams.assignments', $this->exam));

        $response->assertOk();
        $response->assertInertia(
            fn(AssertableInertia $page) => $page
                ->component('Exam/Assignments', false)
                ->has('exam')
                ->has('assignments')
        );
    }

    #[Test]
    public function teacher_can_filter_assignments_by_status()
    {
        // Créer des assignations avec différents statuts
        $student1 = User::factory()->create();
        $student1->assignRole('student');

        $student2 = User::factory()->create();
        $student2->assignRole('student');

        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $student1->id,
            'status' => 'assigned'
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $student2->id,
            'status' => 'submitted'
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('exams.assignments', $this->exam) . '?status=submitted');

        $response->assertOk();
        $response->assertInertia(
            fn(AssertableInertia $page) => $page
                ->component('Exam/Assignments', false)
                ->has('assignments')
        );
    }

    #[Test]
    public function teacher_can_remove_student_assignment()
    {
        // Créer une assignation
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'assigned'
        ]);

        $response = $this->actingAs($this->teacher)
            ->delete(route('exams.assignment.remove', [$this->exam, $this->student]));

        $response->assertRedirect(route('exams.show', $this->exam));
        $response->assertSessionHas('success');

        // Vérifier que l'assignation a été supprimée
        $this->assertDatabaseMissing('exam_assignments', [
            'id' => $assignment->id
        ]);
    }

    #[Test]
    public function teacher_cannot_remove_non_existent_assignment()
    {
        $response = $this->actingAs($this->teacher)
            ->delete(route('exams.assignment.remove', [$this->exam, $this->student]));

        $response->assertRedirect(route('exams.show', $this->exam));
        $response->assertSessionHas('error');
    }

    #[Test]
    public function teacher_cannot_access_other_teacher_exam_assignments()
    {
        // Créer un autre enseignant et son examen
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('exams.assign.show', $otherExam));

        $response->assertForbidden();
    }

    #[Test]
    public function student_cannot_access_assignment_routes()
    {
        $response = $this->actingAs($this->student)
            ->get(route('exams.assign.show', $this->exam));

        $response->assertForbidden();
    }
}
