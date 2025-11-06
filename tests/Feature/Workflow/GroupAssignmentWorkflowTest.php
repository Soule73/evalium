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
use Tests\Traits\InteractsWithTestData;

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
    use RefreshDatabase, InteractsWithTestData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    }

    private function createGroupWithStudents(): array
    {
        $level = Level::create([
            'name' => 'Niveau Test',
            'code' => 'NT1'
        ]);

        $group = Group::create([
            'name' => 'Groupe Test',
            'level_id' => $level->id,
            'academic_year' => now()->year . '-' . (now()->year + 1),
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true
        ]);

        $teacher = $this->createTeacher();
        $student1 = $this->createStudent(['name' => 'Étudiant 1']);
        $student2 = $this->createStudent(['name' => 'Étudiant 2']);
        $studentOutsideGroup = $this->createStudent(['name' => 'Étudiant Hors Groupe']);

        $group->students()->attach($student1->id, [
            'is_active' => true,
            'enrolled_at' => now()
        ]);
        $group->students()->attach($student2->id, [
            'is_active' => true,
            'enrolled_at' => now()
        ]);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'title' => 'Examen de Test',
            'description' => 'Description test',
            'duration' => 60,
            'is_active' => true,
            'start_time' => now()->subHour(),
            'end_time' => now()->addDay()
        ]);

        Question::factory()->create([
            'exam_id' => $exam->id,
            'type' => 'text',
            'content' => 'Question 1',
            'points' => 10
        ]);

        return compact('teacher', 'student1', 'student2', 'studentOutsideGroup', 'exam', 'group', 'level');
    }

    #[Test]
    public function teacher_can_assign_exam_to_group()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group] = $this->createGroupWithStudents();

        $this->actingAs($teacher);

        $response = $this->post(route('exams.assign.groups', $exam), [
            'group_ids' => [$group->id]
        ]);

        $response->assertRedirect(route('exams.show', $exam));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('exam_group', [
            'exam_id' => $exam->id,
            'group_id' => $group->id
        ]);
    }

    #[Test]
    public function students_in_assigned_group_can_access_exam()
    {
        ['teacher' => $teacher, 'student1' => $student1, 'student2' => $student2, 'exam' => $exam, 'group' => $group] = $this->createGroupWithStudents();

        DB::table('exam_group')->insert([
            'exam_id' => $exam->id,
            'group_id' => $group->id,
            'assigned_by' => $teacher->id,
            'assigned_at' => now()
        ]);

        $this->actingAs($student1);
        $response = $this->get(route('student.exams.show', $exam));
        $response->assertOk();

        $this->actingAs($student2);
        $response = $this->get(route('student.exams.show', $exam));
        $response->assertOk();
    }

    #[Test]
    public function student_outside_group_cannot_access_exam()
    {
        ['teacher' => $teacher, 'studentOutsideGroup' => $studentOutsideGroup, 'exam' => $exam, 'group' => $group] = $this->createGroupWithStudents();

        DB::table('exam_group')->insert([
            'exam_id' => $exam->id,
            'group_id' => $group->id,
            'assigned_by' => $teacher->id,
            'assigned_at' => now()
        ]);

        $this->actingAs($studentOutsideGroup);
        $response = $this->get(route('student.exams.show', $exam));
        $response->assertStatus(403);
    }

    #[Test]
    public function virtual_assignment_is_created_for_student_in_group()
    {
        ['teacher' => $teacher, 'student1' => $student1, 'exam' => $exam, 'group' => $group] = $this->createGroupWithStudents();

        DB::table('exam_group')->insert([
            'exam_id' => $exam->id,
            'group_id' => $group->id,
            'assigned_by' => $teacher->id,
            'assigned_at' => now()
        ]);

        $this->assertDatabaseMissing('exam_assignments', [
            'exam_id' => $exam->id,
            'student_id' => $student1->id
        ]);

        $this->actingAs($student1);
        $response = $this->get(route('student.exams.index'));
        $response->assertOk();
    }

    #[Test]
    public function real_assignment_is_created_when_student_starts_exam()
    {
        ['teacher' => $teacher, 'student1' => $student1, 'exam' => $exam, 'group' => $group] = $this->createGroupWithStudents();

        DB::table('exam_group')->insert([
            'exam_id' => $exam->id,
            'group_id' => $group->id,
            'assigned_by' => $teacher->id,
            'assigned_at' => now()
        ]);

        $this->actingAs($student1);
        $response = $this->get(route('student.exams.take', $exam));

        $this->assertTrue(
            in_array($response->status(), [200, 302]),
            "Expected status 200 or 302, got {$response->status()}"
        );

        $this->assertDatabaseHas('exam_assignments', [
            'exam_id' => $exam->id,
            'student_id' => $student1->id,
        ]);

        $assignment = ExamAssignment::where('exam_id', $exam->id)
            ->where('student_id', $student1->id)
            ->first();

        $this->assertNotNull($assignment->started_at);
    }

    #[Test]
    public function teacher_can_view_group_assignments()
    {
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group] = $this->createGroupWithStudents();

        DB::table('exam_group')->insert([
            'exam_id' => $exam->id,
            'group_id' => $group->id,
            'assigned_by' => $teacher->id,
            'assigned_at' => now()
        ]);

        $this->actingAs($teacher);
        $response = $this->get(route('exams.groups', $exam));

        $response->assertOk();
        $response->assertInertia(
            fn($page) =>
            $page->component('Exam/Assignments', false)
                ->has('assignedGroups', 1)
                ->where('assignedGroups.0.id', $group->id)
        );
    }

    #[Test]
    public function statistics_reflect_group_based_assignments()
    {
        ['teacher' => $teacher, 'student1' => $student1, 'exam' => $exam, 'group' => $group] = $this->createGroupWithStudents();

        DB::table('exam_group')->insert([
            'exam_id' => $exam->id,
            'group_id' => $group->id,
            'assigned_by' => $teacher->id,
            'assigned_at' => now()
        ]);

        ExamAssignment::create([
            'exam_id' => $exam->id,
            'student_id' => $student1->id,
            'status' => null,
            'started_at' => now()
        ]);

        $this->actingAs($teacher);
        $response = $this->get(route('exams.groups', $exam));

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
        ['teacher' => $teacher, 'exam' => $exam, 'group' => $group] = $this->createGroupWithStudents();

        DB::table('exam_group')->insert([
            'exam_id' => $exam->id,
            'group_id' => $group->id,
            'assigned_by' => $teacher->id,
            'assigned_at' => now()
        ]);

        $this->actingAs($teacher);
        $response = $this->delete(route('exams.groups.remove', [
            'exam' => $exam,
            'group' => $group
        ]));

        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('exam_group', [
            'exam_id' => $exam->id,
            'group_id' => $group->id
        ]);
    }

    #[Test]
    public function after_group_removal_students_lose_access()
    {
        ['teacher' => $teacher, 'student1' => $student1, 'exam' => $exam, 'group' => $group] = $this->createGroupWithStudents();

        DB::table('exam_group')->insert([
            'exam_id' => $exam->id,
            'group_id' => $group->id,
            'assigned_by' => $teacher->id,
            'assigned_at' => now()
        ]);

        $this->actingAs($teacher);
        $this->delete(route('exams.groups.remove', [
            'exam' => $exam,
            'group' => $group
        ]));

        $this->actingAs($student1);
        $response = $this->get(route('student.exams.show', $exam));
        $response->assertStatus(403);
    }

    #[Test]
    public function inactive_students_in_group_cannot_access()
    {
        ['teacher' => $teacher, 'student1' => $student1, 'student2' => $student2, 'exam' => $exam, 'group' => $group] = $this->createGroupWithStudents();

        DB::table('exam_group')->insert([
            'exam_id' => $exam->id,
            'group_id' => $group->id,
            'assigned_by' => $teacher->id,
            'assigned_at' => now()
        ]);

        $student1->groups()->updateExistingPivot($group->id, [
            'is_active' => false
        ]);

        $student1->refresh();

        $this->actingAs($student1);
        $response = $this->get(route('student.exams.show', $exam));
        $response->assertStatus(403);

        $this->actingAs($student2);
        $response = $this->get(route('student.exams.show', $exam));
        $response->assertOk();
    }
}
