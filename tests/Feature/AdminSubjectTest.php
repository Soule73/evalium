<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class AdminSubjectTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private User $admin;

    private User $student;

    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->admin = $this->createAdmin();
        $this->student = $this->createStudent();
        $this->subject = Subject::factory()->create();
    }

    public function test_admin_can_view_subjects_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.subjects.index'))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->component('Subjects/Index')
                    ->has('subjects')
                    ->has('levels')
                    ->where('routeContext.role', 'admin')
                    ->where('routeContext.editRoute', 'admin.subjects.edit')
            );
    }

    public function test_admin_can_view_subject_show(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.subjects.show', $this->subject))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->component('Subjects/Show')
                    ->has('subject')
                    ->has('classSubjects')
                    ->where('routeContext.role', 'admin')
                    ->where('routeContext.deleteRoute', 'admin.subjects.destroy')
            );
    }

    public function test_unauthenticated_is_redirected_to_login(): void
    {
        $this->get(route('admin.subjects.index'))
            ->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_admin_subjects(): void
    {
        $this->actingAs($this->student)
            ->get(route('admin.subjects.index'))
            ->assertStatus(403);
    }
}
