<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\AssignmentAttachment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
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
     * @return array{student: \App\Models\User, assessment: Assessment, assignment: AssessmentAssignment}
     */
    private function createHomeworkWithFileUploads(array $assessmentOverrides = []): array
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
            'settings' => ['is_published' => true],
        ], $assessmentOverrides));

        $assignment = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
        ]);

        return ['student' => $student, 'assessment' => $assessment, 'assignment' => $assignment];
    }

    public function test_upload_valid_file_succeeds(): void
    {
        ['student' => $student, 'assessment' => $assessment] = $this->createHomeworkWithFileUploads();

        $file = UploadedFile::fake()->create('homework.pdf', 100, 'application/pdf');

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.attachments.upload', $assessment), [
                'file' => $file,
            ]);

        $response->assertCreated();
        $response->assertJsonStructure(['message', 'attachment' => ['id', 'file_name', 'file_size', 'mime_type']]);

        $this->assertDatabaseHas('assignment_attachments', [
            'file_name' => 'homework.pdf',
        ]);
    }

    public function test_upload_over_max_file_size_rejected(): void
    {
        ['student' => $student, 'assessment' => $assessment] = $this->createHomeworkWithFileUploads([
            'max_file_size' => 100,
        ]);

        $file = UploadedFile::fake()->create('large.pdf', 200, 'application/pdf');

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.attachments.upload', $assessment), [
                'file' => $file,
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('file');
    }

    public function test_upload_wrong_extension_rejected(): void
    {
        ['student' => $student, 'assessment' => $assessment] = $this->createHomeworkWithFileUploads([
            'allowed_extensions' => 'pdf,docx',
        ]);

        $file = UploadedFile::fake()->create('image.exe', 50, 'application/x-msdownload');

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.attachments.upload', $assessment), [
                'file' => $file,
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('file');
    }

    public function test_upload_over_max_files_count_rejected(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'assignment' => $assignment] = $this->createHomeworkWithFileUploads([
            'max_files' => 1,
        ]);

        AssignmentAttachment::factory()->create([
            'assessment_assignment_id' => $assignment->id,
        ]);

        $file = UploadedFile::fake()->create('second.pdf', 50, 'application/pdf');

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.attachments.upload', $assessment), [
                'file' => $file,
            ]);

        $response->assertUnprocessable();
        $response->assertJsonFragment(['message' => __('messages.file_upload_limit_reached')]);
    }

    public function test_delete_own_attachment_succeeds(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'assignment' => $assignment] = $this->createHomeworkWithFileUploads();

        $attachment = AssignmentAttachment::factory()->create([
            'assessment_assignment_id' => $assignment->id,
        ]);

        $response = $this->actingAs($student)
            ->deleteJson(route('student.assessments.attachments.delete', [$assessment, $attachment]));

        $response->assertOk();
        $response->assertJsonFragment(['message' => __('messages.file_deleted')]);

        $this->assertDatabaseMissing('assignment_attachments', ['id' => $attachment->id]);
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

        $assessment = Assessment::factory()->supervised()->withFileUploads()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
            'scheduled_at' => now()->subDay(),
            'settings' => ['is_published' => true],
        ]);

        $file = UploadedFile::fake()->create('homework.pdf', 100, 'application/pdf');

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.attachments.upload', $assessment), [
                'file' => $file,
            ]);

        $response->assertUnprocessable();
        $response->assertJsonFragment(['message' => __('messages.file_uploads_not_allowed')]);
    }

    public function test_upload_after_due_date_rejected(): void
    {
        ['student' => $student, 'assessment' => $assessment] = $this->createHomeworkWithFileUploads([
            'due_date' => now()->subDay(),
        ]);

        $file = UploadedFile::fake()->create('late.pdf', 50, 'application/pdf');

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.attachments.upload', $assessment), [
                'file' => $file,
            ]);

        $response->assertConflict();
        $response->assertJsonFragment(['message' => __('messages.assessment_due_date_passed')]);
    }

    public function test_upload_after_submission_rejected(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'assignment' => $assignment] = $this->createHomeworkWithFileUploads();

        $assignment->update(['submitted_at' => now()]);

        $file = UploadedFile::fake()->create('late.pdf', 50, 'application/pdf');

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.attachments.upload', $assessment), [
                'file' => $file,
            ]);

        $response->assertBadRequest();
        $response->assertJsonFragment(['message' => __('messages.assessment_already_submitted')]);
    }

    public function test_delete_after_submission_rejected(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'assignment' => $assignment] = $this->createHomeworkWithFileUploads();

        $attachment = AssignmentAttachment::factory()->create([
            'assessment_assignment_id' => $assignment->id,
        ]);

        $assignment->update(['submitted_at' => now()]);

        $response = $this->actingAs($student)
            ->deleteJson(route('student.assessments.attachments.delete', [$assessment, $attachment]));

        $response->assertBadRequest();
        $response->assertJsonFragment(['message' => __('messages.assessment_already_submitted')]);
    }

    public function test_upload_without_file_uploads_enabled_rejected(): void
    {
        $student = $this->createStudent();
        $classModel = ClassModel::find($this->classSubject->class_id);
        $classModel->enrollments()->create([
            'student_id' => $student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $assessment = Assessment::factory()->homework()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->classSubject->teacher_id,
            'due_date' => now()->addDays(7),
            'scheduled_at' => now()->subDay(),
            'max_files' => 0,
            'settings' => ['is_published' => true],
        ]);

        $file = UploadedFile::fake()->create('homework.pdf', 100, 'application/pdf');

        $response = $this->actingAs($student)
            ->postJson(route('student.assessments.attachments.upload', $assessment), [
                'file' => $file,
            ]);

        $response->assertUnprocessable();
        $response->assertJsonFragment(['message' => __('messages.file_uploads_not_allowed')]);
    }

    public function test_take_homework_passes_attachments(): void
    {
        ['student' => $student, 'assessment' => $assessment, 'assignment' => $assignment] = $this->createHomeworkWithFileUploads();

        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        AssignmentAttachment::factory()->count(2)->create([
            'assessment_assignment_id' => $assignment->id,
        ]);

        $response = $this->actingAs($student)
            ->get(route('student.assessments.take', $assessment));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Student/Assessments/Work')
                ->has('attachments', 2)
        );
    }
}
