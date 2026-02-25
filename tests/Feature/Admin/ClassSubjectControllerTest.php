<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class ClassSubjectControllerTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    private AcademicYear $academicYear;

    private ClassModel $class;

    private Subject $subject;

    private Semester $semester;

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
        $this->class = ClassModel::factory()->create(['academic_year_id' => $this->academicYear->id]);
        $this->subject = Subject::factory()->create(['level_id' => $this->class->level_id]);
        $this->semester = Semester::factory()->create(['academic_year_id' => $this->academicYear->id]);

        $this->classSubject = ClassSubject::factory()->create([
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'valid_to' => null,
        ]);
    }

    // ---------------------------------------------------------------
    // Index
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_index(): void
    {
        $response = $this->get(route('admin.class-subjects.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_index(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.class-subjects.index'));

        $response->assertForbidden();
    }

    public function test_teacher_cannot_access_index(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('admin.class-subjects.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.class-subjects.index'));

        $response->assertOk();
        $response->assertInertia(
            fn($page) => $page
                ->component('Admin/ClassSubjects/Index')
                ->has('classSubjects')
                ->has('filters')
                ->missing('formData')
        );
    }

    // ---------------------------------------------------------------
    // Replace Teacher
    // ---------------------------------------------------------------

    public function test_guest_cannot_replace_teacher(): void
    {
        $newTeacher = $this->createTeacher();

        $response = $this->post(route('admin.class-subjects.replace-teacher', $this->classSubject), [
            'new_teacher_id' => $newTeacher->id,
            'effective_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_replace_teacher(): void
    {
        $newTeacher = $this->createTeacher();

        $response = $this->actingAs($this->student)->post(
            route('admin.class-subjects.replace-teacher', $this->classSubject),
            ['new_teacher_id' => $newTeacher->id, 'effective_date' => now()->toDateString()]
        );

        $response->assertForbidden();
    }

    public function test_admin_can_replace_teacher(): void
    {
        $newTeacher = $this->createTeacher();
        $effectiveDate = now()->toDateString();

        $response = $this->actingAs($this->admin)->post(
            route('admin.class-subjects.replace-teacher', $this->classSubject),
            ['new_teacher_id' => $newTeacher->id, 'effective_date' => $effectiveDate]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('class_subjects', [
            'class_id' => $this->class->id,
            'subject_id' => $this->subject->id,
            'teacher_id' => $newTeacher->id,
            'valid_to' => null,
        ]);

        $this->classSubject->refresh();
        $this->assertNotNull($this->classSubject->valid_to);
    }

    public function test_replace_teacher_requires_different_teacher(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.class-subjects.replace-teacher', $this->classSubject),
            ['new_teacher_id' => $this->teacher->id, 'effective_date' => now()->toDateString()]
        );

        $response->assertSessionHasErrors('new_teacher_id');
    }

    public function test_replace_teacher_requires_effective_date(): void
    {
        $newTeacher = $this->createTeacher();

        $response = $this->actingAs($this->admin)->post(
            route('admin.class-subjects.replace-teacher', $this->classSubject),
            ['new_teacher_id' => $newTeacher->id]
        );

        $response->assertSessionHasErrors('effective_date');
    }

    // ---------------------------------------------------------------
    // Update Coefficient
    // ---------------------------------------------------------------

    public function test_guest_cannot_update_coefficient(): void
    {
        $response = $this->post(
            route('admin.class-subjects.update-coefficient', $this->classSubject),
            ['coefficient' => 3]
        );

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_update_coefficient(): void
    {
        $response = $this->actingAs($this->student)->post(
            route('admin.class-subjects.update-coefficient', $this->classSubject),
            ['coefficient' => 3]
        );

        $response->assertForbidden();
    }

    public function test_admin_can_update_coefficient(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.class-subjects.update-coefficient', $this->classSubject),
            ['coefficient' => 4.5]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('class_subjects', [
            'id' => $this->classSubject->id,
            'coefficient' => 4.5,
        ]);
    }

    public function test_update_coefficient_rejects_zero_or_negative(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.class-subjects.update-coefficient', $this->classSubject),
            ['coefficient' => 0]
        );

        $response->assertSessionHasErrors('coefficient');
    }

    // ---------------------------------------------------------------
    // Terminate
    // ---------------------------------------------------------------

    public function test_guest_cannot_terminate_assignment(): void
    {
        $response = $this->post(
            route('admin.class-subjects.terminate', $this->classSubject),
            ['end_date' => now()->toDateString()]
        );

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_terminate_assignment(): void
    {
        $response = $this->actingAs($this->student)->post(
            route('admin.class-subjects.terminate', $this->classSubject),
            ['end_date' => now()->toDateString()]
        );

        $response->assertForbidden();
    }

    public function test_admin_can_terminate_assignment(): void
    {
        $endDate = now()->toDateString();

        $response = $this->actingAs($this->admin)->post(
            route('admin.class-subjects.terminate', $this->classSubject),
            ['end_date' => $endDate]
        );

        $response->assertRedirect();
        $this->classSubject->refresh();
        $this->assertNotNull($this->classSubject->valid_to);
        $this->assertEquals($endDate, $this->classSubject->valid_to->toDateString());
    }

    public function test_terminate_requires_end_date(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.class-subjects.terminate', $this->classSubject),
            []
        );

        $response->assertSessionHasErrors('end_date');
    }

    // ---------------------------------------------------------------
    // Destroy
    // ---------------------------------------------------------------

    public function test_guest_cannot_delete_class_subject(): void
    {
        $response = $this->delete(route('admin.class-subjects.destroy', $this->classSubject));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_delete_class_subject(): void
    {
        $response = $this->actingAs($this->student)->delete(
            route('admin.class-subjects.destroy', $this->classSubject)
        );

        $response->assertForbidden();
    }

    public function test_admin_can_delete_class_subject_without_assessments(): void
    {
        $response = $this->actingAs($this->admin)->delete(
            route('admin.class-subjects.destroy', $this->classSubject)
        );

        $response->assertRedirect(route('admin.class-subjects.index'));
        $this->assertDatabaseMissing('class_subjects', ['id' => $this->classSubject->id]);
    }

    public function test_admin_cannot_delete_class_subject_with_assessments(): void
    {
        Assessment::factory()->create(['class_subject_id' => $this->classSubject->id]);

        $response = $this->actingAs($this->admin)->delete(
            route('admin.class-subjects.destroy', $this->classSubject)
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('class_subjects', ['id' => $this->classSubject->id]);
    }
}
