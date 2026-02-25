<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class ClassControllerTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    private AcademicYear $academicYear;

    private Level $level;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->admin = $this->createAdmin();
        $this->teacher = $this->createTeacher();
        $this->student = $this->createStudent();

        $this->academicYear = AcademicYear::factory()->create(['is_current' => true]);
        $this->level = Level::factory()->create();
    }

    // ---------------------------------------------------------------
    // Index
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_index(): void
    {
        $response = $this->get(route('admin.classes.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_index(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.classes.index'));

        $response->assertForbidden();
    }

    public function test_teacher_can_access_index(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('admin.classes.index'));

        $response->assertOk();
    }

    public function test_admin_can_access_index(): void
    {
        ClassModel::factory()->create(['academic_year_id' => $this->academicYear->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.classes.index'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Classes/Index')
                ->has('classes')
                ->has('levels')
                ->has('routeContext')
        );
    }

    // ---------------------------------------------------------------
    // Create
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_create(): void
    {
        $response = $this->get(route('admin.classes.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_create(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.classes.create'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_create(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.classes.create'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page->component('Admin/Classes/Create')->has('levels')
        );
    }

    // ---------------------------------------------------------------
    // Store
    // ---------------------------------------------------------------

    public function test_guest_cannot_store(): void
    {
        $response = $this->post(route('admin.classes.store'), $this->validPayload());

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_store(): void
    {
        $response = $this->actingAs($this->student)
            ->post(route('admin.classes.store'), $this->validPayload());

        $response->assertForbidden();
    }

    public function test_teacher_cannot_store(): void
    {
        $response = $this->actingAs($this->teacher)
            ->post(route('admin.classes.store'), $this->validPayload());

        $response->assertForbidden();
    }

    public function test_admin_can_store_class(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.classes.store'), $this->validPayload());

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('classes', [
            'name' => 'G1',
            'level_id' => $this->level->id,
            'academic_year_id' => $this->academicYear->id,
        ]);
    }

    public function test_store_requires_name(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.classes.store'), array_merge($this->validPayload(), ['name' => '']));

        $response->assertSessionHasErrors('name');
    }

    public function test_store_requires_level_id(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.classes.store'), array_merge($this->validPayload(), ['level_id' => '']));

        $response->assertSessionHasErrors('level_id');
    }

    // ---------------------------------------------------------------
    // Show
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_show(): void
    {
        $class = ClassModel::factory()->create(['academic_year_id' => $this->academicYear->id]);

        $response = $this->get(route('admin.classes.show', $class));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_show(): void
    {
        $class = ClassModel::factory()->create(['academic_year_id' => $this->academicYear->id]);

        $response = $this->actingAs($this->student)->get(route('admin.classes.show', $class));

        $response->assertForbidden();
    }

    public function test_admin_can_access_show(): void
    {
        $class = ClassModel::factory()->create(['academic_year_id' => $this->academicYear->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.classes.show', $class));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Classes/Show')
                ->has('class')
                ->has('classSubjects')
                ->has('statistics')
                ->has('routeContext')
        );
    }

    // ---------------------------------------------------------------
    // Edit
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_edit(): void
    {
        $class = ClassModel::factory()->create();

        $response = $this->get(route('admin.classes.edit', $class));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_edit(): void
    {
        $class = ClassModel::factory()->create();

        $response = $this->actingAs($this->student)->get(route('admin.classes.edit', $class));

        $response->assertForbidden();
    }

    public function test_admin_can_access_edit(): void
    {
        $class = ClassModel::factory()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.classes.edit', $class));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/Classes/Edit')
                ->has('class')
                ->has('levels')
        );
    }

    // ---------------------------------------------------------------
    // Update
    // ---------------------------------------------------------------

    public function test_guest_cannot_update(): void
    {
        $class = ClassModel::factory()->create();

        $response = $this->put(route('admin.classes.update', $class), ['name' => 'G2']);

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_update(): void
    {
        $class = ClassModel::factory()->create();

        $response = $this->actingAs($this->student)->put(
            route('admin.classes.update', $class),
            $this->validPayload(['name' => 'G2', 'level_id' => $class->level_id])
        );

        $response->assertForbidden();
    }

    public function test_admin_can_update_class(): void
    {
        $class = ClassModel::factory()->create();

        $response = $this->actingAs($this->admin)->put(route('admin.classes.update', $class), [
            'name' => 'UpdatedName',
            'level_id' => $class->level_id,
            'max_students' => 25,
        ]);

        $response->assertRedirect(route('admin.classes.show', $class));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('classes', [
            'id' => $class->id,
            'name' => 'UpdatedName',
            'max_students' => 25,
        ]);
    }

    public function test_update_requires_name(): void
    {
        $class = ClassModel::factory()->create();

        $response = $this->actingAs($this->admin)
            ->put(route('admin.classes.update', $class), [
                'name' => '',
                'level_id' => $class->level_id,
            ]);

        $response->assertSessionHasErrors('name');
    }

    // ---------------------------------------------------------------
    // Destroy
    // ---------------------------------------------------------------

    public function test_guest_cannot_destroy(): void
    {
        $class = ClassModel::factory()->create();

        $response = $this->delete(route('admin.classes.destroy', $class));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_destroy(): void
    {
        $class = ClassModel::factory()->create();

        $response = $this->actingAs($this->student)->delete(route('admin.classes.destroy', $class));

        $response->assertForbidden();
    }

    public function test_admin_can_destroy_empty_class(): void
    {
        $class = ClassModel::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('admin.classes.destroy', $class));

        $response->assertRedirect(route('admin.classes.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('classes', ['id' => $class->id]);
    }

    public function test_cannot_destroy_class_with_enrolled_students(): void
    {
        $class = ClassModel::factory()->create();
        $student = User::factory()->create();
        Enrollment::factory()->create(['class_id' => $class->id, 'student_id' => $student->id]);

        $response = $this->actingAs($this->admin)->delete(route('admin.classes.destroy', $class));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('classes', ['id' => $class->id]);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'G1',
            'level_id' => $this->level->id,
            'max_students' => 30,
        ], $overrides);
    }
}
