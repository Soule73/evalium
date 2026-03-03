<?php

namespace Tests\Feature\Admin;

use App\Enums\GradeReportStatus;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\GradeReport;
use App\Models\Level;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class GradeReportControllerTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    private AcademicYear $academicYear;

    private ClassModel $class;

    private Semester $semester;

    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->admin = $this->createAdmin();
        $this->teacher = $this->createTeacher();
        $this->student = $this->createStudent();

        $this->academicYear = AcademicYear::factory()->create(['is_current' => true]);
        $level = Level::factory()->create();
        $this->class = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $level->id,
        ]);
        $this->semester = Semester::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'order_number' => 1,
        ]);
        $this->enrollment = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'status' => 'active',
        ]);
    }

    private function createReport(GradeReportStatus $status = GradeReportStatus::Draft): GradeReport
    {
        return $this->createReportForEnrollment($this->enrollment, $status);
    }

    private function createReportForEnrollment(Enrollment $enrollment, GradeReportStatus $status = GradeReportStatus::Draft): GradeReport
    {
        return GradeReport::factory()->create([
            'enrollment_id' => $enrollment->id,
            'semester_id' => $this->semester->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => $status,
            'average' => 14.5,
            'rank' => 1,
            'data' => [
                'header' => [
                    'school_name' => 'Test School',
                    'logo_path' => null,
                    'academic_year' => $this->academicYear->name,
                    'period' => $this->semester->name,
                    'student_name' => $enrollment->student->name ?? 'Student',
                    'class_name' => $this->class->name,
                    'level_name' => 'Level 1',
                ],
                'subjects' => [],
                'footer' => [
                    'average' => 14.5,
                    'rank' => 1,
                    'class_size' => 1,
                    'total_coefficient' => 0,
                ],
            ],
            'remarks' => ['subjects' => []],
            'general_remark' => 'Good progress.',
        ]);
    }

    // ---------------------------------------------------------------
    // Index
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_grade_reports_index(): void
    {
        $this->get(route('admin.classes.grade-reports.index', $this->class->id))
            ->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_grade_reports_index(): void
    {
        $this->actingAs($this->student)
            ->get(route('admin.classes.grade-reports.index', $this->class->id))
            ->assertForbidden();
    }

    public function test_teacher_cannot_access_grade_reports_index(): void
    {
        $this->actingAs($this->teacher)
            ->get(route('admin.classes.grade-reports.index', $this->class->id))
            ->assertForbidden();
    }

    public function test_admin_can_access_grade_reports_index(): void
    {
        $this->createReport();

        $this->actingAs($this->admin)
            ->get(route('admin.classes.grade-reports.index', $this->class->id))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Admin/GradeReports/Index')
                    ->has('class')
                    ->has('reports', 1)
                    ->has('semesters')
                    ->has('permissions')
            );
    }

    public function test_index_filters_by_semester(): void
    {
        $this->createReport();

        $otherSemester = Semester::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'order_number' => 2,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.classes.grade-reports.index', [
                'class' => $this->class->id,
                'semester_id' => $otherSemester->id,
            ]))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Admin/GradeReports/Index')
                    ->has('reports', 0)
            );
    }

    // ---------------------------------------------------------------
    // Show
    // ---------------------------------------------------------------

    public function test_admin_can_view_grade_report(): void
    {
        $report = $this->createReport();

        $this->actingAs($this->admin)
            ->get(route('admin.grade-reports.show', $report->id))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Admin/GradeReports/Show')
                    ->has('report')
                    ->has('permissions')
            );
    }

    public function test_student_cannot_view_draft_report(): void
    {
        $report = $this->createReport(GradeReportStatus::Draft);

        $this->actingAs($this->student)
            ->get(route('admin.grade-reports.show', $report->id))
            ->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Generate
    // ---------------------------------------------------------------

    public function test_admin_can_generate_grade_reports(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.classes.grade-reports.generate', $this->class->id), [
                'semester_id' => $this->semester->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('grade_reports', [
            'enrollment_id' => $this->enrollment->id,
            'semester_id' => $this->semester->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => GradeReportStatus::Draft->value,
        ]);
    }

    public function test_teacher_cannot_generate_grade_reports(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('admin.classes.grade-reports.generate', $this->class->id))
            ->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Update General Remark
    // ---------------------------------------------------------------

    public function test_admin_can_update_general_remark(): void
    {
        $report = $this->createReport();

        $this->actingAs($this->admin)
            ->put(route('admin.grade-reports.update-general-remark', $report->id), [
                'general_remark' => 'Good progress this semester.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertEquals('Good progress this semester.', $report->general_remark);
    }

    public function test_general_remark_validation_rejects_empty(): void
    {
        $report = $this->createReport();

        $this->actingAs($this->admin)
            ->put(route('admin.grade-reports.update-general-remark', $report->id), [
                'general_remark' => '',
            ])
            ->assertSessionHasErrors('general_remark');
    }

    public function test_cannot_update_remark_on_validated_report(): void
    {
        $report = $this->createReport(GradeReportStatus::Validated);

        $this->actingAs($this->admin)
            ->put(route('admin.grade-reports.update-general-remark', $report->id), [
                'general_remark' => 'Too late',
            ])
            ->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Validate
    // ---------------------------------------------------------------

    public function test_admin_can_validate_report(): void
    {
        $report = $this->createReport();

        $this->actingAs($this->admin)
            ->post(route('admin.grade-reports.validate', $report->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertEquals(GradeReportStatus::Validated, $report->status);
        $this->assertEquals($this->admin->id, $report->validated_by);
        $this->assertNotNull($report->validated_at);
    }

    public function test_cannot_validate_already_validated_report(): void
    {
        $report = $this->createReport(GradeReportStatus::Validated);

        $this->actingAs($this->admin)
            ->post(route('admin.grade-reports.validate', $report->id))
            ->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Validate Batch
    // ---------------------------------------------------------------

    public function test_admin_can_validate_batch(): void
    {
        $this->createReport();

        $student2 = $this->createStudent();
        $enrollment2 = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student2->id,
            'status' => 'active',
        ]);
        $this->createReportForEnrollment($enrollment2);

        $this->actingAs($this->admin)
            ->post(route('admin.classes.grade-reports.validate-batch', $this->class->id), [
                'semester_id' => $this->semester->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(
            0,
            GradeReport::where('status', GradeReportStatus::Draft)->count()
        );
    }

    // ---------------------------------------------------------------
    // Publish
    // ---------------------------------------------------------------

    public function test_admin_can_publish_validated_report(): void
    {
        $report = $this->createReport(GradeReportStatus::Validated);

        $this->actingAs($this->admin)
            ->post(route('admin.grade-reports.publish', $report->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $report->refresh();
        $this->assertEquals(GradeReportStatus::Published, $report->status);
    }

    public function test_cannot_publish_draft_report(): void
    {
        $report = $this->createReport(GradeReportStatus::Draft);

        $this->actingAs($this->admin)
            ->post(route('admin.grade-reports.publish', $report->id))
            ->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Publish Batch
    // ---------------------------------------------------------------

    public function test_admin_can_publish_batch(): void
    {
        $this->createReport(GradeReportStatus::Validated);

        $student2 = $this->createStudent();
        $enrollment2 = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student2->id,
            'status' => 'active',
        ]);
        $this->createReportForEnrollment($enrollment2, GradeReportStatus::Validated);

        $this->actingAs($this->admin)
            ->post(route('admin.classes.grade-reports.publish-batch', $this->class->id), [
                'semester_id' => $this->semester->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(
            2,
            GradeReport::where('status', GradeReportStatus::Published)->count()
        );
    }

    // ---------------------------------------------------------------
    // Preview
    // ---------------------------------------------------------------

    public function test_guest_cannot_preview_grade_report(): void
    {
        $report = $this->createReport();

        $this->get(route('admin.grade-reports.preview', $report->id))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_preview_draft_report(): void
    {
        $report = $this->createReport(GradeReportStatus::Draft);

        $this->actingAs($this->admin)
            ->get(route('admin.grade-reports.preview', $report->id))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_admin_can_preview_validated_report(): void
    {
        $report = $this->createReport(GradeReportStatus::Validated);

        $this->actingAs($this->admin)
            ->get(route('admin.grade-reports.preview', $report->id))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_admin_can_preview_published_report(): void
    {
        $report = $this->createReport(GradeReportStatus::Published);

        $this->actingAs($this->admin)
            ->get(route('admin.grade-reports.preview', $report->id))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    // ---------------------------------------------------------------
    // Download
    // ---------------------------------------------------------------

    public function test_download_returns_error_when_no_pdf(): void
    {
        $report = $this->createReport(GradeReportStatus::Validated);

        $this->actingAs($this->admin)
            ->get(route('admin.grade-reports.download', $report->id))
            ->assertRedirect()
            ->assertSessionHas('error');
    }
}
