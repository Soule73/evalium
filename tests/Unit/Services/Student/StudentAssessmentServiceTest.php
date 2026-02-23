<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Student;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Subject;
use App\Models\User;
use App\Services\Core\Scoring\ScoringService;
use App\Services\Student\StudentAssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StudentAssessmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private StudentAssessmentService $service;

    private ScoringService $scoringService;

    private AcademicYear $academicYear;

    private User $teacher;

    private Subject $subject;

    private ClassModel $classModel;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'student']);
        Role::create(['name' => 'teacher']);
        Role::create(['name' => 'admin']);

        $this->academicYear = AcademicYear::factory()->create([
            'name' => '2024-2025',
            'start_date' => now(),
            'end_date' => now()->addYear(),
            'is_current' => true,
        ]);

        $level = Level::factory()->create(['name' => 'Test Level']);
        $this->subject = Subject::factory()->create(['name' => 'Test Subject']);
        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('teacher');

        $this->classModel = ClassModel::factory()->create([
            'name' => 'Test Class',
            'level_id' => $level->id,
            'academic_year_id' => $this->academicYear->id,
        ]);

        $this->scoringService = $this->createMock(ScoringService::class);
        $this->service = new StudentAssessmentService($this->scoringService);
    }

    public function test_get_student_assessments_for_index_returns_empty_array_when_no_enrollment(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $result = $this->service->getStudentAssessmentsForIndex($student, $this->academicYear->id, [], 15);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('assignments', $result);
        $this->assertArrayHasKey('subjects', $result);
        $this->assertEquals([], $result['assignments']);
        $this->assertEquals([], $result['subjects']);
    }

    public function test_get_student_assessments_for_index_returns_paginated_results(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $class = ClassModel::factory()->create(['academic_year_id' => $this->academicYear->id]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $classSubject = ClassSubject::factory()->create([
            'class_id' => $class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $assessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Test Assessment',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDay(),
        ]);

        $result = $this->service->getStudentAssessmentsForIndex($student, $this->academicYear->id, [], 15);

        $this->assertIsArray($result);
        $assignments = $result['assignments'];
        $this->assertNotEmpty($assignments);
        $this->assertCount(1, $assignments->items());
        $this->assertEquals('Test Assessment', $assignments->items()[0]->assessment->title);
        $this->assertNotEmpty($result['subjects']);
    }

    public function test_get_student_assessments_for_index_filters_by_search(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $class = ClassModel::factory()->create(['academic_year_id' => $this->academicYear->id]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $classSubject = ClassSubject::factory()->create([
            'class_id' => $class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Math Test',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDay(),
        ]);

        Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Science Test',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDays(2),
        ]);

        $result = $this->service->getStudentAssessmentsForIndex(
            $student,
            $this->academicYear->id,
            ['search' => 'Math'],
            15
        );

        $assignments = $result['assignments'];
        $this->assertCount(1, $assignments->items());
        $this->assertEquals('Math Test', $assignments->items()[0]->assessment->title);
    }

    public function test_get_student_assessments_for_index_filters_by_status(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $class = ClassModel::factory()->create(['academic_year_id' => $this->academicYear->id]);

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $classSubject = ClassSubject::factory()->create([
            'class_id' => $class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $assessment1 = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Not Started',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDay(),
        ]);

        $assessment2 = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Completed',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDays(2),
        ]);

        AssessmentAssignment::create([
            'assessment_id' => $assessment2->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now(),
        ]);

        $result = $this->service->getStudentAssessmentsForIndex(
            $student,
            $this->academicYear->id,
            ['status' => 'submitted'],
            15
        );

        $assignments = $result['assignments'];
        $this->assertCount(1, $assignments->items());
        $this->assertEquals('Completed', $assignments->items()[0]->assessment->title);
        $this->assertEquals('submitted', $assignments->items()[0]->status);
    }

    public function test_get_assignment_for_results_returns_assignment_with_answers(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $classSubject = ClassSubject::factory()->create([
            'class_id' => $this->classModel->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $assessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Test',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDay(),
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->classModel->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
        $assignment = AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now(),
        ]);

        $result = $this->service->getAssignmentForResults($student, $assessment);

        $this->assertNotNull($result);
        $this->assertEquals($assignment->id, $result->id);
        $this->assertTrue($result->relationLoaded('answers'));
    }

    public function test_get_assignment_for_results_returns_null_when_not_found(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $classSubject = ClassSubject::factory()->create([
            'class_id' => $this->classModel->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $assessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Test',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDay(),
        ]);

        $result = $this->service->getAssignmentForResults($student, $assessment);

        $this->assertNull($result);
    }

    public function test_get_or_create_assignment_creates_new_assignment(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $classSubject = ClassSubject::factory()->create([
            'class_id' => $this->classModel->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $assessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Test',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDay(),
        ]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->classModel->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $assignment = $this->service->getOrCreateAssignment($student, $assessment);

        $this->assertInstanceOf(AssessmentAssignment::class, $assignment);
        $this->assertEquals($assessment->id, $assignment->assessment_id);
        $this->assertEquals($student->id, $assignment->student_id);
    }

    public function test_get_or_create_assignment_returns_existing_assignment(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $classSubject = ClassSubject::factory()->create([
            'class_id' => $this->classModel->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $assessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Test',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDay(),
        ]);

        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->classModel->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
        $existing = AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
        ]);

        $assignment = $this->service->getOrCreateAssignment($student, $assessment);

        $this->assertEquals($existing->id, $assignment->id);
    }

    public function test_can_student_access_assessment_returns_true_when_enrolled(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $class = ClassModel::factory()->create(['academic_year_id' => $this->academicYear->id]);

        Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $class->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        $classSubject = ClassSubject::factory()->create([
            'class_id' => $class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $assessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Test',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDay(),
        ]);

        $result = $this->service->canStudentAccessAssessment($student, $assessment);

        $this->assertTrue($result);
    }

    public function test_can_student_access_assessment_returns_false_when_not_enrolled(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');

        $classSubject = ClassSubject::factory()->create([
            'class_id' => $this->classModel->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $assessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Test',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDay(),
        ]);

        $result = $this->service->canStudentAccessAssessment($student, $assessment);

        $this->assertFalse($result);
    }

    public function test_get_assessment_status_returns_not_submitted_when_no_assignment(): void
    {
        $status = $this->service->getAssessmentStatus(null);

        $this->assertEquals('not_submitted', $status);
    }

    public function test_get_assessment_status_returns_submitted_when_submitted(): void
    {
        $assignment = new AssessmentAssignment([
            'submitted_at' => now(),
        ]);

        $status = $this->service->getAssessmentStatus($assignment);

        $this->assertEquals('submitted', $status);
    }

    public function test_get_assessment_status_returns_graded_when_graded(): void
    {
        $assignment = new AssessmentAssignment([
            'submitted_at' => now(),
            'graded_at' => now(),
        ]);

        $status = $this->service->getAssessmentStatus($assignment);

        $this->assertEquals('graded', $status);
    }

    public function test_save_answers_stores_multiple_choice_as_separate_rows(): void
    {
        $classSubject = ClassSubject::factory()->create([
            'class_id' => $this->classModel->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);
        $assessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Test',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDay(),
        ]);
        $question = $assessment->questions()->create([
            'content' => 'Pick all correct',
            'type' => 'multiple',
            'points' => 5,
            'order_index' => 1,
        ]);
        $choices = [];
        foreach (['A', 'B', 'C'] as $idx => $label) {
            $choices[] = $question->choices()->create([
                'content' => $label,
                'is_correct' => $idx < 2,
                'order_index' => $idx,
            ]);
        }
        $student = User::factory()->create();
        $student->assignRole('student');
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->classModel->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
        $assignment = AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
        ]);

        $result = $this->service->saveAnswers($assignment, [
            $question->id => [$choices[0]->id, $choices[1]->id],
        ]);

        $this->assertTrue($result);
        $this->assertCount(2, $assignment->answers);
        $savedChoiceIds = $assignment->answers->pluck('choice_id')->sort()->values()->all();
        $this->assertEquals([$choices[0]->id, $choices[1]->id], $savedChoiceIds);
    }

    public function test_save_answers_stores_single_choice(): void
    {
        $classSubject = ClassSubject::factory()->create([
            'class_id' => $this->classModel->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);
        $assessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Test',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDay(),
        ]);
        $question = $assessment->questions()->create([
            'content' => 'Pick one',
            'type' => 'one_choice',
            'points' => 3,
            'order_index' => 1,
        ]);
        $choice = $question->choices()->create([
            'content' => 'Answer A',
            'is_correct' => true,
            'order_index' => 0,
        ]);
        $student = User::factory()->create();
        $student->assignRole('student');
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->classModel->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
        $assignment = AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
        ]);

        $result = $this->service->saveAnswers($assignment, [
            $question->id => $choice->id,
        ]);

        $this->assertTrue($result);
        $this->assertCount(1, $assignment->answers()->get());
        $this->assertEquals($choice->id, $assignment->answers()->first()->choice_id);
    }

    public function test_save_answers_stores_text_answer(): void
    {
        $classSubject = ClassSubject::factory()->create([
            'class_id' => $this->classModel->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);
        $assessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Test',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDay(),
        ]);
        $question = $assessment->questions()->create([
            'content' => 'Explain',
            'type' => 'text',
            'points' => 10,
            'order_index' => 1,
        ]);
        $student = User::factory()->create();
        $student->assignRole('student');
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->classModel->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
        $assignment = AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
        ]);

        $result = $this->service->saveAnswers($assignment, [
            $question->id => 'My detailed answer',
        ]);

        $this->assertTrue($result);
        $this->assertEquals('My detailed answer', $assignment->answers()->first()->answer_text);
    }

    public function test_save_answers_replaces_previous_answers(): void
    {
        $classSubject = ClassSubject::factory()->create([
            'class_id' => $this->classModel->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);
        $assessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Test',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDay(),
        ]);
        $question = $assessment->questions()->create([
            'content' => 'Pick all',
            'type' => 'multiple',
            'points' => 5,
            'order_index' => 1,
        ]);
        $choices = [];
        foreach (['A', 'B', 'C'] as $idx => $label) {
            $choices[] = $question->choices()->create([
                'content' => $label,
                'is_correct' => true,
                'order_index' => $idx,
            ]);
        }
        $student = User::factory()->create();
        $student->assignRole('student');
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->classModel->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
        $assignment = AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
        ]);

        $this->service->saveAnswers($assignment, [
            $question->id => [$choices[0]->id, $choices[1]->id],
        ]);
        $this->assertCount(2, $assignment->answers()->get());

        $this->service->saveAnswers($assignment, [
            $question->id => [$choices[2]->id],
        ]);

        $answers = $assignment->answers()->get();
        $this->assertCount(1, $answers);
        $this->assertEquals($choices[2]->id, $answers->first()->choice_id);
    }

    public function test_save_answers_returns_false_when_submitted(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student');
        $classSubject = ClassSubject::factory()->create([
            'class_id' => $this->classModel->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);
        $assessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Test',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDay(),
        ]);
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->classModel->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
        $assignment = AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now(),
        ]);

        $result = $this->service->saveAnswers($assignment, ['1' => 'test']);

        $this->assertFalse($result);
    }

    public function test_format_user_answers_groups_multiple_choice_with_choices_property(): void
    {
        $classSubject = ClassSubject::factory()->create([
            'class_id' => $this->classModel->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);
        $assessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Test',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDay(),
        ]);
        $question = $assessment->questions()->create([
            'content' => 'Pick all',
            'type' => 'multiple',
            'points' => 5,
            'order_index' => 1,
        ]);
        $choiceA = $question->choices()->create(['content' => 'A', 'is_correct' => true, 'order_index' => 0]);
        $choiceB = $question->choices()->create(['content' => 'B', 'is_correct' => true, 'order_index' => 1]);

        $student = User::factory()->create();
        $student->assignRole('student');
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->classModel->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
        $assignment = AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
        ]);
        $assignment->answers()->create(['question_id' => $question->id, 'choice_id' => $choiceA->id]);
        $assignment->answers()->create(['question_id' => $question->id, 'choice_id' => $choiceB->id]);

        $answers = $assignment->answers()->with('choice')->get();
        $result = $this->service->formatUserAnswers($answers);

        $this->assertArrayHasKey($question->id, $result);
        $formatted = $result[$question->id];
        $this->assertIsArray($formatted['choices']);
        $this->assertCount(2, $formatted['choices']);
    }

    public function test_format_user_answers_single_answer_returns_directly(): void
    {
        $classSubject = ClassSubject::factory()->create([
            'class_id' => $this->classModel->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
        ]);
        $assessment = Assessment::create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Test',
            'type' => 'exam',
            'duration_minutes' => 60,
            'coefficient' => 1.0,
            'scheduled_at' => now()->addDay(),
        ]);
        $question = $assessment->questions()->create([
            'content' => 'Pick one',
            'type' => 'one_choice',
            'points' => 3,
            'order_index' => 1,
        ]);
        $choice = $question->choices()->create(['content' => 'A', 'is_correct' => true, 'order_index' => 0]);

        $student = User::factory()->create();
        $student->assignRole('student');
        $enrollment = Enrollment::create([
            'student_id' => $student->id,
            'class_id' => $this->classModel->id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
        $assignment = AssessmentAssignment::create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
        ]);
        $assignment->answers()->create(['question_id' => $question->id, 'choice_id' => $choice->id]);

        $answers = $assignment->answers()->get();
        $result = $this->service->formatUserAnswers($answers);

        $this->assertArrayHasKey($question->id, $result);
        $this->assertEquals($choice->id, $result[$question->id]['choice_id']);
    }
}
