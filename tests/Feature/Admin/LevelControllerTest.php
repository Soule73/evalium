<?php

namespace Tests\Feature\Admin;

use App\Models\ClassModel;
use App\Models\Level;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class LevelControllerTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    private Level $level;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->admin = $this->createAdmin();
        $this->teacher = $this->createTeacher();
        $this->student = $this->createStudent();

        $this->level = Level::factory()->create();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Licence '.rand(1, 9),
            'code' => 'L'.rand(100, 999),
            'description' => 'Description test',
            'order' => 1,
            'is_active' => true,
        ], $overrides);
    }

    // ---------------------------------------------------------------
    // Index
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_index(): void
    {
        $this->get(route('admin.levels.index'))->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_index(): void
    {
        $this->actingAs($this->student)->get(route('admin.levels.index'))->assertForbidden();
    }

    public function test_teacher_cannot_access_index(): void
    {
        $this->actingAs($this->teacher)->get(route('admin.levels.index'))->assertForbidden();
    }

    public function test_admin_can_access_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.levels.index'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Admin/Levels/Index')
                    ->has('levels')
                    ->has('filters')
            );
    }

    // ---------------------------------------------------------------
    // Create
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_create(): void
    {
        $this->get(route('admin.levels.create'))->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_create(): void
    {
        $this->actingAs($this->student)->get(route('admin.levels.create'))->assertForbidden();
    }

    public function test_teacher_cannot_access_create(): void
    {
        $this->actingAs($this->teacher)->get(route('admin.levels.create'))->assertForbidden();
    }

    public function test_admin_can_access_create(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.levels.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Admin/Levels/Create'));
    }

    // ---------------------------------------------------------------
    // Store
    // ---------------------------------------------------------------

    public function test_guest_cannot_store(): void
    {
        $this->post(route('admin.levels.store'), $this->validPayload())->assertRedirect(route('login'));
    }

    public function test_student_cannot_store(): void
    {
        $this->actingAs($this->student)
            ->post(route('admin.levels.store'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_teacher_cannot_store(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('admin.levels.store'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_admin_can_create_level(): void
    {
        $payload = $this->validPayload(['name' => 'Licence 1', 'code' => 'LIC001']);

        $this->actingAs($this->admin)
            ->post(route('admin.levels.store'), $payload)
            ->assertRedirect(route('admin.levels.index'));

        $this->assertDatabaseHas('levels', [
            'name' => 'Licence 1',
            'code' => 'LIC001',
        ]);
    }

    public function test_store_requires_name_and_code(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.levels.store'), ['order' => 1])
            ->assertSessionHasErrors(['name', 'code']);
    }

    public function test_store_requires_unique_code(): void
    {
        $payload = $this->validPayload(['code' => $this->level->code]);

        $this->actingAs($this->admin)
            ->post(route('admin.levels.store'), $payload)
            ->assertSessionHasErrors('code');
    }

    public function test_store_requires_unique_name(): void
    {
        $payload = $this->validPayload(['name' => $this->level->name]);

        $this->actingAs($this->admin)
            ->post(route('admin.levels.store'), $payload)
            ->assertSessionHasErrors('name');
    }

    // ---------------------------------------------------------------
    // Edit
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_edit(): void
    {
        $this->get(route('admin.levels.edit', $this->level))->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_edit(): void
    {
        $this->actingAs($this->student)->get(route('admin.levels.edit', $this->level))->assertForbidden();
    }

    public function test_teacher_cannot_access_edit(): void
    {
        $this->actingAs($this->teacher)->get(route('admin.levels.edit', $this->level))->assertForbidden();
    }

    public function test_admin_can_access_edit(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.levels.edit', $this->level))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Admin/Levels/Edit')
                    ->has('level')
            );
    }

    // ---------------------------------------------------------------
    // Update
    // ---------------------------------------------------------------

    public function test_guest_cannot_update(): void
    {
        $this->put(route('admin.levels.update', $this->level), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    public function test_student_cannot_update(): void
    {
        $this->actingAs($this->student)
            ->put(route('admin.levels.update', $this->level), $this->validPayload())
            ->assertForbidden();
    }

    public function test_teacher_cannot_update(): void
    {
        $this->actingAs($this->teacher)
            ->put(route('admin.levels.update', $this->level), $this->validPayload())
            ->assertForbidden();
    }

    public function test_admin_can_update_level(): void
    {
        $payload = $this->validPayload(['name' => 'Nom modifié', 'code' => 'MOD001']);

        $this->actingAs($this->admin)
            ->put(route('admin.levels.update', $this->level), $payload)
            ->assertRedirect(route('admin.levels.index'));

        $this->assertDatabaseHas('levels', [
            'id' => $this->level->id,
            'name' => 'Nom modifié',
            'code' => 'MOD001',
        ]);
    }

    public function test_update_code_and_name_unique_ignores_self(): void
    {
        $payload = $this->validPayload([
            'name' => $this->level->name,
            'code' => $this->level->code,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.levels.update', $this->level), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('levels', [
            'id' => $this->level->id,
            'code' => $this->level->code,
        ]);
    }

    // ---------------------------------------------------------------
    // Destroy
    // ---------------------------------------------------------------

    public function test_guest_cannot_delete(): void
    {
        $this->delete(route('admin.levels.destroy', $this->level))->assertRedirect(route('login'));
    }

    public function test_student_cannot_delete(): void
    {
        $this->actingAs($this->student)
            ->delete(route('admin.levels.destroy', $this->level))
            ->assertForbidden();
    }

    public function test_teacher_cannot_delete(): void
    {
        $this->actingAs($this->teacher)
            ->delete(route('admin.levels.destroy', $this->level))
            ->assertForbidden();
    }

    public function test_admin_can_delete_level_without_classes(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('admin.levels.destroy', $this->level))
            ->assertRedirect(route('admin.levels.index'));

        $this->assertDatabaseMissing('levels', ['id' => $this->level->id]);
    }

    public function test_admin_cannot_delete_level_with_classes(): void
    {
        ClassModel::factory()->create(['level_id' => $this->level->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.levels.destroy', $this->level))
            ->assertRedirect();

        $this->assertDatabaseHas('levels', ['id' => $this->level->id]);
    }

    // ---------------------------------------------------------------
    // Toggle Status
    // ---------------------------------------------------------------

    public function test_guest_cannot_toggle_status(): void
    {
        $this->patch(route('admin.levels.toggle-status', $this->level))->assertRedirect(route('login'));
    }

    public function test_student_cannot_toggle_status(): void
    {
        $this->actingAs($this->student)
            ->patch(route('admin.levels.toggle-status', $this->level))
            ->assertForbidden();
    }

    public function test_teacher_cannot_toggle_status(): void
    {
        $this->actingAs($this->teacher)
            ->patch(route('admin.levels.toggle-status', $this->level))
            ->assertForbidden();
    }

    public function test_admin_can_deactivate_active_level(): void
    {
        $level = Level::factory()->create(['is_active' => true]);

        $this->actingAs($this->admin)
            ->patch(route('admin.levels.toggle-status', $level))
            ->assertRedirect();

        $this->assertDatabaseHas('levels', ['id' => $level->id, 'is_active' => false]);
    }

    public function test_admin_can_activate_inactive_level(): void
    {
        $level = Level::factory()->create(['is_active' => false]);

        $this->actingAs($this->admin)
            ->patch(route('admin.levels.toggle-status', $level))
            ->assertRedirect();

        $this->assertDatabaseHas('levels', ['id' => $level->id, 'is_active' => true]);
    }
}
