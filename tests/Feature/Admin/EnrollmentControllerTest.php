<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\AssessmentAssignment;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\Level;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class EnrollmentControllerTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    private AcademicYear $academicYear;

    private ClassModel $class;

    private ClassModel $otherClass;

    private Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->admin = $this->createAdmin();
        $this->teacher = $this->createTeacher();
        $this->student = $this->createStudent();

        $level = Level::factory()->create();
        $this->academicYear = AcademicYear::factory()->create(['is_current' => true]);
        $this->class = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $level->id,
        ]);
        $this->otherClass = ClassModel::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'level_id' => $level->id,
        ]);

        $this->enrollment = Enrollment::factory()->create([
            'class_id' => $this->class->id,
            'student_id' => $this->student->id,
            'status' => 'active',
        ]);
    }

    // ---------------------------------------------------------------
    // Index
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_index(): void
    {
        $this->get(route('admin.enrollments.index'))->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_index(): void
    {
        $this->actingAs($this->student)->get(route('admin.enrollments.index'))->assertForbidden();
    }

    public function test_admin_can_access_index(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.enrollments.index'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Admin/Enrollments/Index')
                    ->has('enrollments')
                    ->has('classes')
            );
    }

    // ---------------------------------------------------------------
    // Search Students / Classes (authorization guard)
    // ---------------------------------------------------------------

    public function test_guest_cannot_search_students(): void
    {
        $this->getJson(route('admin.enrollments.search-students'))->assertUnauthorized();
    }

    public function test_student_cannot_search_students(): void
    {
        $this->actingAs($this->student)
            ->getJson(route('admin.enrollments.search-students'))
            ->assertForbidden();
    }

    public function test_admin_can_search_students(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('admin.enrollments.search-students', ['q' => $this->student->name]))
            ->assertOk();
    }

    public function test_guest_cannot_search_classes(): void
    {
        $this->getJson(route('admin.enrollments.search-classes'))->assertUnauthorized();
    }

    public function test_student_cannot_search_classes(): void
    {
        $this->actingAs($this->student)
            ->getJson(route('admin.enrollments.search-classes'))
            ->assertForbidden();
    }

    public function test_admin_can_search_classes(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('admin.enrollments.search-classes'))
            ->assertOk();
    }

    // ---------------------------------------------------------------
    // Transfer
    // ---------------------------------------------------------------

    public function test_guest_cannot_transfer_student(): void
    {
        $this->post(route('admin.enrollments.transfer', $this->enrollment), [
            'new_class_id' => $this->otherClass->id,
        ])->assertRedirect(route('login'));
    }

    public function test_student_cannot_transfer_enrollment(): void
    {
        $this->actingAs($this->student)
            ->post(route('admin.enrollments.transfer', $this->enrollment), [
                'new_class_id' => $this->otherClass->id,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_transfer_student_to_another_class(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.transfer', $this->enrollment), [
                'new_class_id' => $this->otherClass->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->student->id,
            'class_id' => $this->otherClass->id,
            'status' => 'active',
        ]);

        $this->enrollment->refresh();
        $this->assertEquals('withdrawn', $this->enrollment->status->value);
    }

    public function test_transfer_requires_different_class(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.transfer', $this->enrollment), [
                'new_class_id' => $this->class->id,
            ])
            ->assertSessionHasErrors('new_class_id');
    }

    // ---------------------------------------------------------------
    // Withdraw
    // ---------------------------------------------------------------

    public function test_guest_cannot_withdraw_enrollment(): void
    {
        $this->post(route('admin.enrollments.withdraw', $this->enrollment))
            ->assertRedirect(route('login'));
    }

    public function test_student_cannot_withdraw_enrollment(): void
    {
        $this->actingAs($this->student)
            ->post(route('admin.enrollments.withdraw', $this->enrollment))
            ->assertForbidden();
    }

    public function test_admin_can_withdraw_student(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.withdraw', $this->enrollment))
            ->assertRedirect();

        $this->enrollment->refresh();
        $this->assertEquals('withdrawn', $this->enrollment->status->value);
        $this->assertNotNull($this->enrollment->withdrawn_at);
    }

    // ---------------------------------------------------------------
    // Reactivate
    // ---------------------------------------------------------------

    public function test_guest_cannot_reactivate_enrollment(): void
    {
        $withdrawn = Enrollment::factory()->withdrawn()->create([
            'class_id' => $this->class->id,
        ]);

        $this->post(route('admin.enrollments.reactivate', $withdrawn))
            ->assertRedirect(route('login'));
    }

    public function test_student_cannot_reactivate_enrollment(): void
    {
        $withdrawn = Enrollment::factory()->withdrawn()->create([
            'class_id' => $this->class->id,
        ]);

        $this->actingAs($this->student)
            ->post(route('admin.enrollments.reactivate', $withdrawn))
            ->assertForbidden();
    }

    public function test_admin_can_reactivate_withdrawn_enrollment(): void
    {
        $withdrawn = Enrollment::factory()->withdrawn()->create([
            'class_id' => $this->class->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.reactivate', $withdrawn))
            ->assertRedirect();

        $withdrawn->refresh();
        $this->assertEquals('active', $withdrawn->status->value);
        $this->assertNull($withdrawn->withdrawn_at);
    }

    public function test_reactivating_active_enrollment_returns_error(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.enrollments.reactivate', $this->enrollment))
            ->assertRedirect();

        $this->enrollment->refresh();
        $this->assertEquals('active', $this->enrollment->status->value);
    }

    // ---------------------------------------------------------------
    // Destroy
    // ---------------------------------------------------------------

    public function test_guest_cannot_delete_enrollment(): void
    {
        $this->delete(route('admin.enrollments.destroy', $this->enrollment))
            ->assertRedirect(route('login'));
    }

    public function test_student_cannot_delete_enrollment(): void
    {
        $this->actingAs($this->student)
            ->delete(route('admin.enrollments.destroy', $this->enrollment))
            ->assertForbidden();
    }

    public function test_admin_can_delete_enrollment_without_assignments(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('admin.enrollments.destroy', $this->enrollment))
            ->assertRedirect(route('admin.enrollments.index'));

        $this->assertDatabaseMissing('enrollments', ['id' => $this->enrollment->id]);
    }

    public function test_admin_cannot_delete_enrollment_with_assignments(): void
    {
        AssessmentAssignment::factory()->create(['enrollment_id' => $this->enrollment->id]);

        $this->actingAs($this->admin)
            ->delete(route('admin.enrollments.destroy', $this->enrollment))
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', ['id' => $this->enrollment->id]);
    }
}
