<?php

namespace Tests\Feature\Workflow;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Group;
use App\Models\Level;
use App\Models\Question;
use App\Models\ExamAssignment;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test complet du workflow d'assignation par groupes
 * 
 * Scénario testé :
 * 1. Enseignant crée un examen
 * 2. Enseignant assigne l'examen à un groupe
 * 3. Étudiant du groupe accède à l'examen
 * 4. Étudiant démarre l'examen
 * 5. Virtual assignment devient réelle
 */
class GroupAssignmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student1;
    private User $student2;
    private User $studentOutsideGroup;
    private Exam $exam;
    private Group $group;
    private Level $level;

    protected function setUp(): void
    {
        parent::setUp();

        // Utiliser le seeder pour créer les rôles et permissions
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        // Créer un niveau
        $this->level = Level::create([
            'name' => 'Niveau Test',
            'code' => 'NT1'
        ]);

        // Créer un groupe actif
        $this->group = Group::create([
            'name' => 'Groupe Test',
            'level_id' => $this->level->id,
            'academic_year' => now()->year . '-' . (now()->year + 1),
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true
        ]);

        // Créer un enseignant
        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');

        // Créer des étudiants
        $this->student1 = User::factory()->create(['name' => 'Étudiant 1']);
        $this->student1->assignRole('student');

        $this->student2 = User::factory()->create(['name' => 'Étudiant 2']);
        $this->student2->assignRole('student');

        $this->studentOutsideGroup = User::factory()->create(['name' => 'Étudiant Hors Groupe']);
        $this->studentOutsideGroup->assignRole('student');

        // Assigner les étudiants au groupe
        $this->group->students()->attach($this->student1->id, [
            'is_active' => true,
            'enrolled_at' => now()
        ]);
        $this->group->students()->attach($this->student2->id, [
            'is_active' => true,
            'enrolled_at' => now()
        ]);

        // Créer un examen avec questions
        /** @var Exam $exam */
        $exam = Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Examen de Test',
            'description' => 'Description test',
            'duration' => 60,
            'is_active' => true,
            'start_time' => now()->subHour(), // Disponible depuis 1h
            'end_time' => now()->addDay() // Disponible jusqu'à demain
        ]);

        $this->exam = $exam;

        // Ajouter des questions
        Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'content' => 'Question 1',
            'points' => 10
        ]);
    }

    #[Test]
    public function teacher_can_assign_exam_to_group()
    {
        $this->actingAs($this->teacher);

        $response = $this->post(route('exams.assign.groups', $this->exam), [
            'group_ids' => [$this->group->id]
        ]);

        $response->assertRedirect(route('exams.show', $this->exam));
        $response->assertSessionHas('success');

        // Vérifier que l'assignation existe dans exam_group
        $this->assertDatabaseHas('exam_group', [
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id
        ]);
    }

    #[Test]
    public function students_in_assigned_group_can_access_exam()
    {
        // Assigner l'examen au groupe
        DB::table('exam_group')->insert([
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id,
            'assigned_by' => $this->teacher->id,
            'assigned_at' => now()
        ]);

        // Étudiant 1 doit pouvoir accéder
        $this->actingAs($this->student1);
        $response = $this->get(route('student.exams.show', $this->exam));
        $response->assertOk();

        // Étudiant 2 doit pouvoir accéder
        $this->actingAs($this->student2);
        $response = $this->get(route('student.exams.show', $this->exam));
        $response->assertOk();
    }

    #[Test]
    public function student_outside_group_cannot_access_exam()
    {
        // Assigner l'examen au groupe
        DB::table('exam_group')->insert([
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id,
            'assigned_by' => $this->teacher->id,
            'assigned_at' => now()
        ]);

        // Étudiant hors groupe ne doit PAS pouvoir accéder
        $this->actingAs($this->studentOutsideGroup);
        $response = $this->get(route('student.exams.show', $this->exam));
        $response->assertStatus(403); // Forbidden
    }

    #[Test]
    public function virtual_assignment_is_created_for_student_in_group()
    {
        // Assigner l'examen au groupe
        DB::table('exam_group')->insert([
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id,
            'assigned_by' => $this->teacher->id,
            'assigned_at' => now()
        ]);

        // Vérifier qu'il n'y a PAS encore d'ExamAssignment en DB
        $this->assertDatabaseMissing('exam_assignments', [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student1->id
        ]);

        // L'étudiant peut quand même voir l'examen dans sa liste
        $this->actingAs($this->student1);
        $response = $this->get(route('student.exams.index'));
        $response->assertOk();
    }

    #[Test]
    public function real_assignment_is_created_when_student_starts_exam()
    {
        // Assigner l'examen au groupe
        DB::table('exam_group')->insert([
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id,
            'assigned_by' => $this->teacher->id,
            'assigned_at' => now()
        ]);

        // L'étudiant démarre l'examen
        $this->actingAs($this->student1);
        $response = $this->get(route('student.exams.take', $this->exam));

        $this->assertTrue(
            in_array($response->status(), [200, 302]),
            "Expected status 200 or 302, got {$response->status()}"
        );

        $this->assertDatabaseHas('exam_assignments', [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student1->id,
        ]);

        $assignment = ExamAssignment::where('exam_id', $this->exam->id)
            ->where('student_id', $this->student1->id)
            ->first();

        $this->assertNotNull($assignment->started_at);
    }

    #[Test]
    public function teacher_can_view_group_assignments()
    {
        // Assigner l'examen au groupe
        DB::table('exam_group')->insert([
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id,
            'assigned_by' => $this->teacher->id,
            'assigned_at' => now()
        ]);

        $this->actingAs($this->teacher);
        $response = $this->get(route('exams.groups', $this->exam));

        $response->assertOk();
        $response->assertInertia(
            fn($page) =>
            $page->component('Exam/Assignments', false) // false = ne pas vérifier l'existence du fichier
                ->has('assignedGroups', 1)
                ->where('assignedGroups.0.id', $this->group->id)
        );
    }

    #[Test]
    public function statistics_reflect_group_based_assignments()
    {
        // Assigner l'examen au groupe (2 étudiants)
        DB::table('exam_group')->insert([
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id,
            'assigned_by' => $this->teacher->id,
            'assigned_at' => now()
        ]);

        ExamAssignment::create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student1->id,
            'status' => null,
            'started_at' => now()
        ]);

        $this->actingAs($this->teacher);
        $response = $this->get(route('exams.groups', $this->exam));

        $response->assertOk();
        $response->assertInertia(
            fn($page) =>
            $page->component('Exam/Assignments', false)
                ->where('stats.total_assigned', 2)
                ->where('stats.in_progress', 1)
                ->where('stats.not_started', 1)
        );
    }

    #[Test]
    public function teacher_can_remove_group_from_exam()
    {
        // Assigner l'examen au groupe
        DB::table('exam_group')->insert([
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id,
            'assigned_by' => $this->teacher->id,
            'assigned_at' => now()
        ]);

        $this->actingAs($this->teacher);
        $response = $this->delete(route('exams.groups.remove', [
            'exam' => $this->exam,
            'group' => $this->group
        ]));

        $response->assertSessionHas('success');

        // Vérifier que l'assignation a été supprimée
        $this->assertDatabaseMissing('exam_group', [
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id
        ]);
    }

    #[Test]
    public function after_group_removal_students_lose_access()
    {
        // Assigner puis retirer
        DB::table('exam_group')->insert([
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id,
            'assigned_by' => $this->teacher->id,
            'assigned_at' => now()
        ]);

        // Retirer le groupe
        $this->actingAs($this->teacher);
        $this->delete(route('exams.groups.remove', [
            'exam' => $this->exam,
            'group' => $this->group
        ]));

        // L'étudiant ne doit plus pouvoir accéder
        $this->actingAs($this->student1);
        $response = $this->get(route('student.exams.show', $this->exam));
        $response->assertStatus(403);
    }

    #[Test]
    public function inactive_students_in_group_cannot_access()
    {
        // Assigner l'examen au groupe
        DB::table('exam_group')->insert([
            'exam_id' => $this->exam->id,
            'group_id' => $this->group->id,
            'assigned_by' => $this->teacher->id,
            'assigned_at' => now()
        ]);

        // Désactiver l'étudiant 1 dans le groupe
        $this->student1->groups()->updateExistingPivot($this->group->id, [
            'is_active' => false
        ]);

        // Rafraîchir le modèle pour s'assurer que les changements sont pris en compte
        $this->student1->refresh();

        // L'étudiant 1 ne doit plus pouvoir accéder
        $this->actingAs($this->student1);
        $response = $this->get(route('student.exams.show', $this->exam));
        $response->assertStatus(403);

        // L'étudiant 2 (toujours actif) peut toujours accéder
        $this->actingAs($this->student2);
        $response = $this->get(route('student.exams.show', $this->exam));
        $response->assertOk();
    }
}
