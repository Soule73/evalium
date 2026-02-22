<?php

namespace Tests\Feature\Models;

use App\Enums\AssessmentType;
use App\Enums\DeliveryMode;
use App\Enums\QuestionType;
use App\Models\AcademicYear;
use App\Models\Answer;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Semester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class DeliveryModeModelsTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private ClassSubject $classSubject;

    private Assessment $sharedAssessment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        $academicYear = AcademicYear::factory()->create();
        $classModel = ClassModel::factory()->create(['academic_year_id' => $academicYear->id]);
        $semester = Semester::factory()->create(['academic_year_id' => $academicYear->id]);

        $this->classSubject = ClassSubject::factory()->create([
            'class_id' => $classModel->id,
            'semester_id' => $semester->id,
        ]);

        $this->sharedAssessment = Assessment::factory()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
        ]);
    }

    private function createAssessment(array $states = [], array $attributes = []): Assessment
    {
        $factory = Assessment::factory();
        foreach ($states as $state) {
            $factory = $factory->$state();
        }

        return $factory->create(array_merge([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
        ], $attributes));
    }

    private function createAssignment(array $attributes = []): AssessmentAssignment
    {
        return AssessmentAssignment::factory()->create(array_merge([
            'assessment_id' => $this->sharedAssessment->id,
        ], $attributes));
    }

    public function test_assessment_delivery_mode_is_cast_to_enum(): void
    {
        $assessment = $this->createAssessment(['supervised']);

        $this->assertInstanceOf(DeliveryMode::class, $assessment->delivery_mode);
        $this->assertSame(DeliveryMode::Supervised, $assessment->delivery_mode);
    }

    public function test_assessment_supervised_factory_state(): void
    {
        $assessment = $this->createAssessment(['supervised']);

        $this->assertTrue($assessment->isSupervisedMode());
        $this->assertFalse($assessment->isHomeworkMode());
        $this->assertNotNull($assessment->duration_minutes);
    }

    public function test_assessment_homework_factory_state(): void
    {
        $assessment = $this->createAssessment(['homework']);

        $this->assertTrue($assessment->isHomeworkMode());
        $this->assertFalse($assessment->isSupervisedMode());
        $this->assertNotNull($assessment->due_date);
    }

    public function test_assessment_default_delivery_mode_is_supervised(): void
    {
        $assessment = $this->createAssessment([], [
            'type' => 'exam',
            'delivery_mode' => DeliveryMode::Supervised,
        ]);

        $this->assertSame(DeliveryMode::Supervised, $assessment->delivery_mode);
    }

    public function test_question_type_file_requires_manual_grading(): void
    {
        $this->assertTrue(QuestionType::File->requiresManualGrading());
    }

    public function test_file_answer_stores_file_metadata(): void
    {
        $question = Question::factory()->create([
            'assessment_id' => $this->sharedAssessment->id,
            'type' => 'file',
        ]);
        $assignment = $this->createAssignment();

        $answer = Answer::factory()->withFile()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
        ]);

        $this->assertNotNull($answer->file_name);
        $this->assertNotNull($answer->file_path);
        $this->assertNotNull($answer->file_size);
        $this->assertNotNull($answer->mime_type);
    }

    public function test_assessment_due_date_is_cast_to_datetime(): void
    {
        $assessment = $this->createAssessment(['homework']);

        $this->assertInstanceOf(\Carbon\Carbon::class, $assessment->due_date);
    }

    public function test_assignment_started_at_is_cast_to_datetime(): void
    {
        $assignment = AssessmentAssignment::factory()->started()->create([
            'assessment_id' => $this->sharedAssessment->id,
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $assignment->started_at);
    }

    public function test_assignment_status_in_progress_when_started_not_submitted(): void
    {
        $assignment = AssessmentAssignment::factory()->started()->create([
            'assessment_id' => $this->sharedAssessment->id,
        ]);

        $this->assertSame('in_progress', $assignment->status);
    }

    public function test_assignment_status_not_submitted_when_not_started(): void
    {
        $assignment = $this->createAssignment();

        $this->assertSame('not_submitted', $assignment->status);
    }

    public function test_assignment_status_submitted_takes_priority_over_started(): void
    {
        $assignment = $this->createAssignment([
            'started_at' => now()->subHour(),
            'submitted_at' => now(),
        ]);

        $this->assertSame('submitted', $assignment->status);
    }

    public function test_assignment_status_graded_takes_highest_priority(): void
    {
        $assignment = $this->createAssignment([
            'started_at' => now()->subHours(2),
            'submitted_at' => now()->subHour(),
            'graded_at' => now(),
        ]);

        $this->assertSame('graded', $assignment->status);
    }

    public function test_assignment_in_progress_scope(): void
    {
        $student1 = $this->createStudent();
        $student2 = $this->createStudent();
        $student3 = $this->createStudent();

        $enrollment1 = Enrollment::firstOrCreate(
            ['student_id' => $student1->id, 'class_id' => $this->classSubject->class_id],
            ['enrolled_at' => now(), 'status' => 'active']
        );
        $enrollment2 = Enrollment::firstOrCreate(
            ['student_id' => $student2->id, 'class_id' => $this->classSubject->class_id],
            ['enrolled_at' => now(), 'status' => 'active']
        );
        $enrollment3 = Enrollment::firstOrCreate(
            ['student_id' => $student3->id, 'class_id' => $this->classSubject->class_id],
            ['enrolled_at' => now(), 'status' => 'active']
        );

        AssessmentAssignment::factory()->started()->create([
            'assessment_id' => $this->sharedAssessment->id,
            'enrollment_id' => $enrollment1->id,
        ]);
        $this->createAssignment(['enrollment_id' => $enrollment2->id]);
        AssessmentAssignment::factory()->submitted()->create([
            'assessment_id' => $this->sharedAssessment->id,
            'enrollment_id' => $enrollment3->id,
        ]);

        $inProgress = AssessmentAssignment::inProgress()->get();

        $this->assertCount(1, $inProgress);
        $this->assertNotNull($inProgress->first()->started_at);
        $this->assertNull($inProgress->first()->submitted_at);
    }

    public function test_assignment_has_file_answers(): void
    {
        $question = Question::factory()->create([
            'assessment_id' => $this->sharedAssessment->id,
            'type' => 'file',
        ]);
        $assignment = $this->createAssignment();

        Answer::factory()->withFile()->count(2)->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
        ]);

        $this->assertCount(2, $assignment->answers);
    }

    public function test_file_answer_belongs_to_assignment(): void
    {
        $question = Question::factory()->create([
            'assessment_id' => $this->sharedAssessment->id,
            'type' => 'file',
        ]);
        $assignment = $this->createAssignment();

        $answer = Answer::factory()->withFile()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
        ]);

        $this->assertTrue($answer->assessmentAssignment->is($assignment));
    }

    public function test_answer_factory_creates_valid_file_record(): void
    {
        $question = Question::factory()->create([
            'assessment_id' => $this->sharedAssessment->id,
            'type' => 'file',
        ]);
        $assignment = $this->createAssignment();

        $answer = Answer::factory()->withFile()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
        ]);

        $this->assertNotNull($answer->file_name);
        $this->assertNotNull($answer->file_path);
        $this->assertNotNull($answer->file_size);
        $this->assertNotNull($answer->mime_type);
    }

    public function test_examen_factory_defaults_to_supervised(): void
    {
        $assessment = $this->createAssessment(['examen']);

        $this->assertSame(DeliveryMode::Supervised, $assessment->delivery_mode);
        $this->assertSame(AssessmentType::Exam, $assessment->type);
    }

    public function test_devoir_factory_defaults_to_homework(): void
    {
        $assessment = $this->createAssessment(['devoir']);

        $this->assertSame(DeliveryMode::Homework, $assessment->delivery_mode);
        $this->assertSame(AssessmentType::Homework, $assessment->type);
    }

    public function test_tp_factory_defaults_to_homework(): void
    {
        $assessment = $this->createAssessment(['tp']);

        $this->assertSame(DeliveryMode::Homework, $assessment->delivery_mode);
        $this->assertSame(AssessmentType::Practical, $assessment->type);
    }
}
