<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class AdminAssessmentShowTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    private AcademicYear $academicYear;

    private ClassModel $classModel;

    private ClassSubject $classSubject;

    private Assessment $assessment;

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
        $this->classModel = ClassModel::factory()->create(['academic_year_id' => $this->academicYear->id]);
        $semester = Semester::factory()->create(['academic_year_id' => $this->academicYear->id]);

        $subject = Subject::factory()->create();
        $this->classSubject = ClassSubject::factory()->create([
            'class_id' => $this->classModel->id,
            'semester_id' => $semester->id,
            'teacher_id' => $this->teacher->id,
            'subject_id' => $subject->id,
        ]);

        $this->assessment = Assessment::factory()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $this->enrollment = Enrollment::factory()->create([
            'class_id' => $this->classModel->id,
            'student_id' => $this->student->id,
        ]);
    }

    public function test_admin_can_view_assessment_show(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.classes.assessments.show', [
                'class' => $this->classModel,
                'assessment' => $this->assessment,
            ]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Assessments/Show')
                ->has('assessment')
                ->has('assignments')
                ->has('routeContext')
                ->where('routeContext.role', 'admin')
                ->where('routeContext.showRoute', null)
                ->where('routeContext.reviewRoute', 'admin.assessments.review')
                ->where('routeContext.gradeRoute', 'admin.assessments.grade')
                ->where('routeContext.editRoute', null)
                ->where('routeContext.publishRoute', null)
                ->where('routeContext.duplicateRoute', null)
        );
    }

    public function test_admin_show_returns_correct_route_context(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.classes.assessments.show', [
                'class' => $this->classModel,
                'assessment' => $this->assessment,
            ]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->where('routeContext.role', 'admin')
                ->where('routeContext.backRoute', 'admin.assessments.index')
                ->where('routeContext.saveGradeRoute', 'admin.assessments.saveGrade')
        );
    }

    public function test_teacher_cannot_access_admin_assessment_show(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('admin.classes.assessments.show', [
                'class' => $this->classModel,
                'assessment' => $this->assessment,
            ]));

        $response->assertForbidden();
    }

    public function test_student_cannot_access_admin_assessment_show(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('admin.classes.assessments.show', [
                'class' => $this->classModel,
                'assessment' => $this->assessment,
            ]));

        $response->assertForbidden();
    }

    public function test_admin_can_view_review(): void
    {
        $assignment = AssessmentAssignment::factory()->graded()->create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.assessments.review', [$this->assessment, $assignment]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Assessments/Review')
                ->has('assignment')
                ->has('assessment')
                ->has('student')
                ->has('userAnswers')
                ->has('routeContext')
                ->where('routeContext.role', 'admin')
        );
    }

    public function test_admin_can_view_grade(): void
    {
        $assignment = AssessmentAssignment::factory()->submitted()->create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.assessments.grade', [$this->assessment, $assignment]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Assessments/Grade')
                ->has('assignment')
                ->has('assessment')
                ->has('student')
                ->has('userAnswers')
                ->has('routeContext')
                ->where('routeContext.role', 'admin')
        );
    }

    public function test_admin_can_save_grade(): void
    {
        $question = Question::factory()->create([
            'assessment_id' => $this->assessment->id,
            'points' => 10,
        ]);

        $assignment = AssessmentAssignment::factory()->submitted()->create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.assessments.saveGrade', [$this->assessment, $assignment]), [
                'scores' => [
                    ['question_id' => $question->id, 'score' => 8, 'feedback' => 'Good work'],
                ],
                'teacher_notes' => 'Overall good performance',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_review_returns_404_for_mismatched_assignment(): void
    {
        $otherAssessment = Assessment::factory()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $assignment = AssessmentAssignment::factory()->graded()->create([
            'assessment_id' => $otherAssessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.assessments.review', [$this->assessment, $assignment]));

        $response->assertNotFound();
    }

    public function test_grade_returns_404_for_mismatched_assignment(): void
    {
        $otherAssessment = Assessment::factory()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $assignment = AssessmentAssignment::factory()->submitted()->create([
            'assessment_id' => $otherAssessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.assessments.grade', [$this->assessment, $assignment]));

        $response->assertNotFound();
    }

    public function test_student_cannot_access_admin_review(): void
    {
        $assignment = AssessmentAssignment::factory()->graded()->create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('admin.assessments.review', [$this->assessment, $assignment]));

        $response->assertForbidden();
    }

    public function test_student_cannot_access_admin_grade(): void
    {
        $assignment = AssessmentAssignment::factory()->submitted()->create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('admin.assessments.grade', [$this->assessment, $assignment]));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_redirected_from_admin_show(): void
    {
        $response = $this->get(route('admin.classes.assessments.show', [
            'class' => $this->classModel,
            'assessment' => $this->assessment,
        ]));

        $response->assertRedirect(route('login'));
    }

    public function test_super_admin_can_view_assessment_show(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)
            ->get(route('admin.classes.assessments.show', [
                'class' => $this->classModel,
                'assessment' => $this->assessment,
            ]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Assessments/Show')
                ->has('assessment')
                ->has('routeContext')
                ->where('routeContext.role', 'admin')
        );
    }

    public function test_super_admin_can_view_review(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $assignment = AssessmentAssignment::factory()->graded()->create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        $response = $this->actingAs($superAdmin)
            ->get(route('admin.assessments.review', [$this->assessment, $assignment]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Assessments/Review')
                ->where('routeContext.role', 'admin')
        );
    }

    public function test_super_admin_can_view_grade(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $assignment = AssessmentAssignment::factory()->submitted()->create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        $response = $this->actingAs($superAdmin)
            ->get(route('admin.assessments.grade', [$this->assessment, $assignment]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Assessments/Grade')
                ->where('routeContext.role', 'admin')
        );
    }

    public function test_super_admin_can_save_grade(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $question = Question::factory()->create([
            'assessment_id' => $this->assessment->id,
            'points' => 10,
        ]);

        $assignment = AssessmentAssignment::factory()->submitted()->create([
            'assessment_id' => $this->assessment->id,
            'enrollment_id' => $this->enrollment->id,
        ]);

        $response = $this->actingAs($superAdmin)
            ->post(route('admin.assessments.saveGrade', [$this->assessment, $assignment]), [
                'scores' => [
                    ['question_id' => $question->id, 'score' => 9, 'feedback' => 'Excellent'],
                ],
                'teacher_notes' => 'Great performance',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }
}
