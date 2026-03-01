<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class ClassSubjectStoreTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    private ClassModel $class;

    private Subject $subject;

    private Semester $semester;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->admin = $this->createAdmin();
        $this->teacher = $this->createTeacher();
        $this->student = $this->createStudent();

        $academicYear = AcademicYear::factory()->create(['is_current' => true]);
        $this->class = ClassModel::factory()->create(['academic_year_id' => $academicYear->id]);
        $this->subject = Subject::factory()->create(['level_id' => $this->class->level_id]);
        $this->semester = Semester::factory()->create(['academic_year_id' => $academicYear->id]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'coefficient' => 2,
        ], $overrides);
    }

    public function test_guest_cannot_create_class_subject(): void
    {
        $response = $this->post(route('admin.class-subjects.store'), $this->validPayload());

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_create_class_subject(): void
    {
        $response = $this->actingAs($this->student)
            ->post(route('admin.class-subjects.store'), $this->validPayload());

        $response->assertForbidden();
    }

    public function test_teacher_cannot_create_class_subject(): void
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('admin.class-subjects.store'), $this->validPayload());

        $response->assertForbidden();
    }

    public function test_admin_can_create_class_subject_with_teacher(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.class-subjects.store'),
            $this->validPayload(['teacher_id' => $this->teacher->id]),
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('class_subjects', [
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'coefficient' => 2,
        ]);
    }

    public function test_admin_can_create_class_subject_without_teacher(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.class-subjects.store'),
            $this->validPayload(),
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('class_subjects', [
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => null,
            'coefficient' => 2,
        ]);
    }

    public function test_admin_can_create_class_subject_with_semester(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.class-subjects.store'),
            $this->validPayload(['semester_id' => $this->semester->id]),
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('class_subjects', [
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'semester_id' => $this->semester->id,
        ]);
    }

    public function test_admin_is_redirected_to_class_show_when_redirect_to_provided(): void
    {
        $classShowPath = parse_url(route('admin.classes.show', $this->class->id), PHP_URL_PATH);

        $response = $this->actingAs($this->admin)->post(
            route('admin.class-subjects.store'),
            $this->validPayload(['redirect_to' => $classShowPath]),
        );

        $response->assertRedirect($classShowPath);
    }

    public function test_admin_is_redirected_to_class_subject_show_by_default(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.class-subjects.store'),
            $this->validPayload(),
        );

        $classSubject = ClassSubject::latest()->first();
        $response->assertRedirect(route('admin.classes.subjects.show', [
            'class' => $classSubject->class_id,
            'class_subject' => $classSubject->id,
        ]));
    }

    public function test_creation_requires_class_id(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.class-subjects.store'),
            ['subject_id' => $this->subject->id, 'coefficient' => 2],
        );

        $response->assertSessionHasErrors('class_id');
    }

    public function test_creation_requires_subject_id(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.class-subjects.store'),
            ['class_id' => $this->class->id, 'coefficient' => 2],
        );

        $response->assertSessionHasErrors('subject_id');
    }

    public function test_creation_requires_positive_coefficient(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.class-subjects.store'),
            $this->validPayload(['coefficient' => -1]),
        );

        $response->assertSessionHasErrors('coefficient');
    }

    public function test_teacher_id_must_be_valid_user_when_provided(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.class-subjects.store'),
            $this->validPayload(['teacher_id' => 99999]),
        );

        $response->assertSessionHasErrors('teacher_id');
    }

    public function test_admin_cannot_create_duplicate_active_assignment(): void
    {
        $this->actingAs($this->admin)->post(
            route('admin.class-subjects.store'),
            $this->validPayload(),
        );

        $response = $this->actingAs($this->admin)->post(
            route('admin.class-subjects.store'),
            $this->validPayload(),
        );

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('class_subjects', 1);
    }
}
