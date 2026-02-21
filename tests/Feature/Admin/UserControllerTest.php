<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Notifications\UserCredentialsNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class UserControllerTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private User $superAdmin;

    private User $admin;

    private User $teacher;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->superAdmin = $this->createSuperAdmin();
        $this->admin = $this->createAdmin();
        $this->teacher = $this->createTeacher();
        $this->student = $this->createStudent();
    }

    // ---------------------------------------------------------------
    // Show admin user
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_admin_show(): void
    {
        $response = $this->get(route('admin.users.show', $this->admin));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_admin_show(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('admin.users.show', $this->admin));

        $response->assertForbidden();
    }

    public function test_teacher_cannot_access_admin_show(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('admin.users.show', $this->admin));

        $response->assertForbidden();
    }

    public function test_admin_can_view_admin_show_page(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.show', $this->admin));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/Users/ShowAdmin')
                ->has('user')
                ->where('canDelete', false)
                ->where('canToggleStatus', true)
        );
    }

    public function test_super_admin_can_view_admin_show_page_with_delete_permission(): void
    {
        $targetAdmin = $this->createAdmin();

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.users.show', $targetAdmin));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/Users/ShowAdmin')
                ->where('canDelete', true)
        );
    }

    public function test_admin_cannot_view_teacher_via_admin_show(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.show', $this->teacher));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_admin_cannot_view_student_via_admin_show(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.show', $this->student));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ---------------------------------------------------------------
    // Store user
    // ---------------------------------------------------------------

    public function test_admin_can_create_user_and_receives_credentials_in_flash(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Student',
                'email' => 'newstudent@example.com',
                'role' => 'student',
                'send_credentials' => false,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionHas('has_new_user', true);
        $response->assertSessionHas('new_user_credentials');

        $credentials = $response->getSession()->get('new_user_credentials');
        $this->assertEquals('New Student', $credentials['name']);
        $this->assertEquals('newstudent@example.com', $credentials['email']);
        $this->assertNotEmpty($credentials['password']);

        $this->assertDatabaseHas('users', ['email' => 'newstudent@example.com']);
    }

    public function test_admin_creates_user_with_send_credentials_sends_email(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Emailed User',
                'email' => 'emailed@example.com',
                'role' => 'teacher',
                'send_credentials' => true,
            ]);

        $newUser = User::where('email', 'emailed@example.com')->first();
        Notification::assertSentTo($newUser, UserCredentialsNotification::class);
    }

    public function test_admin_creates_user_without_send_credentials_no_email(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Silent User',
                'email' => 'silent@example.com',
                'role' => 'student',
                'send_credentials' => false,
            ]);

        Notification::assertNothingSent();
    }

    public function test_pending_credentials_returns_and_clears_session(): void
    {
        $credentials = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret123',
        ];

        $response = $this->actingAs($this->admin)
            ->withSession(['new_user_credentials' => $credentials])
            ->get(route('admin.users.pending-credentials'));

        $response->assertOk();
        $response->assertJson($credentials);

        $secondResponse = $this->actingAs($this->admin)
            ->get(route('admin.users.pending-credentials'));

        $secondResponse->assertNotFound();
    }

    public function test_pending_credentials_returns_404_when_no_session_data(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.users.pending-credentials'));

        $response->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_access_pending_credentials(): void
    {
        $response = $this->get(route('admin.users.pending-credentials'));

        $response->assertRedirect();
    }
}
