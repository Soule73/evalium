<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Answer;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Question;
use App\Models\Semester;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class FileUploadTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private ClassSubject $classSubject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        Storage::fake('local');

        $academicYear = AcademicYear::factory()->create();
        $classModel = ClassModel::factory()->create(['academic_year_id' => $academicYear->id]);
        $semester = Semester::factory()->create(['academic_year_id' => $academicYear->id]);

        $this->classSubject = ClassSubject::factory()->create([
            'class_id' => $classModel->id,
            'semester_id' => $semester->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * @return array{student: \App\Models\User, assessment: Assessment, question: Question, assignment: AssessmentAssignment}
     */
    private function createFileAssessment(array $assessmentOverrides = []): array
    {
        $student = $this->createStudent();
        $classModel = ClassModel::find($this->classSubject->class_id);
        $enrollment = $classModel->enrollments()->create([
            'student_id' => $student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $assessment = Assessment::factory()->homework()->withFileUploads()->create(array_merge([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
            'due_date' => now()->addDays(7),
            'scheduled_at' => now()->subDay(),
            'is_published' => true,
        ], $assessmentOverrides));

        $question = Question::factory()->fileType()->create([
            'assessment_id' => $assessment->id,
            'points' => 10,
        ]);

        $assignment = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
        ]);

        return [
            'student' => $student,
            'assessment' => $assessment,
            'question' => $question,
            'assignment' => $assignment,
        ];
    }

    public function test_upload_valid_file_succeeds(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'question' => $question] = $this->createFileAssessment();

        $file = UploadedFile::fake()->create('homework.pdf', 100, 'application/pdf');

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.file-answers.upload', $assessment), [
                'question_id' => $question->id,
                'file' => $file,
            ]);

        $response->assertCreated();
        $response->assertJsonStructure(['message', 'answer' => ['id', 'file_name', 'file_size', 'mime_type']]);

        $this->assertDatabaseHas('answers', [
            'question_id' => $question->id,
            'file_name' => 'homework.pdf',
        ]);
    }

    public function test_upload_over_max_file_size_rejected(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'question' => $question] = $this->createFileAssessment();

        config()->set('assessment.file_uploads.max_size_kb', 50);

        $file = UploadedFile::fake()->create('large.pdf', 200, 'application/pdf');

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.file-answers.upload', $assessment), [
                'question_id' => $question->id,
                'file' => $file,
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('file');
    }

    public function test_upload_wrong_extension_rejected(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'question' => $question] = $this->createFileAssessment();

        config()->set('assessment.file_uploads.allowed_extensions', ['pdf', 'docx']);

        $file = UploadedFile::fake()->create('image.exe', 50, 'application/x-msdownload');

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.file-answers.upload', $assessment), [
                'question_id' => $question->id,
                'file' => $file,
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('file');
    }

    public function test_reuploading_same_question_replaces_existing_file_answer(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'question' => $question, 'assignment' => $assignment] = $this->createFileAssessment();

        Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'file_name' => 'first.pdf',
            'file_path' => 'file-answers/1/1/first.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        $file = UploadedFile::fake()->create('second.pdf', 50, 'application/pdf');

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.file-answers.upload', $assessment), [
                'question_id' => $question->id,
                'file' => $file,
            ]);

        $response->assertCreated();
        $this->assertDatabaseCount('answers', 1);
        $this->assertDatabaseHas('answers', ['question_id' => $question->id, 'file_name' => 'second.pdf']);
    }

    public function test_delete_own_file_answer_succeeds(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'assignment' => $assignment, 'question' => $question] = $this->createFileAssessment();

        $answer = Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'file_name' => 'homework.pdf',
            'file_path' => 'file-answers/1/1/homework.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        $response = $this->actingAs($student)
            ->deleteJson(route('student.assessments.file-answers.delete', [$assessment, $answer]));

        $response->assertOk();
        $response->assertJsonFragment(['message' => __('messages.file_deleted')]);

        $this->assertDatabaseMissing('answers', ['id' => $answer->id]);
    }

    public function test_upload_on_supervised_assessment_rejected(): void
    {
        $student = $this->createStudent();
        $classModel = ClassModel::find($this->classSubject->class_id);
        $classModel->enrollments()->create([
            'student_id' => $student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $assessment = Assessment::factory()->supervised()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
            'scheduled_at' => now()->subDay(),
            'duration_minutes' => 60,
            'is_published' => true,
        ]);

        $question = Question::factory()->fileType()->create(['assessment_id' => $assessment->id]);

        $file = UploadedFile::fake()->create('homework.pdf', 100, 'application/pdf');

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.file-answers.upload', $assessment), [
                'question_id' => $question->id,
                'file' => $file,
            ]);

        $response->assertUnprocessable();
        $response->assertJsonFragment(['message' => __('messages.file_uploads_not_allowed')]);
    }

    public function test_upload_after_due_date_rejected(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'question' => $question] = $this->createFileAssessment([
            'due_date' => now()->subDay(),
        ]);

        $file = UploadedFile::fake()->create('late.pdf', 50, 'application/pdf');

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.file-answers.upload', $assessment), [
                'question_id' => $question->id,
                'file' => $file,
            ]);

        $response->assertConflict();
        $response->assertJsonFragment(['message' => __('messages.assessment_due_date_passed')]);
    }

    public function test_upload_after_submission_rejected(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'assignment' => $assignment, 'question' => $question] = $this->createFileAssessment();

        $assignment->update(['submitted_at' => now()]);

        $file = UploadedFile::fake()->create('late.pdf', 50, 'application/pdf');

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.file-answers.upload', $assessment), [
                'question_id' => $question->id,
                'file' => $file,
            ]);

        $response->assertBadRequest();
        $response->assertJsonFragment(['message' => __('messages.assessment_already_submitted')]);
    }

    public function test_delete_after_submission_rejected(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'assignment' => $assignment, 'question' => $question] = $this->createFileAssessment();

        $answer = Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'file_name' => 'homework.pdf',
            'file_path' => 'file-answers/1/1/homework.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        $assignment->update(['submitted_at' => now()]);

        $response = $this->actingAs($student)
            ->deleteJson(route('student.assessments.file-answers.delete', [$assessment, $answer]));

        $response->assertBadRequest();
        $response->assertJsonFragment(['message' => __('messages.assessment_already_submitted')]);
    }

    public function test_take_homework_passes_file_answers(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'assignment' => $assignment, 'question' => $question] = $this->createFileAssessment();

        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        Answer::factory()->count(2)->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'file_name' => 'file.pdf',
            'file_path' => 'file-answers/1/1/file.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.assessments.take', $assessment));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Student/Assessments/Work')
                ->has('fileAnswers', 2)
        );
    }
}
