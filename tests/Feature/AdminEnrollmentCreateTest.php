<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassModel;
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
                fn ($page) => $page
                    ->component('Admin/Enrollments/Create')
                    ->has('selectedYearId')
            );
    }

    public function test_create_page_does_not_preload_all_students_or_classes(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.create'))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->component('Admin/Enrollments/Create')
                    ->missing('classes')
                    ->missing('students')
            );
    }

    public function test_search_students_endpoint_returns_results(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('admin.enrollments.search-students', ['q' => $this->student->name]))
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $this->student->id]);
    }

    public function test_search_classes_endpoint_returns_results(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('admin.enrollments.search-classes', ['q' => $this->class->name]))
            ->assertStatus(200)
            ->assertJsonFragment(['id' => $this->class->id]);
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

    public function test_admin_can_create_student_in_enrollment_context(): void
    {
        Notification::fake();

        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.create-student'), [
                'name' => 'New Student',
                'email' => 'newstudent@example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'name' => 'New Student',
            'email' => 'newstudent@example.com',
        ]);

        $newUser = User::where('email', 'newstudent@example.com')->first();
        $this->assertTrue($newUser->hasRole('student'));
    }

    public function test_create_student_validates_email(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.create-student'), [
                'name' => 'New Student',
                'email' => 'invalid-email',
            ])
            ->assertSessionHasErrors(['email']);
    }

    public function test_create_student_rejects_duplicate_email(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.create-student'), [
                'name' => 'Duplicate',
                'email' => $this->student->email,
            ])
            ->assertSessionHasErrors(['email']);
    }

    public function test_student_cannot_access_create_page(): void
    {
        $this->actingAs($this->student)
            ->get(route('admin.enrollments.create'))
            ->assertForbidden();
    }

    public function test_student_cannot_create_student_in_enrollment_context(): void
    {
        $this->actingAs($this->student)
            ->post(route('admin.enrollments.create-student'), [
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

    public function test_bulk_store_enrolls_multiple_students(): void
    {
        $student2 = User::factory()->create();
        $student2->assignRole('student');

        $this->actingAs($this->admin)
            ->postJson(route('admin.enrollments.bulk-store'), [
                'class_id' => $this->class->id,
                'student_ids' => [$this->student->id, $student2->id],
            ])
            ->assertStatus(200)
            ->assertJsonPath('class_name', $this->class->name)
            ->assertJsonCount(2, 'enrolled')
            ->assertJsonCount(0, 'failed');

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
        ]);
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student2->id,
            'class_id' => $this->class->id,
        ]);
    }

    public function test_bulk_store_reports_already_enrolled_as_failed(): void
    {
        \App\Models\Enrollment::factory()->create([
            'student_id' => $this->student->id,
            'class_id' => $this->class->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.enrollments.bulk-store'), [
                'class_id' => $this->class->id,
                'student_ids' => [$this->student->id],
            ])
            ->assertStatus(200)
            ->assertJsonCount(0, 'enrolled')
            ->assertJsonCount(1, 'failed');
    }

    public function test_bulk_store_validates_required_fields(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.enrollments.bulk-store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['class_id', 'student_ids']);
    }

    public function test_student_cannot_bulk_store(): void
    {
        $this->actingAs($this->student)
            ->postJson(route('admin.enrollments.bulk-store'), [
                'class_id' => $this->class->id,
                'student_ids' => [$this->student->id],
            ])
            ->assertForbidden();
    }
}
