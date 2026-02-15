<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class AdminEnrollmentCreateTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private User $admin;

    private User $student;

    private ClassModel $class;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->admin = $this->createAdmin();
        $this->student = $this->createStudent();

        $academicYear = AcademicYear::firstOrCreate(
            ['is_current' => true],
            ['name' => '2025/2026', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30']
        );

        $level = Level::factory()->create();

        $this->class = ClassModel::factory()->create([
            'academic_year_id' => $academicYear->id,
            'level_id' => $level->id,
            'max_students' => 30,
        ]);
    }

    public function test_admin_can_view_create_enrollment_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.create'))
            ->assertStatus(200)
            ->assertInertia(
                fn($page) => $page
                    ->component('Admin/Enrollments/Create')
                    ->has('classes')
                    ->has('students')
            );
    }

    public function test_create_page_returns_classes_with_enrollments(): void
    {
        Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.create'))
            ->assertStatus(200)
            ->assertInertia(
                fn($page) => $page
                    ->component('Admin/Enrollments/Create')
                    ->has(
                        'classes',
                        1,
                        fn($page) => $page
                            ->where('active_enrollments_count', 1)
                            ->has('enrollments', 1)
                            ->etc()
                    )
            );
    }

    public function test_create_page_returns_students_with_avatar(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.create'))
            ->assertStatus(200)
            ->assertInertia(
                fn($page) => $page
                    ->component('Admin/Enrollments/Create')
                    ->has(
                        'students',
                        1,
                        fn($page) => $page
                            ->has('email')
                            ->has('name')
                            ->etc()
                    )
            );
    }

    public function test_admin_can_enroll_student(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.store'), [
                'student_id' => $this->student->id,
                'class_id' => $this->class->id,
            ])
            ->assertRedirect(route('admin.enrollments.index'));

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'status' => 'active',
        ]);
    }

    public function test_admin_can_quick_create_student(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)
            ->postJson(route('admin.enrollments.quick-student'), [
                'name' => 'New Student',
                'email' => 'newstudent@example.com',
            ])
            ->assertStatus(201)
            ->assertJsonStructure(['id', 'name', 'email', 'avatar']);

        $this->assertDatabaseHas('users', [
            'name' => 'New Student',
            'email' => 'newstudent@example.com',
        ]);

        $newUser = User::where('email', 'newstudent@example.com')->first();
        $this->assertTrue($newUser->hasRole('student'));
    }

    public function test_quick_create_student_validates_email(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.enrollments.quick-student'), [
                'name' => 'New Student',
                'email' => 'invalid-email',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_quick_create_student_rejects_duplicate_email(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.enrollments.quick-student'), [
                'name' => 'Duplicate',
                'email' => $this->student->email,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_student_cannot_access_create_page(): void
    {
        $this->actingAs($this->student)
            ->get(route('admin.enrollments.create'))
            ->assertForbidden();
    }

    public function test_student_cannot_quick_create_student(): void
    {
        $this->actingAs($this->student)
            ->postJson(route('admin.enrollments.quick-student'), [
                'name' => 'Test',
                'email' => 'test@example.com',
            ])
            ->assertForbidden();
    }

    public function test_enrollment_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.store'), [])
            ->assertSessionHasErrors(['student_id', 'class_id']);
    }
}
