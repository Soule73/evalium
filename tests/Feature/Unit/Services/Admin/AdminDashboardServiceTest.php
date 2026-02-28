<?php

namespace Tests\Feature\Unit\Services\Admin;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Semester;
use App\Models\Subject;
use App\Services\Admin\AdminDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class AdminDashboardServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private AdminDashboardService $service;

    private AcademicYear $academicYear;

    private Level $level;

    private Semester $semester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        $this->service = app(AdminDashboardService::class);
        $this->academicYear = AcademicYear::factory()->create([
            'name' => '2025-2026',
            'is_current' => true,
        ]);
        $this->level = Level::factory()->create([
            'name' => 'License 1',
            'code' => 'L1',
        ]);
        $this->semester = Semester::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Semester 1',
            'order_number' => 1,
        ]);
    }

    #[Test]
    public function get_dashboard_stats_returns_user_counts_by_role(): void
    {
        $this->createMultipleStudents(3);
        $this->createTeacher();

        $stats = $this->service->getDashboardStats();

        $this->assertGreaterThanOrEqual(3, $stats['studentsCount']);
        $this->assertGreaterThanOrEqual(1, $stats['teachersCount']);
        $this->assertArrayHasKey('totalUsers', $stats);
        $this->assertArrayHasKey('adminsCount', $stats);
    }

    #[Test]
    public function get_dashboard_stats_returns_year_scoped_counts(): void
    {
        $class = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'max_students' => 30,
        ]);

        $student = $this->createStudent();
        Enrollment::factory()->create([
            'class_id' => $class->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $stats = $this->service->getDashboardStats($this->academicYear->id);

        $this->assertEquals(1, $stats['classesCount']);
        $this->assertEquals(1, $stats['enrollmentsCount']);
    }

    #[Test]
    public function get_users_by_role_chart_returns_donut_data(): void
    {
        $this->createMultipleStudents(5);
        $this->createTeacher();
        $this->createAdmin();

        $data = $this->service->getUsersByRoleChart();

        $this->assertNotEmpty($data);
        foreach ($data as $item) {
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('value', $item);
            $this->assertArrayHasKey('color', $item);
            $this->assertGreaterThan(0, $item['value']);
        }

        $roleNames = array_column($data, 'name');
        $this->assertContains('Student', $roleNames);
        $this->assertContains('Teacher', $roleNames);
    }

    #[Test]
    public function get_classes_by_level_chart_returns_bar_data(): void
    {
        $level2 = Level::factory()->create(['name' => 'License 2', 'code' => 'L2']);

        ClassModel::factory()->count(3)->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);
        ClassModel::factory()->count(2)->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $level2->id,
        ]);

        $data = $this->service->getClassesByLevelChart($this->academicYear->id);

        $this->assertCount(2, $data);
        $this->assertEquals('License 1', $data[0]->name);
        $this->assertEquals(3, $data[0]->value);
        $this->assertEquals('License 2', $data[1]->name);
        $this->assertEquals(2, $data[1]->value);
    }

    #[Test]
    public function get_enrollment_capacity_chart_returns_enrolled_vs_max(): void
    {
        $class = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'max_students' => 30,
        ]);

        $students = $this->createMultipleStudents(5);
        foreach ($students as $student) {
            Enrollment::factory()->create([
                'class_id' => $class->id,
                'student_id' => $student->id,
                'status' => 'active',
            ]);
        }

        $data = $this->service->getEnrollmentCapacityChart($this->academicYear->id);

        $this->assertCount(1, $data);
        $this->assertEquals(5, $data[0]->enrolled);
        $this->assertEquals(30, $data[0]->capacity);
    }

    #[Test]
    public function get_assessment_status_counts_returns_published_and_draft(): void
    {
        $teacher = $this->createTeacher();
        $subject = Subject::factory()->create(['code' => 'MATH01']);
        $class = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);
        $classSubject = ClassSubject::factory()->create([
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $this->semester->id,
        ]);

        Assessment::factory()->count(3)->create([
            'class_subject_id' => $classSubject->id,
            'is_published' => true,
        ]);
        Assessment::factory()->count(2)->create([
            'class_subject_id' => $classSubject->id,
            'is_published' => false,
        ]);

        $counts = $this->service->getAssessmentStatusCounts($this->academicYear->id);

        $this->assertEquals(3, $counts['published']);
        $this->assertEquals(2, $counts['draft']);
        $this->assertEquals(5, $counts['total']);
    }

    #[Test]
    public function get_chart_data_returns_all_chart_datasets(): void
    {
        $data = $this->service->getChartData($this->academicYear->id);

        $this->assertArrayHasKey('usersByRole', $data);
        $this->assertArrayHasKey('classesByLevel', $data);
        $this->assertArrayHasKey('enrollmentCapacity', $data);
    }

    #[Test]
    public function get_dashboard_data_includes_assessment_counts(): void
    {
        $data = $this->service->getDashboardData($this->academicYear->id);

        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('assessmentsCount', $data['stats']);
        $this->assertArrayHasKey('publishedCount', $data['stats']);
        $this->assertArrayHasKey('draftCount', $data['stats']);
    }
}
