<?php

namespace Tests\Feature\Commands;

use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class MaterialiseAssessmentAssignmentsTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    /**
     * Build a complete assessment linked to a class with enrolled students.
     *
     * @return array{assessment: Assessment, class: ClassModel, enrollments: \Illuminate\Database\Eloquent\Collection}
     */
    private function setupEndedHomeworkAssessment(int $studentCount = 2): array
    {
        $classSubject = ClassSubject::factory()->create();
        $class = ClassModel::find($classSubject->class_id);

        $assessment = Assessment::factory()->homework()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'due_date' => now()->subDay(),
            'is_published' => true,
        ]);

        $enrollments = collect();
        for ($i = 0; $i < $studentCount; $i++) {
            $student = $this->createStudent();
            $enrollments->push(
                Enrollment::factory()->create([
                    'class_id' => $class->id,
                    'student_id' => $student->id,
                ])
            );
        }

        return ['assessment' => $assessment, 'class' => $class, 'enrollments' => $enrollments];
    }

    public function test_creates_assignments_for_all_enrolled_students(): void
    {
        ['assessment' => $assessment, 'enrollments' => $enrollments] = $this->setupEndedHomeworkAssessment(3);

        $this->assertDatabaseCount('assessment_assignments', 0);

        $this->artisan('assessment:materialise-assignments')
            ->assertSuccessful();

        $this->assertDatabaseCount('assessment_assignments', 3);

        foreach ($enrollments as $enrollment) {
            $this->assertDatabaseHas('assessment_assignments', [
                'assessment_id' => $assessment->id,
                'enrollment_id' => $enrollment->id,
                'started_at' => null,
                'submitted_at' => null,
            ]);
        }
    }

    public function test_does_not_duplicate_existing_assignments(): void
    {
        ['assessment' => $assessment, 'enrollments' => $enrollments] = $this->setupEndedHomeworkAssessment(2);

        AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollments->first()->id,
        ]);

        $this->artisan('assessment:materialise-assignments')
            ->assertSuccessful();

        $this->assertDatabaseCount('assessment_assignments', 2);
    }

    public function test_skips_unpublished_assessments(): void
    {
        $classSubject = ClassSubject::factory()->create();
        $class = ClassModel::find($classSubject->class_id);

        Assessment::factory()->homework()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'due_date' => now()->subDay(),
            'is_published' => false,
        ]);

        $student = $this->createStudent();
        Enrollment::factory()->create(['class_id' => $class->id, 'student_id' => $student->id]);

        $this->artisan('assessment:materialise-assignments')
            ->assertSuccessful();

        $this->assertDatabaseCount('assessment_assignments', 0);
    }

    public function test_skips_assessments_that_have_not_ended(): void
    {
        $classSubject = ClassSubject::factory()->create();
        $class = ClassModel::find($classSubject->class_id);

        Assessment::factory()->homework()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'due_date' => now()->addDay(),
            'is_published' => true,
        ]);

        $student = $this->createStudent();
        Enrollment::factory()->create(['class_id' => $class->id, 'student_id' => $student->id]);

        $this->artisan('assessment:materialise-assignments')
            ->assertSuccessful();

        $this->assertDatabaseCount('assessment_assignments', 0);
    }

    public function test_skips_withdrawn_enrollments(): void
    {
        $classSubject = ClassSubject::factory()->create();
        $class = ClassModel::find($classSubject->class_id);

        $assessment = Assessment::factory()->homework()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'due_date' => now()->subDay(),
            'is_published' => true,
        ]);

        $student = $this->createStudent();
        Enrollment::factory()->withdrawn()->create(['class_id' => $class->id, 'student_id' => $student->id]);

        $this->artisan('assessment:materialise-assignments')
            ->assertSuccessful();

        $this->assertDatabaseCount('assessment_assignments', 0);
    }

    public function test_dry_run_does_not_persist_assignments(): void
    {
        $this->setupEndedHomeworkAssessment(2);

        $this->artisan('assessment:materialise-assignments', ['--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseCount('assessment_assignments', 0);
    }

    public function test_dry_run_still_reports_would_be_created_count(): void
    {
        $this->setupEndedHomeworkAssessment(2);

        $this->artisan('assessment:materialise-assignments', ['--dry-run' => true])
            ->expectsOutputToContain('Created: 2')
            ->assertSuccessful();
    }

    public function test_handles_ended_supervised_assessment(): void
    {
        $classSubject = ClassSubject::factory()->create();
        $class = ClassModel::find($classSubject->class_id);

        $assessment = Assessment::factory()->supervised()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'scheduled_at' => now()->subHours(3),
            'duration_minutes' => 60,
            'is_published' => true,
        ]);

        $student = $this->createStudent();
        Enrollment::factory()->create(['class_id' => $class->id, 'student_id' => $student->id]);

        $this->artisan('assessment:materialise-assignments')
            ->assertSuccessful();

        $this->assertDatabaseHas('assessment_assignments', [
            'assessment_id' => $assessment->id,
        ]);
    }

    public function test_preserves_assignments_of_students_who_did_start(): void
    {
        ['assessment' => $assessment, 'enrollments' => $enrollments] = $this->setupEndedHomeworkAssessment(1);

        AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollments->first()->id,
            'started_at' => now()->subHours(2),
            'submitted_at' => now()->subHour(),
        ]);

        $this->artisan('assessment:materialise-assignments')
            ->assertSuccessful();

        $this->assertDatabaseCount('assessment_assignments', 1);

        $assignment = AssessmentAssignment::first();
        $this->assertNotNull($assignment->started_at);
        $this->assertNotNull($assignment->submitted_at);
    }
}
