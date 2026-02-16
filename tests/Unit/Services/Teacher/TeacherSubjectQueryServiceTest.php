<?php

namespace Tests\Unit\Services\Teacher;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\ClassSubject;
use App\Models\Subject;
use App\Services\Teacher\TeacherSubjectQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class TeacherSubjectQueryServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private TeacherSubjectQueryService $service;

    private AcademicYear $academicYear;

    private \App\Models\Level $level;

    private \App\Models\Semester $semester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        $this->service = app(TeacherSubjectQueryService::class);
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
    public function it_gets_subjects_for_teacher_with_aggregated_data(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher1@test.com']);

        $subjects = Subject::factory()->count(3)->sequence(
            ['code' => 'MATH01', 'name' => 'Mathematics'],
            ['code' => 'PHYS01', 'name' => 'Physics'],
            ['code' => 'CHEM01', 'name' => 'Chemistry']
        )->create();

        foreach ($subjects as $index => $subject) {
            $class = \App\Models\ClassModel::factory()->create([
                'academic_year_id' => $this->academicYear->id,
                'level_id' => $this->level->id,
                'name' => 'Class '.($index + 1),
            ]);

            $classSubject = ClassSubject::factory()->create([
                'semester_id' => $this->semester->id,
                'teacher_id' => $teacher->id,
                'subject_id' => $subject->id,
                'class_id' => $class->id,
            ]);

            Assessment::factory()->count(2)->create([
                'class_subject_id' => $classSubject->id,
            ]);
        }

        $result = $this->service->getSubjectsForTeacher(
            $teacher->id,
            $this->academicYear->id,
            [],
            10
        );

        $this->assertEquals(3, $result->total());

        foreach ($result->items() as $subject) {
            $this->assertEquals(2, $subject->assessments_count);
            $this->assertGreaterThanOrEqual(1, $subject->classes_count);
        }
    }

    #[Test]
    public function get_subjects_for_teacher_does_not_cause_n_plus_one(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher2@test.com']);

        $subjects = Subject::factory()->count(5)->sequence(
            ['code' => 'MATH02', 'name' => 'Math Advanced'],
            ['code' => 'PHYS02', 'name' => 'Physics Advanced'],
            ['code' => 'CHEM02', 'name' => 'Chemistry Advanced'],
            ['code' => 'BIO02', 'name' => 'Biology'],
            ['code' => 'HIST02', 'name' => 'History']
        )->create();

        foreach ($subjects as $index => $subject) {
            $class = \App\Models\ClassModel::factory()->create([
                'academic_year_id' => $this->academicYear->id,
                'level_id' => $this->level->id,
                'name' => 'Class '.chr(65 + $index),
            ]);

            $classSubject = ClassSubject::factory()->create([
                'semester_id' => $this->semester->id,
                'teacher_id' => $teacher->id,
                'subject_id' => $subject->id,
                'class_id' => $class->id,
            ]);

            Assessment::factory()->count(3)->create([
                'class_subject_id' => $classSubject->id,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $result = $this->service->getSubjectsForTeacher(
            $teacher->id,
            $this->academicYear->id,
            [],
            10
        );

        $queryCount = count(DB::getQueryLog());

        $this->assertEquals(5, $result->total());
        $this->assertLessThanOrEqual(4, $queryCount, "Expected <= 4 queries (optimized from N+1), but got {$queryCount}");

        foreach ($result->items() as $subject) {
            $this->assertEquals(3, $subject->assessments_count);
        }
    }

    #[Test]
    public function it_handles_empty_results_gracefully(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher3@test.com']);

        $result = $this->service->getSubjectsForTeacher(
            $teacher->id,
            $this->academicYear->id,
            [],
            10
        );

        $this->assertEquals(0, $result->total());
        $this->assertEmpty($result->items());
    }

    #[Test]
    public function it_filters_subjects_by_search(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher4@test.com']);

        $subjects = Subject::factory()->count(2)->sequence(
            ['code' => 'MATH03', 'name' => 'Mathematics'],
            ['code' => 'PHYS03', 'name' => 'Physics']
        )->create();

        foreach ($subjects as $index => $subject) {
            $class = \App\Models\ClassModel::factory()->create([
                'academic_year_id' => $this->academicYear->id,
                'level_id' => $this->level->id,
                'name' => 'Search Test Class '.($index + 1),
            ]);

            ClassSubject::factory()->create([
                'semester_id' => $this->semester->id,
                'teacher_id' => $teacher->id,
                'subject_id' => $subject->id,
                'class_id' => $class->id,
            ]);
        }

        $result = $this->service->getSubjectsForTeacher(
            $teacher->id,
            $this->academicYear->id,
            ['search' => 'Math'],
            10
        );

        $this->assertEquals(1, $result->total());
        $this->assertEquals('Mathematics', $result->items()[0]->name);
    }

    #[Test]
    public function it_filters_subjects_by_class_id(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher5@test.com']);

        $subjects = Subject::factory()->count(2)->sequence(
            ['code' => 'MATH04', 'name' => 'Math'],
            ['code' => 'PHYS04', 'name' => 'Physics']
        )->create();

        $class1 = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'name' => 'Filter Class A',
        ]);

        $class2 = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'name' => 'Filter Class B',
        ]);

        ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subjects[0]->id,
            'class_id' => $class1->id,
        ]);

        ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subjects[1]->id,
            'class_id' => $class2->id,
        ]);

        $result = $this->service->getSubjectsForTeacher(
            $teacher->id,
            $this->academicYear->id,
            ['class_id' => $class1->id],
            10
        );

        $this->assertEquals(1, $result->total());
        $this->assertEquals('Math', $result->items()[0]->name);
    }

    #[Test]
    public function it_gets_classes_for_filter(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher6@test.com']);

        $class1 = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'name' => 'Classes Filter A',
        ]);

        $class2 = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'name' => 'Classes Filter B',
        ]);

        $subject = Subject::factory()->create(['code' => 'MATH05', 'name' => 'Mathematics']);

        ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'class_id' => $class1->id,
        ]);

        ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'class_id' => $class2->id,
        ]);

        $classes = $this->service->getClassesForFilter($teacher->id, $this->academicYear->id);

        $this->assertCount(2, $classes);
        $this->assertTrue($classes->contains('id', $class1->id));
        $this->assertTrue($classes->contains('id', $class2->id));
    }

    #[Test]
    public function it_gets_subject_details_with_classes_info(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher7@test.com']);
        $subject = Subject::factory()->create(['code' => 'MATH06', 'name' => 'Mathematics']);

        $class1 = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'name' => 'Details Class A',
        ]);

        $class2 = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'name' => 'Details Class B',
        ]);

        $classSubject1 = ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'class_id' => $class1->id,
        ]);

        $classSubject2 = ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'class_id' => $class2->id,
        ]);

        Assessment::factory()->count(2)->create(['class_subject_id' => $classSubject1->id]);
        Assessment::factory()->count(3)->create(['class_subject_id' => $classSubject2->id]);

        $result = $this->service->getSubjectDetails($subject, $teacher->id, $this->academicYear->id);

        $this->assertEquals('Mathematics', $result->name);
        $this->assertCount(2, $result->classes);
        $this->assertCount(2, $result->class_subjects);
        $this->assertEquals(5, $result->total_assessments);
    }

    #[Test]
    public function it_gets_assessments_for_subject(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher8@test.com']);
        $subject = Subject::factory()->create(['code' => 'MATH07', 'name' => 'Mathematics']);

        $class = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'name' => 'Assessments Class',
        ]);

        $classSubject = ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'class_id' => $class->id,
        ]);

        Assessment::factory()->count(3)->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Math Test',
        ]);

        $result = $this->service->getAssessmentsForSubject(
            $subject,
            $teacher->id,
            $this->academicYear->id,
            [],
            10
        );

        $this->assertEquals(3, $result->total());
    }

    #[Test]
    public function it_filters_assessments_by_search(): void
    {
        $teacher = $this->createTeacher(['email' => 'teacher9@test.com']);
        $subject = Subject::factory()->create(['code' => 'MATH08', 'name' => 'Mathematics']);

        $class = \App\Models\ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'name' => 'Search Assessments Class',
        ]);

        $classSubject = ClassSubject::factory()->create([
            'semester_id' => $this->semester->id,
            'teacher_id' => $teacher->id,
            'subject_id' => $subject->id,
            'class_id' => $class->id,
        ]);

        Assessment::factory()->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Algebra Test',
        ]);

        Assessment::factory()->create([
            'class_subject_id' => $classSubject->id,
            'title' => 'Geometry Quiz',
        ]);

        $result = $this->service->getAssessmentsForSubject(
            $subject,
            $teacher->id,
            $this->academicYear->id,
            ['search' => 'Algebra'],
            10
        );

        $this->assertEquals(1, $result->total());
        $this->assertEquals('Algebra Test', $result->items()[0]->title);
    }
}
