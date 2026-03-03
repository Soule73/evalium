<?php

namespace Tests\Feature\Services\Core;

use App\Enums\GradeReportStatus;
use App\Models\AcademicYear;
use App\Models\Answer;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\GradeReport;
use App\Models\Level;
use App\Models\Question;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use App\Services\Core\GradeReport\GradeReportService;
use App\Services\Core\GradeReport\RemarkGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class GradeReportServiceTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private GradeReportService $service;

    private RemarkGeneratorService $remarkService;

    private AcademicYear $academicYear;

    private Semester $semester;

    private ClassModel $class;

    private Level $level;

    private User $teacher;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();

        $this->service = app(GradeReportService::class);
        $this->remarkService = app(RemarkGeneratorService::class);

        $this->admin = $this->createAdmin();
        $this->teacher = $this->createTeacher();

        $this->academicYear = AcademicYear::factory()->create([
            'is_current' => true,
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
        ]);

        $this->semester = Semester::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Semestre 1',
            'order_number' => 1,
            'start_date' => '2025-09-01',
            'end_date' => '2026-01-31',
        ]);

        $this->level = Level::factory()->create();

        $this->class = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);
    }

    /**
     * @return array{enrollment: Enrollment, classSubject: ClassSubject}
     */
    private function createStudentWithGrade(float $score, float $maxPoints = 20.0): array
    {
        $student = $this->createStudent();
        $subject = Subject::factory()->create(['level_id' => $this->level->id]);

        $classSubject = ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'semester_id' => $this->semester->id,
            'coefficient' => 2.0,
        ]);

        $enrollment = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $assessment = Assessment::factory()->create([
            'class_subject_id' => $classSubject->id,
            'is_published' => true,
            'coefficient' => 1.0,
        ]);

        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'points' => $maxPoints,
        ]);

        $assignment = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now(),
            'graded_at' => now(),
        ]);

        Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'score' => $score,
        ]);

        return ['enrollment' => $enrollment, 'classSubject' => $classSubject];
    }

    #[Test]
    public function it_generates_draft_reports_for_class(): void
    {
        $this->createStudentWithGrade(15.0);
        $this->createStudentWithGrade(12.0);

        $reports = $this->service->generateDrafts($this->class, $this->semester);

        $this->assertCount(2, $reports);
        $this->assertTrue($reports->every(fn ($r) => $r->status === GradeReportStatus::Draft));
        $this->assertTrue($reports->every(fn ($r) => $r->academic_year_id === $this->academicYear->id));
    }

    #[Test]
    public function generated_report_contains_expected_data_structure(): void
    {
        $this->createStudentWithGrade(16.0);

        $reports = $this->service->generateDrafts($this->class, $this->semester);
        $report = $reports->first();

        $this->assertArrayHasKey('header', $report->data);
        $this->assertArrayHasKey('subjects', $report->data);
        $this->assertArrayHasKey('footer', $report->data);

        $this->assertEquals($this->academicYear->name, $report->data['header']['academic_year']);
        $this->assertEquals($this->class->name, $report->data['header']['class_name']);
        $this->assertNotEmpty($report->data['header']['student_name']);
    }

    #[Test]
    public function generated_report_has_auto_remarks(): void
    {
        $this->createStudentWithGrade(16.0);

        $reports = $this->service->generateDrafts($this->class, $this->semester);
        $report = $reports->first();

        $this->assertNotNull($report->remarks);
        $this->assertArrayHasKey('subjects', $report->remarks);
        $this->assertNotEmpty($report->remarks['subjects']);
        $this->assertNotNull($report->general_remark);
    }

    #[Test]
    public function it_updates_subject_remarks(): void
    {
        $scenario = $this->createStudentWithGrade(14.0);

        $reports = $this->service->generateDrafts($this->class, $this->semester);
        $report = $reports->first();

        $subjectRemarks = [
            [
                'class_subject_id' => $scenario['classSubject']->id,
                'remark' => 'Custom teacher remark',
            ],
        ];

        $updated = $this->service->updateRemarks($report, $subjectRemarks);

        $found = false;
        foreach ($updated->remarks['subjects'] as $r) {
            if ($r['class_subject_id'] === $scenario['classSubject']->id) {
                $this->assertEquals('Custom teacher remark', $r['remark']);
                $this->assertFalse($r['auto_generated']);
                $found = true;
            }
        }
        $this->assertTrue($found);
    }

    #[Test]
    public function it_updates_general_remark(): void
    {
        $this->createStudentWithGrade(14.0);

        $reports = $this->service->generateDrafts($this->class, $this->semester);
        $report = $reports->first();

        $updated = $this->service->updateGeneralRemark($report, 'Custom general remark');

        $this->assertEquals('Custom general remark', $updated->general_remark);
    }

    #[Test]
    public function it_validates_a_report(): void
    {
        $this->createStudentWithGrade(14.0);

        $reports = $this->service->generateDrafts($this->class, $this->semester);
        $report = $reports->first();

        $validated = $this->service->validate($report, $this->admin);

        $this->assertEquals(GradeReportStatus::Validated, $validated->status);
        $this->assertEquals($this->admin->id, $validated->validated_by);
        $this->assertNotNull($validated->validated_at);
        $this->assertNotNull($validated->file_path);
    }

    #[Test]
    public function it_validates_batch_of_reports(): void
    {
        $this->createStudentWithGrade(15.0);
        $this->createStudentWithGrade(12.0);

        $this->service->generateDrafts($this->class, $this->semester);

        $count = $this->service->validateBatch($this->class, $this->semester, $this->admin);

        $this->assertEquals(2, $count);

        $allValidated = GradeReport::where('status', GradeReportStatus::Validated)->count();
        $this->assertEquals(2, $allValidated);
    }

    #[Test]
    public function it_publishes_a_validated_report(): void
    {
        $this->createStudentWithGrade(14.0);

        $reports = $this->service->generateDrafts($this->class, $this->semester);
        $report = $reports->first();
        $this->service->validate($report, $this->admin);

        $published = $this->service->publish($report->fresh());

        $this->assertEquals(GradeReportStatus::Published, $published->status);
    }

    #[Test]
    public function it_generates_pdf_file(): void
    {
        $this->createStudentWithGrade(14.0);

        $reports = $this->service->generateDrafts($this->class, $this->semester);
        $report = $reports->first();

        $path = $this->service->generatePdf($report);

        $this->assertNotEmpty($path);
        $this->assertStringEndsWith('.pdf', $path);
    }

    #[Test]
    public function regenerating_drafts_updates_existing_reports(): void
    {
        $this->createStudentWithGrade(14.0);

        $reports = $this->service->generateDrafts($this->class, $this->semester);
        $firstReport = $reports->first();
        $originalId = $firstReport->id;

        $regenerated = $this->service->generateDrafts($this->class, $this->semester);
        $this->assertEquals($originalId, $regenerated->first()->id);
        $this->assertCount(1, GradeReport::all());
    }

    #[Test]
    public function it_generates_annual_reports_without_semester(): void
    {
        $this->createStudentWithGrade(14.0);

        $reports = $this->service->generateDrafts($this->class);

        $this->assertCount(1, $reports);
        $report = $reports->first();
        $this->assertNull($report->semester_id);
    }
}
