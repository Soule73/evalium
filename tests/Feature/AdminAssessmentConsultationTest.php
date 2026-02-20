<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class AdminAssessmentConsultationTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    private AcademicYear $academicYear;

    private ClassModel $classModel;

    private ClassSubject $classSubject;

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
    }

    public function test_admin_can_view_assessments_index(): void
    {
        Assessment::factory()->count(3)->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.assessments.index'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/Assessments/Index')
                ->has('assessments.data', 3)
                ->has('classes')
                ->has('subjects')
                ->has('teachers')
        );
    }

    public function test_admin_can_filter_assessments_by_search(): void
    {
        Assessment::factory()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Mathematics Final Exam',
        ]);
        Assessment::factory()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Physics Quiz',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.assessments.index', ['search' => 'Mathematics']));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/Assessments/Index')
                ->has('assessments.data', 1)
        );
    }

    public function test_non_admin_cannot_access_assessments_index(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('admin.assessments.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_view_teacher_show_with_assessments(): void
    {
        Assessment::factory()->count(2)->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.show.teacher', $this->teacher));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/Users/ShowTeacher')
                ->has('assessments.data', 2)
                ->has('stats')
                ->where('stats.total', 2)
        );
    }

    public function test_admin_can_view_enrollment_show_with_grade_breakdown(): void
    {
        $enrollment = Enrollment::factory()->create([
            'class_id' => $this->classModel->id,
            'student_id' => $this->student->id,
        ]);

        $assessment = Assessment::factory()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        AssessmentAssignment::factory()->graded()->create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.classes.students.show', [
                'class' => $this->classModel->id,
                'enrollment' => $enrollment->id,
            ]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/Classes/Students/Show')
                ->has('enrollment')
                ->has('subjects')
                ->has('overallStats')
                ->where('overallStats.student_id', $this->student->id)
                ->where('overallStats.class_id', $this->classModel->id)
        );
    }

    public function test_admin_can_view_class_show_with_assessments(): void
    {
        Assessment::factory()->count(2)->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.classes.show', $this->classModel));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/Classes/Show')
                ->has('recentAssessments.data', 2)
                ->has('statistics')
        );
    }

    public function test_show_teacher_rejects_non_teacher_user(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.show.teacher', $this->student));

        $response->assertRedirect();
    }

    public function test_teacher_stats_are_accurate(): void
    {
        Assessment::factory()->count(3)->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
        ]);
        Assessment::factory()->published()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.show.teacher', $this->teacher));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->where('stats.total', 4)
                ->where('stats.published', 1)
                ->where('stats.unpublished', 3)
        );
    }

    public function test_grade_breakdown_uses_correct_formula_on_enrollment_show(): void
    {
        $enrollment = Enrollment::factory()->create([
            'class_id' => $this->classModel->id,
            'student_id' => $this->student->id,
        ]);

        $assessment = Assessment::factory()->published()->create([
            'class_subject_id' => $this->classSubject->id,
            'teacher_id' => $this->teacher->id,
            'coefficient' => 1,
        ]);

        $question = $assessment->questions()->create([
            'content' => 'Test question',
            'type' => 'text',
            'points' => 20,
            'order_index' => 1,
        ]);

        AssessmentAssignment::factory()->graded()->create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
            'score' => 15.0,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.classes.students.show', [
                'class' => $this->classModel->id,
                'enrollment' => $enrollment->id,
            ]));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->has('subjects.data', 1)
                ->where('subjects.data.0.average', fn ($value) => (float) $value === 15.0)
                ->where('overallStats.annual_average', fn ($value) => (float) $value === 15.0)
        );
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get(route('admin.assessments.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_users_index_excludes_students(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/Users/Index')
                ->has('users.data')
                ->where('users.data', fn ($users) => collect($users)->every(
                    fn ($user) => collect($user['roles'] ?? [])->doesntContain('name', 'student')
                ))
        );
    }
}
