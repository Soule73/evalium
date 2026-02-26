<?php

namespace Tests\Feature\Admin;

use App\Models\ClassSubject;
use App\Models\Level;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class SubjectControllerTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->admin = $this->createAdmin();
        $this->teacher = $this->createTeacher();
        $this->student = $this->createStudent();

        $this->subject = Subject::factory()->create();
    }

    private function validPayload(array $overrides = []): array
    {
        $level = Level::factory()->create();

        return array_merge([
            'level_id' => $level->id,
            'name' => 'Mathématiques avancées',
            'code' => 'MATH-' . rand(100, 999),
            'description' => null,
        ], $overrides);
    }

    // ---------------------------------------------------------------
    // Index
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_index(): void
    {
        $this->get(route('admin.subjects.index'))->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_index(): void
    {
        $this->actingAs($this->student)->get(route('admin.subjects.index'))->assertForbidden();
    }

    public function test_teacher_cannot_access_index(): void
    {
        $this->actingAs($this->teacher)->get(route('admin.subjects.index'))->assertForbidden();
    }

    public function test_admin_can_access_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.subjects.index'))
            ->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->component('Admin/Subjects/Index')
                    ->has('subjects')
                    ->has('levels')
                    ->has('filters')
            );
    }

    // ---------------------------------------------------------------
    // Create
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_create(): void
    {
        $this->get(route('admin.subjects.create'))->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_create(): void
    {
        $this->actingAs($this->student)->get(route('admin.subjects.create'))->assertForbidden();
    }

    public function test_admin_can_access_create(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.subjects.create'))
            ->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->component('Admin/Subjects/Create')
                    ->has('levels')
            );
    }

    // ---------------------------------------------------------------
    // Store
    // ---------------------------------------------------------------

    public function test_guest_cannot_store(): void
    {
        $this->post(route('admin.subjects.store'), $this->validPayload())->assertRedirect(route('login'));
    }

    public function test_student_cannot_store(): void
    {
        $this->actingAs($this->student)
            ->post(route('admin.subjects.store'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_teacher_cannot_store(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('admin.subjects.store'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_admin_can_create_subject(): void
    {
        $payload = $this->validPayload();

        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), $payload)
            ->assertRedirect(route('admin.subjects.index'));

        $this->assertDatabaseHas('subjects', [
            'name' => $payload['name'],
            'code' => $payload['code'],
        ]);
    }

    public function test_store_requires_name_and_code(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), ['level_id' => Level::factory()->create()->id])
            ->assertSessionHasErrors(['name', 'code']);
    }

    public function test_store_requires_unique_code(): void
    {
        $payload = $this->validPayload(['code' => $this->subject->code]);

        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), $payload)
            ->assertSessionHasErrors('code');
    }

    public function test_store_rejects_duplicate_name_within_same_level(): void
    {
        $level = Level::factory()->create();
        Subject::factory()->create(['level_id' => $level->id, 'name' => 'Mathématiques']);

        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), [
                'level_id' => $level->id,
                'name' => 'Mathématiques',
                'code' => 'MATH-' . rand(100, 999),
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_store_allows_same_name_in_different_levels(): void
    {
        $levelA = Level::factory()->create();
        $levelB = Level::factory()->create();
        Subject::factory()->create(['level_id' => $levelA->id, 'name' => 'Mathématiques']);

        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), [
                'level_id' => $levelB->id,
                'name' => 'Mathématiques',
                'code' => 'MATH-' . rand(100, 999),
            ])
            ->assertSessionHasNoErrors();
    }

    // ---------------------------------------------------------------
    // Show
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_show(): void
    {
        $this->get(route('admin.subjects.show', $this->subject))->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_show(): void
    {
        $this->actingAs($this->student)
            ->get(route('admin.subjects.show', $this->subject))
            ->assertForbidden();
    }

    public function test_admin_can_view_subject(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.subjects.show', $this->subject))
            ->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->component('Admin/Subjects/Show')
                    ->has('subject')
                    ->has('classSubjects')
            );
    }

    // ---------------------------------------------------------------
    // Edit
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_edit(): void
    {
        $this->get(route('admin.subjects.edit', $this->subject))->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_edit(): void
    {
        $this->actingAs($this->student)
            ->get(route('admin.subjects.edit', $this->subject))
            ->assertForbidden();
    }

    public function test_admin_can_access_edit(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.subjects.edit', $this->subject))
            ->assertOk()
            ->assertInertia(
                fn($page) => $page
                    ->component('Admin/Subjects/Edit')
                    ->has('subject')
                    ->has('levels')
            );
    }

    // ---------------------------------------------------------------
    // Update
    // ---------------------------------------------------------------

    public function test_guest_cannot_update(): void
    {
        $this->put(route('admin.subjects.update', $this->subject), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    public function test_student_cannot_update(): void
    {
        $this->actingAs($this->student)
            ->put(route('admin.subjects.update', $this->subject), $this->validPayload())
            ->assertForbidden();
    }

    public function test_teacher_cannot_update(): void
    {
        $this->actingAs($this->teacher)
            ->put(route('admin.subjects.update', $this->subject), $this->validPayload())
            ->assertForbidden();
    }

    public function test_admin_can_update_subject(): void
    {
        $payload = $this->validPayload(['name' => 'Nom modifié']);

        $this->actingAs($this->admin)
            ->put(route('admin.subjects.update', $this->subject), $payload)
            ->assertRedirect(route('admin.subjects.show', $this->subject));

        $this->assertDatabaseHas('subjects', ['id' => $this->subject->id, 'name' => 'Nom modifié']);
    }

    public function test_update_code_unique_ignores_self(): void
    {
        $payload = $this->validPayload(['code' => $this->subject->code]);

        $this->actingAs($this->admin)
            ->put(route('admin.subjects.update', $this->subject), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('subjects', ['id' => $this->subject->id, 'code' => $this->subject->code]);
    }

    public function test_update_rejects_duplicate_name_within_same_level(): void
    {
        $level = Level::factory()->create();
        Subject::factory()->create(['level_id' => $level->id, 'name' => 'Mathématiques']);
        $subjectToUpdate = Subject::factory()->create(['level_id' => $level->id, 'name' => 'Physique']);

        $this->actingAs($this->admin)
            ->put(route('admin.subjects.update', $subjectToUpdate), [
                'level_id' => $level->id,
                'name' => 'Mathématiques',
                'code' => 'PHYS-' . rand(100, 999),
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_update_name_unique_ignores_self(): void
    {
        $level = Level::factory()->create();
        $subjectToUpdate = Subject::factory()->create(['level_id' => $level->id, 'name' => 'Mathématiques']);

        $this->actingAs($this->admin)
            ->put(route('admin.subjects.update', $subjectToUpdate), [
                'level_id' => $level->id,
                'name' => 'Mathématiques',
                'code' => $subjectToUpdate->code,
            ])
            ->assertRedirect();
    }

    // ---------------------------------------------------------------
    // Destroy
    // ---------------------------------------------------------------

    public function test_guest_cannot_delete(): void
    {
        $this->delete(route('admin.subjects.destroy', $this->subject))->assertRedirect(route('login'));
    }

    public function test_student_cannot_delete(): void
    {
        $this->actingAs($this->student)
            ->delete(route('admin.subjects.destroy', $this->subject))
            ->assertForbidden();
    }

    public function test_admin_can_delete_subject_without_assignments(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('admin.subjects.destroy', $this->subject))
            ->assertRedirect(route('admin.subjects.index'));

        $this->assertDatabaseMissing('subjects', ['id' => $this->subject->id]);
    }

    public function test_admin_cannot_delete_subject_with_class_assignments(): void
    {
        ClassSubject::factory()->create(['subject_id' => $this->subject->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.subjects.destroy', $this->subject))
            ->assertRedirect();

        $this->assertDatabaseHas('subjects', ['id' => $this->subject->id]);
    }
}
