<?php

namespace Tests\Feature\Controllers\Exam;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Group;
use Spatie\Permission\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamGroupAssignmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private Exam $exam;
    private Group $group;

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

        // Créer un groupe
        $this->group = Group::factory()->active()->create();

        // Ajouter l'étudiant au groupe avec enrolled_at
        $this->group->students()->attach($this->student->id, [
            'enrolled_at' => now(),
            'is_active' => true
        ]);

        // Créer un examen
        $this->exam = Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Test Exam',
            'is_active' => true
        ]);
    }

    #[Test]
    public function teacher_can_assign_exam_to_group()
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.exams.assign.groups', $this->exam), [
                'group_ids' => [$this->group->id]
            ]);

        $response->assertRedirect(route('teacher.exams.show', $this->exam));
        $response->assertSessionHas('success');

        // Vérifier que l'assignation de groupe a été créée
        $this->assertDatabaseHas('exam_group', [
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id
        ]);
    }

    #[Test]
    public function teacher_can_assign_exam_to_multiple_groups()
    {
        $group2 = Group::factory()->active()->create();
        $group3 = Group::factory()->active()->create();

        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.exams.assign.groups', $this->exam), [
                'group_ids' => [$this->group->id, $group2->id, $group3->id]
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Vérifier que les 3 assignations ont été créées
        $this->assertDatabaseCount('exam_group', 3);
    }

    #[Test]
    public function teacher_cannot_assign_exam_to_invalid_group()
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.exams.assign.groups', $this->exam), [
                'group_ids' => [999] // ID qui n'existe pas
            ]);

        $response->assertSessionHasErrors('group_ids.0');
    }

    #[Test]
    public function teacher_cannot_assign_exam_twice_to_same_group()
    {
        // Première assignation
        $this->actingAs($this->teacher)
            ->post(route('teacher.exams.assign.groups', $this->exam), [
                'group_ids' => [$this->group->id]
            ]);

        // Tentative de réassignation
        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.exams.assign.groups', $this->exam), [
                'group_ids' => [$this->group->id]
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Vérifier qu'il n'y a toujours qu'une seule assignation
        $this->assertDatabaseCount('exam_group', 1);
    }

    #[Test]
    public function teacher_can_remove_exam_from_group()
    {
        // Assigner l'examen au groupe
        $this->exam->groups()->attach($this->group->id, [
            'assigned_by' => $this->teacher->id,
            'assigned_at' => now(),
        ]);

        $response = $this->actingAs($this->teacher)
            ->delete(route('teacher.exams.groups.remove', [$this->exam, $this->group->id]));

        $response->assertSessionHas('success');

        // Vérifier que l'assignation a été supprimée
        $this->assertDatabaseMissing('exam_group', [
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id
        ]);
    }

    #[Test]
    public function teacher_cannot_remove_exam_from_non_assigned_group()
    {
        $response = $this->actingAs($this->teacher)
            ->delete(route('teacher.exams.groups.remove', [$this->exam, $this->group->id]));

        $response->assertSessionHas('error');
    }

    #[Test]
    public function teacher_cannot_access_other_teacher_exam_group_assignments()
    {
        // Créer un autre enseignant et son examen
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('teacher');

        $otherExam = Exam::factory()->create([
            'teacher_id' => $otherTeacher->id
        ]);

        $response = $this->actingAs($this->teacher)
            ->post(route('teacher.exams.assign.groups', $otherExam), [
                'group_ids' => [$this->group->id]
            ]);

        $response->assertForbidden();
    }

    #[Test]
    public function student_cannot_access_group_assignment_routes()
    {
        $response = $this->actingAs($this->student)
            ->post(route('teacher.exams.assign.groups', $this->exam), [
                'group_ids' => [$this->group->id]
            ]);

        $response->assertForbidden();
    }

    #[Test]
    public function assigning_exam_to_group_does_not_create_individual_assignments()
    {
        // Assigner l'examen au groupe
        $this->actingAs($this->teacher)
            ->post(route('teacher.exams.assign.groups', $this->exam), [
                'group_ids' => [$this->group->id]
            ]);

        // Vérifier qu'aucune assignation individuelle n'est créée automatiquement
        $this->assertDatabaseCount('exam_assignments', 0);
    }
}
