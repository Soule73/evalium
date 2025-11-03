<?php

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Group;
use App\Models\Level;
use App\Services\ExamService;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExamServiceGroupAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private ExamService $examService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->examService = app(ExamService::class);

        // Create roles if they don't exist
        Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);
    }

    /** @test */
    public function student_can_access_exam_assigned_via_group()
    {
        // Arrange: Create teacher, student, group, and exam
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $student = User::factory()->create();
        $student->assignRole('student');

        $level = Level::factory()->create(['name' => 'Test Level']);

        $group = Group::factory()->create([
            'level_id' => $level->id,
            'is_active' => true,
        ]);

        // Assign student to group
        $student->groups()->attach($group->id, [
            'enrolled_at' => now(),
            'is_active' => true,
        ]);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'is_active' => true,
        ]);

        // Assign exam to group
        /** @var Exam $exam */
        $exam->groups()->attach($group->id, [
            'assigned_at' => now(),
            'assigned_by' => $teacher->id,
        ]);

        // Act: Check if student can access exam
        $canAccess = $this->examService->studentCanAccessExam($exam, $student->id);

        // Assert
        $this->assertTrue($canAccess, 'Student should be able to access exam assigned to their group');
    }

    /** @test */
    public function student_cannot_access_exam_not_assigned_to_them()
    {
        // Arrange
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $student = User::factory()->create();
        $student->assignRole('student');

        /** @var Exam $exam */
        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'is_active' => true,
        ]);

        // Act
        $canAccess = $this->examService->studentCanAccessExam($exam, $student->id);

        // Assert
        $this->assertFalse($canAccess, 'Student should not be able to access exam not assigned to them');
    }

    /** @test */
    public function student_cannot_access_exam_via_inactive_group()
    {
        // Arrange
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $student = User::factory()->create();
        $student->assignRole('student');

        $level = Level::factory()->create(['name' => 'Test Level']);

        $group = Group::factory()->create([
            'level_id' => $level->id,
            'is_active' => true,
        ]);

        // Assign student to group but mark as inactive
        $student->groups()->attach($group->id, [
            'enrolled_at' => now(),
            'is_active' => false,
        ]);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'is_active' => true,
        ]);

        /** @var Exam $exam */
        $exam->groups()->attach($group->id, [
            'assigned_at' => now(),
            'assigned_by' => $teacher->id,
        ]);

        // Act
        $canAccess = $this->examService->studentCanAccessExam($exam, $student->id);

        // Assert
        $this->assertFalse($canAccess, 'Student should not be able to access exam via inactive group membership');
    }

    /** @test */
    public function get_assigned_exam_returns_virtual_assignment_for_group_exam()
    {
        // Arrange
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $student = User::factory()->create();
        $student->assignRole('student');

        $level = Level::factory()->create(['name' => 'Test Level']);

        $group = Group::factory()->create([
            'level_id' => $level->id,
            'is_active' => true,
        ]);

        $student->groups()->attach($group->id, [
            'enrolled_at' => now(),
            'is_active' => true,
        ]);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'is_active' => true,
        ]);

        /** @var Exam $exam */
        $exam->groups()->attach($group->id, [
            'assigned_at' => now(),
            'assigned_by' => $teacher->id,
        ]);

        // Act
        $assignment = $this->examService->getAssignedExamForStudent($exam, $student->id);

        // Assert
        $this->assertNotNull($assignment, 'Should return a virtual assignment');
        $this->assertEquals('assigned', $assignment->status);
        $this->assertEquals($exam->id, $assignment->exam_id);
        $this->assertEquals($student->id, $assignment->student_id);
        $this->assertFalse($assignment->exists, 'Assignment should be marked as virtual (not persisted)');
    }

    /** @test */
    public function get_assigned_exam_prefers_real_assignment_over_virtual()
    {
        // Arrange
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');

        $student = User::factory()->create();
        $student->assignRole('student');

        $level = Level::factory()->create(['name' => 'Test Level']);

        $group = Group::factory()->create([
            'level_id' => $level->id,
            'is_active' => true,
        ]);

        $student->groups()->attach($group->id, [
            'enrolled_at' => now(),
            'is_active' => true,
        ]);

        $exam = Exam::factory()->create([
            'teacher_id' => $teacher->id,
            'is_active' => true,
        ]);

        // Assign via group
        /** @var Exam $exam */
        $exam->groups()->attach($group->id, [
            'assigned_at' => now(),
            'assigned_by' => $teacher->id,
        ]);

        // Create a real assignment (student has started the exam)
        $realAssignment = $exam->assignments()->create([
            'student_id' => $student->id,
            'status' => 'started',
            'assigned_at' => now(),
            'started_at' => now(),
        ]);

        // Act
        $assignment = $this->examService->getAssignedExamForStudent($exam, $student->id);

        // Assert
        $this->assertNotNull($assignment);
        $this->assertTrue($assignment->exists, 'Should return the real persisted assignment');
        $this->assertEquals('started', $assignment->status);
        $this->assertEquals($realAssignment->id, $assignment->id);
    }
}
