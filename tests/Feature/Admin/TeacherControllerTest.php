<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\ClassSubject;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class TeacherControllerTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->admin = $this->createAdmin();
        $this->teacher = $this->createTeacher();
        $this->student = $this->createStudent();
    }

    // ---------------------------------------------------------------
    // Index
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_teachers_index(): void
    {
        $response = $this->get(route('admin.teachers.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_teachers_index(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('admin.teachers.index'));

        $response->assertForbidden();
    }

    public function test_teacher_cannot_access_teachers_index(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('admin.teachers.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_teachers_index(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.teachers.index'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/Teachers/Index')
                ->has('teachers')
        );
    }

    public function test_teachers_index_only_returns_teachers(): void
    {
        $extraTeacher = $this->createTeacher();
        $extraAdmin = $this->createAdmin();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.teachers.index'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/Teachers/Index')
                ->where('teachers.data', function ($data) use ($extraAdmin) {
                    $items = is_array($data) ? $data : $data->toArray();
                    foreach ($items as $user) {
                        if (($user['id'] ?? null) === $extraAdmin->id) {
                            return false;
                        }
                    }

                    return true;
                })
        );
    }

    public function test_teachers_index_supports_search_filter(): void
    {
        $targetTeacher = $this->createTeacher(['name' => 'Unique Teacher Name XYZ']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.teachers.index', ['search' => 'Unique Teacher Name XYZ']));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/Teachers/Index')
                ->where('teachers.data.0.id', $targetTeacher->id)
        );
    }

    // ---------------------------------------------------------------
    // Show
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_teacher_show(): void
    {
        $response = $this->get(route('admin.teachers.show', $this->teacher));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_teacher_show(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('admin.teachers.show', $this->teacher));

        $response->assertForbidden();
    }

    public function test_teacher_cannot_access_teacher_show(): void
    {
        $anotherTeacher = $this->createTeacher();

        $response = $this->actingAs($this->teacher)
            ->get(route('admin.teachers.show', $anotherTeacher));

        $response->assertForbidden();
    }

    public function test_admin_can_view_teacher_show(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.teachers.show', $this->teacher));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/Teachers/Show')
                ->has('user')
                ->has('assessments')
                ->has('stats')
        );
    }

    public function test_admin_cannot_view_non_teacher_via_teacher_show(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.teachers.show', $admin));

        $response->assertRedirect();
    }

    // ---------------------------------------------------------------
    // Store
    // ---------------------------------------------------------------

    public function test_guest_cannot_create_teacher(): void
    {
        $response = $this->post(route('admin.teachers.store'), [
            'name' => 'New Teacher',
            'email' => 'newteacher@example.com',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_create_teacher(): void
    {
        $response = $this->actingAs($this->student)
            ->post(route('admin.teachers.store'), [
                'name' => 'New Teacher',
                'email' => 'newteacher@example.com',
            ]);

        $response->assertForbidden();
    }

    public function test_admin_can_create_teacher(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.teachers.store'), [
                'name' => 'New Teacher',
                'email' => 'newteacher@example.com',
                'role' => 'teacher',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('has_new_user', true);
        $response->assertSessionHas('new_user_credentials');

        $this->assertDatabaseHas('users', ['email' => 'newteacher@example.com']);

        $createdUser = User::where('email', 'newteacher@example.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertTrue($createdUser->hasRole('teacher'));
    }

    public function test_admin_cannot_create_teacher_with_invalid_data(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.teachers.store'), [
                'name' => '',
                'email' => 'not-an-email',
            ]);

        $response->assertSessionHasErrors(['name', 'email']);
    }

    public function test_admin_cannot_create_teacher_with_duplicate_email(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.teachers.store'), [
                'name' => 'Duplicate',
                'email' => $this->teacher->email,
            ]);

        $response->assertSessionHasErrors(['email']);
    }

    // ---------------------------------------------------------------
    // Toggle Status
    // ---------------------------------------------------------------

    private function createTeacherWithCurrentYearAssignment(): User
    {
        $teacher = $this->createTeacher();
        $academicYear = AcademicYear::factory()->current()->create();
        $semester = Semester::factory()->create(['academic_year_id' => $academicYear->id]);
        ClassSubject::factory()->create([
            'teacher_id' => $teacher->id,
            'semester_id' => $semester->id,
        ]);

        return $teacher;
    }

    public function test_admin_can_toggle_teacher_status_when_not_teaching(): void
    {
        $initialStatus = $this->teacher->is_active;

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.teachers.toggle-status', $this->teacher));

        $response->assertRedirect();
        $this->teacher->refresh();
        $this->assertNotEquals($initialStatus, $this->teacher->is_active);
    }

    public function test_admin_cannot_toggle_teacher_status_when_teaching_in_current_year(): void
    {
        $teacher = $this->createTeacherWithCurrentYearAssignment();

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.teachers.toggle-status', $teacher));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_student_cannot_toggle_teacher_status(): void
    {
        $response = $this->actingAs($this->student)
            ->patch(route('admin.teachers.toggle-status', $this->teacher));

        $response->assertForbidden();
    }

    public function test_guest_cannot_toggle_teacher_status(): void
    {
        $response = $this->patch(route('admin.teachers.toggle-status', $this->teacher));

        $response->assertRedirect(route('login'));
    }

    // ---------------------------------------------------------------
    // Destroy
    // ---------------------------------------------------------------

    public function test_super_admin_can_delete_teacher_not_teaching(): void
    {
        $superAdmin = $this->createSuperAdmin();

        $response = $this->actingAs($superAdmin)
            ->delete(route('admin.teachers.destroy', $this->teacher));

        $response->assertRedirect(route('admin.teachers.index'));
        $this->assertSoftDeleted('users', ['id' => $this->teacher->id]);
    }

    public function test_super_admin_cannot_delete_teacher_teaching_in_current_year(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacherWithCurrentYearAssignment();

        $response = $this->actingAs($superAdmin)
            ->delete(route('admin.teachers.destroy', $teacher));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNotSoftDeleted('users', ['id' => $teacher->id]);
    }

    public function test_admin_cannot_delete_teacher(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.teachers.destroy', $this->teacher));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertNotSoftDeleted('users', ['id' => $this->teacher->id]);
    }

    public function test_student_cannot_delete_teacher(): void
    {
        $response = $this->actingAs($this->student)
            ->delete(route('admin.teachers.destroy', $this->teacher));

        $response->assertForbidden();
    }

    public function test_guest_cannot_delete_teacher(): void
    {
        $response = $this->delete(route('admin.teachers.destroy', $this->teacher));

        $response->assertRedirect(route('login'));
    }

    public function test_teacher_show_exposes_has_active_classes_flag(): void
    {
        $teacher = $this->createTeacherWithCurrentYearAssignment();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.teachers.show', $teacher));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/Teachers/Show')
                ->where('hasActiveClassesInCurrentYear', true)
                ->where('canDelete', false)
                ->where('canToggleStatus', false)
        );
    }
}
