<?php

namespace Tests\Unit\Services\Teacher;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Services\Teacher\TeacherDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class TeacherDashboardServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private TeacherDashboardService $service;

    private AcademicYear $academicYear;

    private \App\Models\Level $level;

    private \App\Models\Semester $semester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        $this->service = app(TeacherDashboardService::class);
        $this->academicYear = AcademicYear::factory()->create([
            'name' => '2025-2026',
            'is_current' => true,
        ]);
        $this->level = \App\Models\Level::factory()->create([
            'name' => 'Test Level',
            'code' => 'TEST_LVL',
        ]);
        $this->semester = \App\Models\Semester::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Semestre 1',
            'order_number' => 1,
        ]);
    }

    #[Test]
    public function get_dashboard_stats_uses_sql_aggregation_not_php_counting(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher1@test.com']);

        $classes = \App\Models\ClassModel::factory()->count(3)->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);

        $subjects = \App\Models\Subject::factory()->count(4)->sequence(
            ['code' => 'MATH01', 'name' => 'Mathematics'],
            ['code' => 'PHYS01', 'name' => 'Physics'],
            ['code' => 'CHEM01', 'name' => 'Chemistry'],
            ['code' => 'BIO01', 'name' => 'Biology']
        )->create();

        foreach ($classes as $class) {
            foreach ($subjects->take(2) as $subject) {
                $classSubject = ClassSubject::factory()->create([
                    'semester_id' => $this->semester->id,
                    'teacher_id' => $teacher->id,
                    'class_id' => $class->id,
                    'subject_id' => $subject->id,
                ]);

                Assessment::factory()->count(2)->create([
                    'class_subject_id' => $classSubject->id,
                ]);
            }
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $stats = $this->service->getDashboardStats(
            $teacher->id,
            $this->academicYear->id
        );

        $queryLog = DB::getQueryLog();
        $queryCount = count($queryLog);

        $this->assertLessThanOrEqual(3, $queryCount, "Expected <= 3 queries (COUNT DISTINCT + assessments count + in_progress count), but got {$queryCount}");

        $firstQuery = $queryLog[0]['query'] ?? '';
        $this->assertStringContainsString('COUNT(DISTINCT', $firstQuery, 'Should use COUNT(DISTINCT) in SQL, not PHP counting');

        $this->assertEquals(3, $stats['total_classes']);
        $this->assertEquals(2, $stats['total_subjects']);
        $this->assertEquals(12, $stats['total_assessments']);
        $this->assertArrayHasKey('in_progress_assessments', $stats);
    }

    #[Test]
    public function get_dashboard_stats_handles_empty_assignments(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher2@test.com']);

        $stats = $this->service->getDashboardStats(
            $teacher->id,
            $this->academicYear->id
        );

        $this->assertEquals(0, $stats['total_classes']);
        $this->assertEquals(0, $stats['total_subjects']);
        $this->assertEquals(0, $stats['total_assessments']);
    }

    #[Test]
    public function get_dashboard_stats_counts_unique_classes_and_subjects(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher3@test.com']);

        $class1 = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);

        $subject1 = \App\Models\Subject::factory()->create(['code' => 'MATH02', 'name' => 'Math']);

        ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class1->id,
            'subject_id' => $subject1->id,
        ]);

        ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class1->id,
            'subject_id' => $subject1->id,
        ]);

        $stats = $this->service->getDashboardStats(
            $teacher->id,
            $this->academicYear->id
        );

        $this->assertEquals(1, $stats['total_classes'], 'Should count unique classes only');
        $this->assertEquals(1, $stats['total_subjects'], 'Should count unique subjects only');
    }

    #[Test]
    public function get_dashboard_stats_filters_by_academic_year(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher4@test.com']);

        $anotherYear = AcademicYear::factory()->create([
            'name' => '2024-2025',
            'is_current' => false,
        ]);

        $classCurrentYear = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);

        $classOtherYear = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $anotherYear->id,
            'level_id' => $this->level->id,
        ]);

        $subject = \App\Models\Subject::factory()->create(['code' => 'MATH03', 'name' => 'Math']);

        ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $classCurrentYear->id,
            'subject_id' => $subject->id,
        ]);

        $otherSemester = \App\Models\Semester::factory()->create([
            'academic_year_id' => $anotherYear->id,
            'name' => 'Semestre 1',
            'order_number' => 1,
        ]);

        ClassSubject::factory()->create([
            'semester_id' => $otherSemester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $classOtherYear->id,
            'subject_id' => $subject->id,
        ]);

        $stats = $this->service->getDashboardStats(
            $teacher->id,
            $this->academicYear->id
        );

        $this->assertEquals(1, $stats['total_classes'], 'Should only count classes from current academic year');
    }

    #[Test]
    public function get_dashboard_stats_only_counts_active_assignments(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher5@test.com']);

        $class = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);

        $subject = \App\Models\Subject::factory()->create(['code' => 'MATH04', 'name' => 'Math']);

        ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'valid_to' => null,
        ]);

        ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'valid_to' => now()->subDay(),
        ]);

        $stats = $this->service->getDashboardStats(
            $teacher->id,
            $this->academicYear->id
        );

        $this->assertEquals(1, $stats['total_classes'], 'Should only count active assignments (valid_to = null)');
    }

    #[Test]
    public function get_recent_assessments_returns_latest_limited_to_three(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher6@test.com']);

        $class = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);

        $subject = \App\Models\Subject::factory()->create(['code' => 'MATH05', 'name' => 'Math']);

        $classSubject = ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
        ]);

        Assessment::factory()->count(5)->sequence(
            ['scheduled_at' => now()->subDays(5), 'class_subject_id' => $classSubject->id],
            ['scheduled_at' => now()->subDays(3), 'class_subject_id' => $classSubject->id],
            ['scheduled_at' => now()->subDays(1), 'class_subject_id' => $classSubject->id],
            ['scheduled_at' => now()->addDays(1), 'class_subject_id' => $classSubject->id],
            ['scheduled_at' => now()->addDays(3), 'class_subject_id' => $classSubject->id],
        )->create();

        $result = $this->service->getRecentAssessments($teacher->id, $this->academicYear->id);

        $this->assertCount(3, $result->items());
    }

    #[Test]
    public function get_recent_assessments_excludes_other_teachers_assessments(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher7@test.com']);
        $otherTeacher = $this->createTeacher(['email' => 'teacher8@test.com']);

        $class = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);

        $subject = \App\Models\Subject::factory()->create(['code' => 'MATH06', 'name' => 'Math']);

        $ownClassSubject = ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
        ]);

        $otherClassSubject = ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $otherTeacher->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
        ]);

        Assessment::factory()->create(['class_subject_id' => $ownClassSubject->id]);
        Assessment::factory()->create(['class_subject_id' => $otherClassSubject->id]);

        $result = $this->service->getRecentAssessments($teacher->id, $this->academicYear->id);

        $this->assertCount(1, $result->items());
    }
}
