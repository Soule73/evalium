<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\ExamAssignment;
use Spatie\Permission\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use App\Services\Teacher\ExamAssignmentService;
use App\Services\Teacher\ExamGroupService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExamAssignmentService $service;
    private User $teacher;
    private User $student;
    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les rôles
        Role::create(['name' => 'teacher']);
        Role::create(['name' => 'student']);

        $examGroupService = new ExamGroupService();
        $this->service = new ExamAssignmentService($examGroupService);

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
    public function it_can_get_assignment_form_data()
    {
        $data = $this->service->getAssignmentFormData($this->exam);

        $this->assertArrayHasKey('exam', $data);
        $this->assertArrayHasKey('students', $data);
        $this->assertArrayHasKey('alreadyAssigned', $data);
        $this->assertEquals($this->exam->id, $data['exam']->id);
    }

    #[Test]
    public function it_can_assign_exam_to_students()
    {
        $studentIds = [$this->student->id];

        $result = $this->service->assignExamToStudents($this->exam, $studentIds);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['assigned_count']);

        // Vérifier que l'assignation a été créée
        $this->assertDatabaseHas('exam_assignments', [
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'assigned'
        ]);
    }

    #[Test]
    public function it_prevents_duplicate_assignments()
    {
        // Créer une assignation existante
        ExamAssignment::create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'assigned'
        ]);

        $studentIds = [$this->student->id];
        $result = $this->service->assignExamToStudents($this->exam, $studentIds);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['assigned_count']);
        $this->assertEquals(1, $result['already_assigned_count']);
    }

    #[Test]
    public function it_can_get_paginated_assignments()
    {
        // Créer des assignations
        ExamAssignment::factory()->count(15)->create([
            'exam_id' => $this->exam->id,
        ]);

        $result = $this->service->getExamAssignments($this->exam, 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(10, $result->perPage());
        $this->assertGreaterThan(0, $result->total());
    }

    #[Test]
    public function it_can_filter_assignments_by_status()
    {
        // Créer des assignations avec différents statuts
        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'status' => 'assigned'
        ]);

        ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'status' => 'submitted'
        ]);

        $result = $this->service->getExamAssignments($this->exam, 10, null, 'assigned');

        $assignments = $result->items();
        $this->assertCount(1, $assignments);
        $this->assertEquals('assigned', $assignments[0]->status);
    }

    #[Test]
    public function it_can_calculate_assignment_statistics()
    {
        // Créer des assignations avec différents statuts
        ExamAssignment::factory()->count(3)->create([
            'exam_id' => $this->exam->id,
            'status' => 'assigned'
        ]);

        ExamAssignment::factory()->count(2)->create([
            'exam_id' => $this->exam->id,
            'status' => 'submitted',
            'score' => 85.5
        ]);

        $stats = $this->service->getExamAssignmentStats($this->exam);

        $this->assertEquals(5, $stats['total_assigned']);
        $this->assertEquals(2, $stats['total_submitted']);
        $this->assertArrayHasKey('completion_rate', $stats);
    }

    #[Test]
    public function it_handles_empty_statistics()
    {
        $stats = $this->service->getExamAssignmentStats($this->exam);

        $this->assertEquals(0, $stats['total_assigned']);
        $this->assertEquals(0, $stats['total_submitted']);
        $this->assertArrayHasKey('completion_rate', $stats);
    }
}
