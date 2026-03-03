<?php

namespace Tests\Feature\Policies;

use App\Enums\GradeReportStatus;
use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\GradeReport;
use App\Models\Level;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use App\Policies\GradeReportPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class GradeReportPolicyTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private GradeReportPolicy $policy;

    private AcademicYear $academicYear;

    private ClassModel $class;

    private Level $level;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        $this->policy = new GradeReportPolicy;

        $this->academicYear = AcademicYear::factory()->create(['is_current' => true]);
        $this->level = Level::factory()->create();
        $this->class = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);
    }

    private int $semesterCounter = 0;

    private function createReportForStudent(User $student, GradeReportStatus $status = GradeReportStatus::Draft, ?User $teacher = null): GradeReport
    {
        $this->semesterCounter++;

        $semester = Semester::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'order_number' => $this->semesterCounter,
        ]);

        $enrollment = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        if ($teacher) {
            $subject = Subject::factory()->create(['level_id' => $this->level->id]);
            ClassSubject::factory()->create([
                'class_id' => $this->class->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'semester_id' => $semester->id,
            ]);
        }

        return GradeReport::factory()->create([
            'enrollment_id' => $enrollment->id,
            'semester_id' => $semester->id,
            'academic_year_id' => $this->academicYear->id,
            'status' => $status,
        ]);
    }

    #[Test]
    public function admin_can_view_any_reports(): void
    {
        $admin = $this->createAdmin();
        $this->assertTrue($this->policy->viewAny($admin));
    }

    #[Test]
    public function teacher_can_view_any_reports(): void
    {
        $teacher = $this->createTeacher();
        $this->assertTrue($this->policy->viewAny($teacher));
    }

    #[Test]
    public function student_can_view_any_reports(): void
    {
        $student = $this->createStudent();
        $this->assertTrue($this->policy->viewAny($student));
    }

    #[Test]
    public function admin_can_view_any_report(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $report = $this->createReportForStudent($student);

        $this->assertTrue($this->policy->view($admin, $report));
    }

    #[Test]
    public function student_can_view_own_published_report(): void
    {
        $student = $this->createStudent();
        $report = $this->createReportForStudent($student, GradeReportStatus::Published);

        $this->assertTrue($this->policy->view($student, $report));
    }

    #[Test]
    public function student_cannot_view_own_draft_report(): void
    {
        $student = $this->createStudent();
        $report = $this->createReportForStudent($student, GradeReportStatus::Draft);

        $this->assertFalse($this->policy->view($student, $report));
    }

    #[Test]
    public function student_cannot_view_other_students_report(): void
    {
        $student1 = $this->createStudent();
        $student2 = $this->createStudent();
        $report = $this->createReportForStudent($student1, GradeReportStatus::Published);

        $this->assertFalse($this->policy->view($student2, $report));
    }

    #[Test]
    public function only_admin_can_create_reports(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $student = $this->createStudent();

        $this->assertTrue($this->policy->create($admin));
        $this->assertFalse($this->policy->create($teacher));
        $this->assertFalse($this->policy->create($student));
    }

    #[Test]
    public function teacher_can_update_remarks_on_draft_for_their_class(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();
        $report = $this->createReportForStudent($student, GradeReportStatus::Draft, $teacher);

        $this->assertTrue($this->policy->updateRemarks($teacher, $report));
    }

    #[Test]
    public function teacher_cannot_update_remarks_on_validated_report(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();
        $report = $this->createReportForStudent($student, GradeReportStatus::Validated, $teacher);

        $this->assertFalse($this->policy->updateRemarks($teacher, $report));
    }

    #[Test]
    public function only_admin_can_update_general_remark_on_draft(): void
    {
        $admin = $this->createAdmin();
        $teacher = $this->createTeacher();
        $student = $this->createStudent();
        $report = $this->createReportForStudent($student, GradeReportStatus::Draft);

        $this->assertTrue($this->policy->updateGeneralRemark($admin, $report));
        $this->assertFalse($this->policy->updateGeneralRemark($teacher, $report));
    }

    #[Test]
    public function only_admin_can_validate_draft_report(): void
    {
        $admin = $this->createAdmin();
        $student1 = $this->createStudent();
        $student2 = $this->createStudent();
        $draftReport = $this->createReportForStudent($student1, GradeReportStatus::Draft);
        $validatedReport = $this->createReportForStudent($student2, GradeReportStatus::Validated);

        $this->assertTrue($this->policy->validate($admin, $draftReport));
        $this->assertFalse($this->policy->validate($admin, $validatedReport));
    }

    #[Test]
    public function only_admin_can_publish_validated_report(): void
    {
        $admin = $this->createAdmin();
        $student1 = $this->createStudent();
        $student2 = $this->createStudent();
        $validatedReport = $this->createReportForStudent($student1, GradeReportStatus::Validated);
        $draftReport = $this->createReportForStudent($student2, GradeReportStatus::Draft);

        $this->assertTrue($this->policy->publish($admin, $validatedReport));
        $this->assertFalse($this->policy->publish($admin, $draftReport));
    }

    #[Test]
    public function admin_can_download_any_report(): void
    {
        $admin = $this->createAdmin();
        $student = $this->createStudent();
        $report = $this->createReportForStudent($student, GradeReportStatus::Draft);

        $this->assertTrue($this->policy->download($admin, $report));
    }

    #[Test]
    public function student_can_download_own_published_report(): void
    {
        $student = $this->createStudent();
        $report = $this->createReportForStudent($student, GradeReportStatus::Published);

        $this->assertTrue($this->policy->download($student, $report));
    }

    #[Test]
    public function student_cannot_download_unpublished_report(): void
    {
        $student = $this->createStudent();
        $report = $this->createReportForStudent($student, GradeReportStatus::Draft);

        $this->assertFalse($this->policy->download($student, $report));
    }
}
