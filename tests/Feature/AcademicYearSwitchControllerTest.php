<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class AcademicYearSwitchControllerTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private User $admin;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        $this->admin = $this->createAdmin();
        $this->student = $this->createStudent();
    }

    /**
     * Switch session endpoint redirects back on success.
     */
    public function test_authenticated_user_can_switch_academic_year_in_session(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('api.academic-years.set-current'), [
                'academic_year_id' => $year->id,
            ]);

        $response->assertRedirect();
        $this->assertEquals($year->id, session('academic_year_id'));
    }

    /**
     * Switching does not set is_current=true in the database.
     */
    public function test_switch_does_not_change_is_current_in_database(): void
    {
        $currentYear = AcademicYear::factory()->current()->create();
        $otherYear = AcademicYear::factory()->create(['is_current' => false]);

        $this->actingAs($this->student)
            ->post(route('api.academic-years.set-current'), [
                'academic_year_id' => $otherYear->id,
            ]);

        $this->assertDatabaseHas('academic_years', ['id' => $currentYear->id, 'is_current' => true]);
        $this->assertDatabaseHas('academic_years', ['id' => $otherYear->id, 'is_current' => false]);
    }

    /**
     * Guest cannot switch academic year.
     */
    public function test_guest_cannot_switch_academic_year(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->post(route('api.academic-years.set-current'), [
            'academic_year_id' => $year->id,
        ]);

        $response->assertRedirect(route('login'));
    }

    /**
     * Invalid academic year ID is rejected.
     */
    public function test_invalid_academic_year_id_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('api.academic-years.set-current'), [
                'academic_year_id' => 99999,
            ]);

        $response->assertSessionHasErrors('academic_year_id');
    }
}
