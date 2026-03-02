<?php

namespace Tests\Feature\Services\Core;

use App\Models\AcademicYear;
use App\Models\Answer;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\Question;
use App\Models\Semester;
use App\Models\Subject;
use App\Services\Core\GradeCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class GradeCalculationServiceTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private GradeCalculationService $service;

    private AcademicYear $academicYear;

    private Semester $semester1;

    private Semester $semester2;

    private ClassModel $class;

    private Level $level;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();
        $this->service = app(GradeCalculationService::class);

        $this->academicYear = AcademicYear::factory()->create([
            'is_current' => true,
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
        ]);

        $this->semester1 = Semester::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Semestre 1',
            'order_number' => 1,
            'start_date' => '2025-09-01',
            'end_date' => '2026-01-31',
        ]);

        $this->semester2 = Semester::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'name' => 'Semestre 2',
            'order_number' => 2,
            'start_date' => '2026-02-01',
            'end_date' => '2026-06-30',
        ]);

        $this->level = Level::factory()->create();

        $this->class = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);
    }

    /**
     * @return array{enrollment: Enrollment, classSubject: ClassSubject, assessment: Assessment}
     */
    private function createGradedScenario(
        Semester $semester,
        float $score,
        float $maxPoints = 20.0,
        float $assessmentCoefficient = 1.0,
        float $subjectCoefficient = 2.0
    ): array {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();
        $subject = Subject::factory()->create(['level_id' => $this->level->id]);

        $classSubject = ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $semester->id,
            'coefficient' => $subjectCoefficient,
        ]);

        $enrollment = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $assessment = Assessment::factory()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $teacher->id,
            'coefficient' => $assessmentCoefficient,
            'is_published' => true,
        ]);

        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'points' => $maxPoints,
        ]);

        $assignment = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now()->subHour(),
            'graded_at' => now()->subMinutes(30),
        ]);

        Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'score' => $score,
        ]);

        return compact('enrollment', 'classSubject', 'assessment');
    }

    #[Test]
    public function it_calculates_semester_grade_for_matching_semester(): void
    {
        $data = $this->createGradedScenario($this->semester1, score: 15.0, maxPoints: 20.0);

        $grade = $this->service->calculateSemesterGrade(
            $data['enrollment'],
            $data['classSubject'],
            $this->semester1
        );

        $this->assertNotNull($grade);
        $this->assertEquals(15.0, $grade);
    }

    #[Test]
    public function it_returns_null_for_mismatched_semester(): void
    {
        $data = $this->createGradedScenario($this->semester1, score: 15.0);

        $grade = $this->service->calculateSemesterGrade(
            $data['enrollment'],
            $data['classSubject'],
            $this->semester2
        );

        $this->assertNull($grade);
    }

    #[Test]
    public function it_calculates_semester_average_across_subjects(): void
    {
        $student = $this->createStudent();
        $enrollment = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $teacher = $this->createTeacher();

        $subject1 = Subject::factory()->create(['level_id' => $this->level->id]);
        $cs1 = ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $subject1->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $this->semester1->id,
            'coefficient' => 3,
        ]);

        $subject2 = Subject::factory()->create(['level_id' => $this->level->id]);
        $cs2 = ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $subject2->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $this->semester1->id,
            'coefficient' => 2,
        ]);

        $this->createGradedAssessmentForEnrollment($enrollment, $cs1, 16.0, 20.0);
        $this->createGradedAssessmentForEnrollment($enrollment, $cs2, 12.0, 20.0);

        $result = $this->service->calculateSemesterAverage($enrollment, $this->semester1);

        $this->assertNotNull($result);
        $this->assertArrayHasKey('average', $result);
        $this->assertArrayHasKey('total_coefficient', $result);
        $this->assertArrayHasKey('subjects', $result);
        $this->assertCount(2, $result['subjects']);

        $expectedAverage = round((3 * 16.0 + 2 * 12.0) / (3 + 2), 2);
        $this->assertEquals($expectedAverage, $result['average']);
        $this->assertEquals(5.0, $result['total_coefficient']);
    }

    #[Test]
    public function it_returns_null_for_semester_with_no_subjects(): void
    {
        $student = $this->createStudent();
        $enrollment = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $result = $this->service->calculateSemesterAverage($enrollment, $this->semester2);

        $this->assertNull($result);
    }

    #[Test]
    public function it_returns_semester_breakdown_with_correct_structure(): void
    {
        $student = $this->createStudent();
        $enrollment = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $teacher = $this->createTeacher();
        $subject = Subject::factory()->create(['level_id' => $this->level->id]);
        $cs = ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $this->semester1->id,
            'coefficient' => 2,
        ]);

        $this->createGradedAssessmentForEnrollment($enrollment, $cs, 14.0, 20.0);

        $breakdown = $this->service->getSemesterGradeBreakdown($enrollment, $this->semester1);

        $this->assertEquals($student->id, $breakdown['student_id']);
        $this->assertEquals($student->name, $breakdown['student_name']);
        $this->assertEquals($this->class->id, $breakdown['class_id']);
        $this->assertArrayHasKey('subjects', $breakdown);
        $this->assertArrayHasKey('semester_average', $breakdown);
        $this->assertArrayHasKey('total_coefficient', $breakdown);
        $this->assertCount(1, $breakdown['subjects']);
        $this->assertEquals(14.0, $breakdown['semester_average']);
    }

    #[Test]
    public function it_excludes_other_semester_subjects_from_breakdown(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $enrollment = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $subject1 = Subject::factory()->create(['level_id' => $this->level->id]);
        ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $subject1->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $this->semester1->id,
            'coefficient' => 2,
        ]);

        $subject2 = Subject::factory()->create(['level_id' => $this->level->id]);
        ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $subject2->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $this->semester2->id,
            'coefficient' => 3,
        ]);

        $breakdown = $this->service->getSemesterGradeBreakdown($enrollment, $this->semester1);

        $this->assertCount(1, $breakdown['subjects']);
    }

    #[Test]
    public function it_ranks_students_by_descending_average(): void
    {
        $teacher = $this->createTeacher();
        $subject = Subject::factory()->create(['level_id' => $this->level->id]);
        $cs = ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $this->semester1->id,
            'coefficient' => 1,
        ]);

        $student1 = $this->createStudent();
        $enrollment1 = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student1->id,
            'status' => 'active',
        ]);
        $this->createGradedAssessmentForEnrollment($enrollment1, $cs, 18.0, 20.0);

        $student2 = $this->createStudent();
        $enrollment2 = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student2->id,
            'status' => 'active',
        ]);
        $this->createGradedAssessmentForEnrollment($enrollment2, $cs, 12.0, 20.0);

        $student3 = $this->createStudent();
        $enrollment3 = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student3->id,
            'status' => 'active',
        ]);
        $this->createGradedAssessmentForEnrollment($enrollment3, $cs, 15.0, 20.0);

        $ranking = $this->service->calculateClassRanking($this->class, $this->semester1);

        $this->assertCount(3, $ranking);
        $this->assertEquals(1, $ranking[0]['rank']);
        $this->assertEquals(18.0, $ranking[0]['average']);
        $this->assertEquals(2, $ranking[1]['rank']);
        $this->assertEquals(15.0, $ranking[1]['average']);
        $this->assertEquals(3, $ranking[2]['rank']);
        $this->assertEquals(12.0, $ranking[2]['average']);
    }

    #[Test]
    public function it_handles_tied_ranks_correctly(): void
    {
        $teacher = $this->createTeacher();
        $subject = Subject::factory()->create(['level_id' => $this->level->id]);
        $cs = ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $this->semester1->id,
            'coefficient' => 1,
        ]);

        $student1 = $this->createStudent();
        $enrollment1 = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student1->id,
            'status' => 'active',
        ]);
        $this->createGradedAssessmentForEnrollment($enrollment1, $cs, 16.0, 20.0);

        $student2 = $this->createStudent();
        $enrollment2 = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student2->id,
            'status' => 'active',
        ]);
        $this->createGradedAssessmentForEnrollment($enrollment2, $cs, 16.0, 20.0);

        $student3 = $this->createStudent();
        $enrollment3 = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student3->id,
            'status' => 'active',
        ]);
        $this->createGradedAssessmentForEnrollment($enrollment3, $cs, 10.0, 20.0);

        $ranking = $this->service->calculateClassRanking($this->class, $this->semester1);

        $this->assertCount(3, $ranking);
        $this->assertEquals(1, $ranking[0]['rank']);
        $this->assertEquals(1, $ranking[1]['rank']);
        $this->assertEquals(3, $ranking[2]['rank']);
    }

    #[Test]
    public function it_handles_students_with_no_grades_in_ranking(): void
    {
        $student = $this->createStudent();
        Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $ranking = $this->service->calculateClassRanking($this->class, $this->semester1);

        $this->assertCount(1, $ranking);
        $this->assertNull($ranking[0]['average']);
        $this->assertNull($ranking[0]['rank']);
    }

    #[Test]
    public function it_calculates_ranking_without_semester_for_annual(): void
    {
        $teacher = $this->createTeacher();
        $subject = Subject::factory()->create(['level_id' => $this->level->id]);
        $cs = ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $this->semester1->id,
            'coefficient' => 1,
        ]);

        $student1 = $this->createStudent();
        $enrollment1 = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student1->id,
            'status' => 'active',
        ]);
        $this->createGradedAssessmentForEnrollment($enrollment1, $cs, 18.0, 20.0);

        $student2 = $this->createStudent();
        $enrollment2 = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student2->id,
            'status' => 'active',
        ]);
        $this->createGradedAssessmentForEnrollment($enrollment2, $cs, 10.0, 20.0);

        $ranking = $this->service->calculateClassRanking($this->class);

        $this->assertCount(2, $ranking);
        $this->assertEquals(1, $ranking[0]['rank']);
        $this->assertEquals(2, $ranking[1]['rank']);
    }

    #[Test]
    public function it_excludes_withdrawn_students_from_ranking(): void
    {
        $teacher = $this->createTeacher();
        $subject = Subject::factory()->create(['level_id' => $this->level->id]);
        $cs = ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $this->semester1->id,
            'coefficient' => 1,
        ]);

        $activeStudent = $this->createStudent();
        $enrollment = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $activeStudent->id,
            'status' => 'active',
        ]);
        $this->createGradedAssessmentForEnrollment($enrollment, $cs, 14.0, 20.0);

        $withdrawnStudent = $this->createStudent();
        Enrollment::factory()->withdrawn()->create([
            'class_id' => $this->class->id,
            'student_id' => $withdrawnStudent->id,
        ]);

        $ranking = $this->service->calculateClassRanking($this->class, $this->semester1);

        $this->assertCount(1, $ranking);
    }

    #[Test]
    public function it_calculates_semester_average_with_null_for_ungraded_subjects(): void
    {
        $student = $this->createStudent();
        $enrollment = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $teacher = $this->createTeacher();

        $subject1 = Subject::factory()->create(['level_id' => $this->level->id]);
        $cs1 = ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $subject1->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $this->semester1->id,
            'coefficient' => 3,
        ]);

        $subject2 = Subject::factory()->create(['level_id' => $this->level->id]);
        ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $subject2->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $this->semester1->id,
            'coefficient' => 2,
        ]);

        $this->createGradedAssessmentForEnrollment($enrollment, $cs1, 16.0, 20.0);

        $result = $this->service->calculateSemesterAverage($enrollment, $this->semester1);

        $this->assertNotNull($result);
        $this->assertEquals(16.0, $result['average']);
        $this->assertEquals(3.0, $result['total_coefficient']);
        $this->assertCount(2, $result['subjects']);

        $ungradedSubject = collect($result['subjects'])->firstWhere('grade', null);
        $this->assertNotNull($ungradedSubject);
    }

    #[Test]
    public function it_handles_multiple_assessments_in_semester_grade(): void
    {
        $student = $this->createStudent();
        $teacher = $this->createTeacher();
        $enrollment = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $subject = Subject::factory()->create(['level_id' => $this->level->id]);
        $cs = ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'semester_id' => $this->semester1->id,
            'coefficient' => 2,
        ]);

        $this->createGradedAssessmentForEnrollment($enrollment, $cs, 16.0, 20.0, assessmentCoefficient: 2.0);
        $this->createGradedAssessmentForEnrollment($enrollment, $cs, 12.0, 20.0, assessmentCoefficient: 1.0);

        $grade = $this->service->calculateSemesterGrade($enrollment, $cs, $this->semester1);

        $expectedGrade = round((2.0 * 16.0 + 1.0 * 12.0) / (2.0 + 1.0), 2);
        $this->assertEquals($expectedGrade, $grade);
    }

    /**
     * Helper to create a graded assessment for an existing enrollment.
     */
    private function createGradedAssessmentForEnrollment(
        Enrollment $enrollment,
        ClassSubject $classSubject,
        float $score,
        float $maxPoints = 20.0,
        float $assessmentCoefficient = 1.0
    ): Assessment {
        $assessment = Assessment::factory()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'coefficient' => $assessmentCoefficient,
            'is_published' => true,
        ]);

        $question = Question::factory()->create([
            'assessment_id' => $assessment->id,
            'points' => $maxPoints,
        ]);

        $assignment = AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'submitted_at' => now()->subHour(),
            'graded_at' => now()->subMinutes(30),
        ]);

        Answer::factory()->create([
            'assessment_assignment_id' => $assignment->id,
            'question_id' => $question->id,
            'score' => $score,
        ]);

        return $assessment;
    }
}
