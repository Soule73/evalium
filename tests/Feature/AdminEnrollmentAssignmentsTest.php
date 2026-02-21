<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class AdminEnrollmentAssignmentsTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private User $admin;

    private User $student;

    private ClassModel $class;

    private Enrollment $enrollment;

    private AcademicYear $academicYear;

    private Level $level;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();

        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->admin = $this->createAdmin();
        $this->student = $this->createStudent();

        $this->academicYear = AcademicYear::firstOrCreate(
            ['is_current' => true],
            ['name' => '2025/2026', 'start_date' => '2025-09-01', 'end_date' => '2026-06-30']
        );

        $this->level = Level::factory()->create();

        $this->class = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'max_students' => 30,
        ]);

        $this->enrollment = $this->class->enrollments()->create([
            'student_id' => $this->student->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);
    }

    public function test_admin_can_withdraw_student(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.withdraw', $this->enrollment))
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'id' => $this->enrollment->id,
            'status' => 'withdrawn',
        ]);
    }

    public function test_admin_can_reactivate_withdrawn_enrollment(): void
    {
        $this->enrollment->update(['status' => 'withdrawn', 'withdrawn_at' => now()]);

        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.reactivate', $this->enrollment))
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'id' => $this->enrollment->id,
            'status' => 'active',
        ]);
    }

    public function test_reactivation_fails_for_active_enrollment(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.reactivate', $this->enrollment))
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'id' => $this->enrollment->id,
            'status' => 'active',
        ]);
    }

    public function test_enrollment_fails_gracefully_when_class_is_full(): void
    {
        $fullClass = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'max_students' => 1,
        ]);

        $existingStudent = $this->createStudent();
        $fullClass->enrollments()->create([
            'student_id' => $existingStudent->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $newStudent = $this->createStudent();

        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.store'), [
                'student_id' => $newStudent->id,
                'class_id' => $fullClass->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $newStudent->id,
            'class_id' => $fullClass->id,
        ]);
    }

    public function test_capacity_check_ignores_withdrawn_enrollments(): void
    {
        $fullClass = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'max_students' => 1,
        ]);

        $formerStudent = $this->createStudent();
        $fullClass->enrollments()->create([
            'student_id' => $formerStudent->id,
            'enrolled_at' => now(),
            'status' => 'withdrawn',
            'withdrawn_at' => now(),
        ]);

        $newStudent = $this->createStudent();

        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.store'), [
                'student_id' => $newStudent->id,
                'class_id' => $fullClass->id,
            ])
            ->assertRedirect(route('admin.enrollments.index'));

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $newStudent->id,
            'class_id' => $fullClass->id,
            'status' => 'active',
        ]);
    }

    public function test_enrollment_fails_gracefully_when_student_already_enrolled(): void
    {
        $secondClass = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.store'), [
                'student_id' => $this->student->id,
                'class_id' => $secondClass->id,
            ])
            ->assertredirect();

        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $this->student->id,
            'class_id' => $secondClass->id,
        ]);
    }

    public function test_admin_can_transfer_student_to_another_class(): void
    {
        $targetClass = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'max_students' => 30,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.transfer', $this->enrollment), [
                'new_class_id' => $targetClass->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->student->id,
            'class_id' => $targetClass->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('enrollments', [
            'id' => $this->enrollment->id,
            'status' => 'withdrawn',
        ]);
    }

    public function test_transfer_fails_when_target_class_is_full(): void
    {
        $fullClass = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $this->level->id,
            'max_students' => 1,
        ]);

        $blockingStudent = $this->createStudent();
        $fullClass->enrollments()->create([
            'student_id' => $blockingStudent->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.transfer', $this->enrollment), [
                'new_class_id' => $fullClass->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $this->student->id,
            'class_id' => $fullClass->id,
        ]);

        $this->assertDatabaseHas('enrollments', [
            'id' => $this->enrollment->id,
            'status' => 'active',
        ]);
    }

    public function test_unauthenticated_user_cannot_transfer_student(): void
    {
        $this->post(route('admin.enrollments.transfer', $this->enrollment), [
            'new_class_id' => $this->class->id,
        ])->assertRedirect(route('login'));
    }

    public function test_student_cannot_withdraw_an_enrollment(): void
    {
        $this->actingAs($this->student)
            ->post(route('admin.enrollments.withdraw', $this->enrollment))
            ->assertStatus(403);
    }

    public function test_admin_can_delete_enrollment(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('admin.enrollments.destroy', $this->enrollment))
            ->assertRedirect();

        $this->assertDatabaseMissing('enrollments', [
            'id' => $this->enrollment->id,
        ]);
    }

    public function test_admin_can_view_enrollments_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.index'))
            ->assertStatus(200)
            ->assertInertia(
                fn ($page) => $page
                    ->component('Admin/Enrollments/Index')
                    ->has('enrollments')
                    ->has('classes')
            );
    }

    public function test_admin_can_view_enrollment_create_page(): void
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

    public function test_student_cannot_access_enrollment_index(): void
    {
        $this->actingAs($this->student)
            ->get(route('admin.enrollments.index'))
            ->assertStatus(403);
    }

    public function test_create_student_in_enrollment_context_succeeds(): void
    {
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

    public function test_create_student_fails_with_duplicate_email(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.create-student'), [
                'name' => $this->student->name,
                'email' => $this->student->email,
            ])
            ->assertSessionHasErrors(['email']);
    }
}
