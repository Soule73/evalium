<?php

namespace Tests\Feature\Teacher;

use App\Models\User;
use App\Models\Group;
use App\Models\Exam;
use Tests\TestCase;
use App\Services\Teacher\ExamAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamGroupAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_assign_exam_to_group()
    {
        $teacher = User::factory()->create()->assignRole('teacher');
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);

        $group = Group::factory()->create();

        // Créer des étudiants et les assigner au groupe
        $students = User::factory()->count(3)->create();
        foreach ($students as $student) {
            $student->assignRole('student');
            $student->groups()->attach($group->id, [
                'enrolled_at' => now(),
                'is_active' => true,
            ]);
        }

        $service = new ExamAssignmentService();
        $result = $service->assignExamToGroup($exam, $group->id);

        $this->assertTrue($result['success']);
        $this->assertEquals(3, $result['assigned_count']);
        $this->assertEquals(0, $result['already_assigned_count']);
        $this->assertEquals(3, $result['total_students']);

        // Vérifier que tous les étudiants sont bien assignés
        $assignments = $exam->assignments()->count();
        $this->assertEquals(3, $assignments);
    }

    public function test_handles_duplicate_assignments()
    {
        $teacher = User::factory()->create()->assignRole('teacher');
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);

        $group = Group::factory()->create();

        // Créer un étudiant et l'assigner au groupe
        $student = User::factory()->create()->assignRole('student');
        $student->groups()->attach($group->id, [
            'enrolled_at' => now(),
            'is_active' => true,
        ]);

        $service = new ExamAssignmentService();

        // Première assignation
        $result1 = $service->assignExamToGroup($exam, $group->id);
        $this->assertEquals(1, $result1['assigned_count']);
        $this->assertEquals(0, $result1['already_assigned_count']);

        // Deuxième assignation (doit détecter la duplication)
        $result2 = $service->assignExamToGroup($exam, $group->id);
        $this->assertEquals(0, $result2['assigned_count']);
        $this->assertEquals(1, $result2['already_assigned_count']);
    }

    public function test_only_assigns_to_active_students()
    {
        $teacher = User::factory()->create()->assignRole('teacher');
        $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);

        $group = Group::factory()->create();

        // Créer des étudiants
        $activeStudent = User::factory()->create()->assignRole('student');
        $inactiveStudent = User::factory()->create()->assignRole('student');

        // Assigner un étudiant actif
        $activeStudent->groups()->attach($group->id, [
            'enrolled_at' => now(),
            'is_active' => true,
        ]);

        // Assigner un étudiant inactif
        $inactiveStudent->groups()->attach($group->id, [
            'enrolled_at' => now()->subDays(30),
            'left_at' => now()->subDays(5),
            'is_active' => false,
        ]);

        $service = new ExamAssignmentService();
        $result = $service->assignExamToGroup($exam, $group->id);

        // Seul l'étudiant actif doit être assigné
        $this->assertEquals(1, $result['assigned_count']);
        $this->assertEquals(1, $result['total_students']);

        $assignments = $exam->assignments()->count();
        $this->assertEquals(1, $assignments);

        // Vérifier que c'est le bon étudiant
        $assignedStudent = $exam->assignments()->first()->student;
        $this->assertEquals($activeStudent->id, $assignedStudent->id);
    }
}
