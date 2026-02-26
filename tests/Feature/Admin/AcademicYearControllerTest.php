<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\ClassModel;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class AcademicYearControllerTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->admin = $this->createAdmin();
        $this->teacher = $this->createTeacher();
        $this->student = $this->createStudent();
    }

    // ---------------------------------------------------------------
    // Archives (index)
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_archives(): void
    {
        $response = $this->get(route('admin.academic-years.archives'));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_archives(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('admin.academic-years.archives'));

        $response->assertForbidden();
    }

    public function test_teacher_cannot_access_archives(): void
    {
        $response = $this->actingAs($this->teacher)
            ->get(route('admin.academic-years.archives'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_archives(): void
    {
        $year = AcademicYear::factory()->create();
        Semester::factory()->create(['academic_year_id' => $year->id, 'order_number' => 1]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.academic-years.archives'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/AcademicYears/Archives')
                ->has('academicYears')
                ->has('filters')
        );
    }

    public function test_archives_filters_by_search(): void
    {
        AcademicYear::factory()->create(['name' => '2024/2025']);
        AcademicYear::factory()->create(['name' => '2025/2026']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.academic-years.archives', ['search' => '2024']));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/AcademicYears/Archives')
                ->where('filters.search', '2024')
        );
    }

    public function test_archives_eager_loads_semesters(): void
    {
        $year = AcademicYear::factory()->create();
        Semester::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'Semestre 1',
            'start_date' => $year->start_date,
            'end_date' => $year->start_date->copy()->addMonths(4),
            'order_number' => 1,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.academic-years.archives'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/AcademicYears/Archives')
                ->has('academicYears.data.0.semesters')
        );
    }

    // ---------------------------------------------------------------
    // Create page
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_create_page(): void
    {
        $response = $this->get(route('admin.academic-years.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_create_page(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('admin.academic-years.create'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_create_page(): void
    {
        AcademicYear::factory()->current()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.academic-years.create'));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/AcademicYears/Create')
                ->has('currentYear')
                ->has('futureYearExists')
        );
    }

    // ---------------------------------------------------------------
    // Store
    // ---------------------------------------------------------------

    public function test_store_requires_authentication(): void
    {
        $response = $this->post(route('admin.academic-years.store'), []);

        $response->assertRedirect(route('login'));
    }

    public function test_store_requires_name(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.academic-years.store'), $this->validPayload(['name' => '']));

        $response->assertSessionHasErrors('name');
    }

    public function test_store_requires_unique_name(): void
    {
        AcademicYear::factory()->create(['name' => '2024/2025']);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.academic-years.store'), $this->validPayload(['name' => '2024/2025']));

        $response->assertSessionHasErrors('name');
    }

    public function test_store_requires_start_date(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.academic-years.store'), $this->validPayload(['start_date' => '']));

        $response->assertSessionHasErrors('start_date');
    }

    public function test_store_requires_end_date_after_start_date(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.academic-years.store'), $this->validPayload([
                'start_date' => '2025-09-01',
                'end_date' => '2025-08-01',
            ]));

        $response->assertSessionHasErrors('end_date');
    }

    public function test_store_requires_at_least_one_semester(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.academic-years.store'), $this->validPayload(['semesters' => []]));

        $response->assertSessionHasErrors('semesters');
    }

    public function test_store_validates_semester_name_required(): void
    {
        $payload = $this->validPayload();
        $payload['semesters'][0]['name'] = '';

        $response = $this->actingAs($this->admin)->post(route('admin.academic-years.store'), $payload);

        $response->assertSessionHasErrors('semesters.0.name');
    }

    public function test_store_validates_semester_dates_required(): void
    {
        $payload = $this->validPayload();
        $payload['semesters'][0]['start_date'] = '';
        $payload['semesters'][0]['end_date'] = '';

        $response = $this->actingAs($this->admin)->post(route('admin.academic-years.store'), $payload);

        $response->assertSessionHasErrors(['semesters.0.start_date', 'semesters.0.end_date']);
    }

    public function test_store_validates_semester_end_after_start(): void
    {
        $payload = $this->validPayload();
        $payload['semesters'] = [
            [
                'name' => 'Semestre 1',
                'start_date' => '2026-01-15',
                'end_date' => '2025-10-01',
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.academic-years.store'), $payload);

        $response->assertSessionHasErrors('semesters.0.end_date');
    }

    public function test_store_validates_semester_within_year_bounds(): void
    {
        $payload = $this->validPayload();
        $payload['semesters'] = [
            [
                'name' => 'Semestre 1',
                'start_date' => '2024-01-01',
                'end_date' => '2024-06-30',
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.academic-years.store'), $payload);

        $response->assertSessionHasErrors('semesters.0.start_date');
    }

    public function test_store_validates_semester_overlap(): void
    {
        $payload = $this->validPayload();
        $payload['semesters'] = [
            [
                'name' => 'Semestre 1',
                'start_date' => '2025-09-01',
                'end_date' => '2026-02-15',
            ],
            [
                'name' => 'Semestre 2',
                'start_date' => '2026-02-01',
                'end_date' => '2026-06-30',
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.academic-years.store'), $payload);

        $response->assertSessionHasErrors('semesters.1.start_date');
    }

    public function test_store_creates_academic_year_with_semesters(): void
    {
        $payload = $this->validPayload();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.academic-years.store'), $payload);

        $response->assertRedirect(route('admin.academic-years.archives'));
        $response->assertSessionHas('success');

        $year = AcademicYear::where('name', $payload['name'])->first();
        $this->assertNotNull($year);
        $this->assertEquals($payload['start_date'], $year->start_date->format('Y-m-d'));
        $this->assertEquals($payload['end_date'], $year->end_date->format('Y-m-d'));
        $this->assertCount(2, $year->semesters);
    }

    public function test_store_with_is_current_deactivates_previous(): void
    {
        $existingCurrent = AcademicYear::factory()->current()->create();

        $payload = $this->validPayload(['is_current' => true]);

        $this->actingAs($this->admin)->post(route('admin.academic-years.store'), $payload);

        $this->assertFalse($existingCurrent->fresh()->is_current);

        $newYear = AcademicYear::where('name', $payload['name'])->first();
        $this->assertTrue($newYear->is_current);
    }

    public function test_store_without_is_current_keeps_previous(): void
    {
        $existingCurrent = AcademicYear::factory()->current()->create();

        $payload = $this->validPayload(['is_current' => false]);

        $this->actingAs($this->admin)->post(route('admin.academic-years.store'), $payload);

        $this->assertTrue($existingCurrent->fresh()->is_current);
    }

    public function test_student_cannot_store(): void
    {
        $response = $this->actingAs($this->student)
            ->post(route('admin.academic-years.store'), $this->validPayload());

        $response->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Edit page
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_edit_page(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->get(route('admin.academic-years.edit', $year));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_edit_page(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->actingAs($this->student)
            ->get(route('admin.academic-years.edit', $year));

        $response->assertForbidden();
    }

    public function test_admin_can_access_edit_page(): void
    {
        $year = AcademicYear::factory()->create();
        Semester::factory()->create([
            'academic_year_id' => $year->id,
            'order_number' => 1,
            'start_date' => $year->start_date,
            'end_date' => $year->start_date->copy()->addMonths(4),
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.academic-years.edit', $year));

        $response->assertOk();
        $response->assertInertia(
            fn ($page) => $page
                ->component('Admin/AcademicYears/Edit')
                ->has('academicYear')
                ->has('academicYear.semesters')
        );
    }

    // ---------------------------------------------------------------
    // Update
    // ---------------------------------------------------------------

    public function test_update_requires_authentication(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->put(route('admin.academic-years.update', $year), []);

        $response->assertRedirect(route('login'));
    }

    public function test_update_validates_unique_name_excluding_self(): void
    {
        $yearA = AcademicYear::factory()->create(['name' => '2024/2025']);
        $yearB = AcademicYear::factory()->create(['name' => '2025/2026']);

        $response = $this->actingAs($this->admin)
            ->put(
                route('admin.academic-years.update', $yearB),
                $this->validPayload(['name' => '2024/2025'])
            );

        $response->assertSessionHasErrors('name');
    }

    public function test_update_allows_same_name_for_self(): void
    {
        $year = AcademicYear::factory()->create(['name' => '2024/2025']);
        Semester::factory()->create([
            'academic_year_id' => $year->id,
            'order_number' => 1,
            'start_date' => $year->start_date,
            'end_date' => $year->start_date->copy()->addMonths(4),
        ]);

        $response = $this->actingAs($this->admin)
            ->put(
                route('admin.academic-years.update', $year),
                $this->validPayload([
                    'name' => '2024/2025',
                    'start_date' => $year->start_date->format('Y-m-d'),
                    'end_date' => $year->end_date->format('Y-m-d'),
                    'semesters' => [
                        [
                            'name' => 'Semestre 1',
                            'start_date' => $year->start_date->format('Y-m-d'),
                            'end_date' => $year->start_date->copy()->addMonths(4)->format('Y-m-d'),
                        ],
                    ],
                ])
            );

        $response->assertRedirect(route('admin.academic-years.archives'));
        $response->assertSessionHasNoErrors();
    }

    public function test_update_modifies_year_and_syncs_semesters(): void
    {
        $year = AcademicYear::factory()->create([
            'name' => '2024/2025',
            'start_date' => '2024-09-01',
            'end_date' => '2025-06-30',
        ]);

        $sem1 = Semester::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'Semestre 1',
            'start_date' => '2024-09-01',
            'end_date' => '2025-01-31',
            'order_number' => 1,
        ]);

        $sem2 = Semester::factory()->create([
            'academic_year_id' => $year->id,
            'name' => 'Semestre 2',
            'start_date' => '2025-02-01',
            'end_date' => '2025-06-30',
            'order_number' => 2,
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.academic-years.update', $year), [
                'name' => '2024/2025 Updated',
                'start_date' => '2024-09-01',
                'end_date' => '2025-06-30',
                'is_current' => false,
                'semesters' => [
                    [
                        'id' => $sem1->id,
                        'name' => 'S1 Renamed',
                        'start_date' => '2024-09-01',
                        'end_date' => '2025-01-31',
                    ],
                    [
                        'name' => 'S3 New',
                        'start_date' => '2025-02-01',
                        'end_date' => '2025-06-30',
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.academic-years.archives'));
        $response->assertSessionHas('success');

        $year->refresh();
        $this->assertEquals('2024/2025 Updated', $year->name);

        $this->assertDatabaseHas('semesters', ['id' => $sem1->id, 'name' => 'S1 Renamed']);
        $this->assertDatabaseMissing('semesters', ['id' => $sem2->id]);
        $this->assertDatabaseHas('semesters', ['name' => 'S3 New', 'academic_year_id' => $year->id]);
    }

    public function test_update_with_is_current_deactivates_previous(): void
    {
        $existingCurrent = AcademicYear::factory()->current()->create();
        $yearToUpdate = AcademicYear::factory()->create([
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
        ]);
        Semester::factory()->create([
            'academic_year_id' => $yearToUpdate->id,
            'order_number' => 1,
            'start_date' => '2026-09-01',
            'end_date' => '2027-01-31',
        ]);

        $this->actingAs($this->admin)->put(
            route('admin.academic-years.update', $yearToUpdate),
            [
                'name' => $yearToUpdate->name,
                'start_date' => '2026-09-01',
                'end_date' => '2027-06-30',
                'is_current' => true,
                'semesters' => [
                    [
                        'name' => 'Semestre 1',
                        'start_date' => '2026-09-01',
                        'end_date' => '2027-01-31',
                    ],
                ],
            ]
        );

        $this->assertFalse($existingCurrent->fresh()->is_current);
        $this->assertTrue($yearToUpdate->fresh()->is_current);
    }

    public function test_student_cannot_update(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->actingAs($this->student)
            ->put(route('admin.academic-years.update', $year), $this->validPayload());

        $response->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Destroy
    // ---------------------------------------------------------------

    public function test_destroy_requires_authentication(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->delete(route('admin.academic-years.destroy', $year));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_destroy(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->actingAs($this->student)
            ->delete(route('admin.academic-years.destroy', $year));

        $response->assertForbidden();
    }

    public function test_admin_can_destroy_academic_year(): void
    {
        $year = AcademicYear::factory()->create(['is_current' => false]);
        Semester::factory()->create(['academic_year_id' => $year->id, 'order_number' => 1]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.academic-years.destroy', $year));

        $response->assertRedirect(route('admin.academic-years.archives'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('academic_years', ['id' => $year->id]);
        $this->assertDatabaseMissing('semesters', ['academic_year_id' => $year->id]);
    }

    public function test_cannot_destroy_current_academic_year(): void
    {
        $year = AcademicYear::factory()->current()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.academic-years.destroy', $year));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('academic_years', ['id' => $year->id]);
    }

    public function test_cannot_destroy_academic_year_with_classes(): void
    {
        $year = AcademicYear::factory()->create(['is_current' => false]);
        ClassModel::factory()->create(['academic_year_id' => $year->id]);

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.academic-years.destroy', $year));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('academic_years', ['id' => $year->id]);
    }

    // ---------------------------------------------------------------
    // Set Current
    // ---------------------------------------------------------------

    public function test_set_current_requires_authentication(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->post(route('admin.academic-years.set-current', $year));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_set_current(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->actingAs($this->student)
            ->post(route('admin.academic-years.set-current', $year));

        $response->assertForbidden();
    }

    public function test_admin_can_set_current(): void
    {
        $oldCurrent = AcademicYear::factory()->current()->create();
        $newCurrent = AcademicYear::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.academic-years.set-current', $newCurrent));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertFalse($oldCurrent->fresh()->is_current);
        $this->assertTrue($newCurrent->fresh()->is_current);
    }

    public function test_set_current_when_no_previous_current(): void
    {
        $year = AcademicYear::factory()->create(['is_current' => false]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.academic-years.set-current', $year));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue($year->fresh()->is_current);
    }

    // ---------------------------------------------------------------
    // Archive
    // ---------------------------------------------------------------

    public function test_archive_requires_authentication(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->post(route('admin.academic-years.archive', $year));

        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_archive(): void
    {
        $year = AcademicYear::factory()->current()->create();

        $response = $this->actingAs($this->student)
            ->post(route('admin.academic-years.archive', $year));

        $response->assertForbidden();
    }

    public function test_admin_can_archive_current_year(): void
    {
        $year = AcademicYear::factory()->current()->create();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.academic-years.archive', $year));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertFalse($year->fresh()->is_current);
    }

    public function test_archive_non_current_year_stays_non_current(): void
    {
        $year = AcademicYear::factory()->create(['is_current' => false]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.academic-years.archive', $year));

        $response->assertRedirect();
        $this->assertFalse($year->fresh()->is_current);
    }

    // ---------------------------------------------------------------
    // Service: syncSemesters edge cases
    // ---------------------------------------------------------------

    public function test_store_with_three_non_overlapping_semesters(): void
    {
        $payload = [
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_current' => false,
            'semesters' => [
                [
                    'name' => 'Trimestre 1',
                    'start_date' => '2025-09-01',
                    'end_date' => '2025-12-01',
                ],
                [
                    'name' => 'Trimestre 2',
                    'start_date' => '2025-12-02',
                    'end_date' => '2026-03-15',
                ],
                [
                    'name' => 'Trimestre 3',
                    'start_date' => '2026-03-16',
                    'end_date' => '2026-06-30',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.academic-years.store'), $payload);

        $response->assertRedirect(route('admin.academic-years.archives'));
        $response->assertSessionHasNoErrors();

        $year = AcademicYear::where('name', '2025/2026')->first();
        $this->assertCount(3, $year->semesters);
        $this->assertEquals([1, 2, 3], $year->semesters->pluck('order_number')->sort()->values()->all());
    }

    public function test_update_removes_all_old_semesters_when_none_have_ids(): void
    {
        $year = AcademicYear::factory()->create([
            'start_date' => '2024-09-01',
            'end_date' => '2025-06-30',
        ]);

        Semester::factory()->create([
            'academic_year_id' => $year->id,
            'order_number' => 1,
            'start_date' => '2024-09-01',
            'end_date' => '2025-01-31',
        ]);

        Semester::factory()->create([
            'academic_year_id' => $year->id,
            'order_number' => 2,
            'start_date' => '2025-02-01',
            'end_date' => '2025-06-30',
        ]);

        $this->actingAs($this->admin)->put(
            route('admin.academic-years.update', $year),
            [
                'name' => $year->name,
                'start_date' => '2024-09-01',
                'end_date' => '2025-06-30',
                'is_current' => false,
                'semesters' => [
                    [
                        'name' => 'New Single Semester',
                        'start_date' => '2024-09-01',
                        'end_date' => '2025-06-30',
                    ],
                ],
            ]
        );

        $this->assertCount(1, $year->fresh()->semesters);
    }

    // ---------------------------------------------------------------
    // Wizard Store
    // ---------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    private function wizardPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => '2026/2027',
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
            'is_current' => false,
            'class_ids' => [],
            'semesters' => [
                [
                    'name' => 'Semester 1',
                    'start_date' => '2026-09-01',
                    'end_date' => '2027-01-31',
                ],
                [
                    'name' => 'Semester 2',
                    'start_date' => '2027-02-01',
                    'end_date' => '2027-06-30',
                ],
            ],
        ], $overrides);
    }

    public function test_wizard_store_requires_authentication(): void
    {
        $response = $this->postJson(route('admin.academic-years.wizard-store'), []);

        $response->assertUnauthorized();
    }

    public function test_student_cannot_use_wizard_store(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson(route('admin.academic-years.wizard-store'), $this->wizardPayload());

        $response->assertForbidden();
    }

    public function test_wizard_store_creates_academic_year(): void
    {
        $currentYear = AcademicYear::factory()->current()->create([
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.academic-years.wizard-store'), $this->wizardPayload());

        $response->assertCreated();
        $response->assertJsonStructure(['year', 'duplicated_classes_count']);
        $response->assertJsonPath('duplicated_classes_count', 0);

        $this->assertDatabaseHas('academic_years', ['name' => '2026/2027']);
    }

    public function test_wizard_store_duplicates_selected_classes(): void
    {
        $currentYear = AcademicYear::factory()->current()->create([
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
        ]);
        $class1 = ClassModel::factory()->create(['academic_year_id' => $currentYear->id]);
        $class2 = ClassModel::factory()->create(['academic_year_id' => $currentYear->id]);

        $response = $this->actingAs($this->admin)
            ->postJson(
                route('admin.academic-years.wizard-store'),
                $this->wizardPayload(['class_ids' => [$class1->id]])
            );

        $response->assertCreated();
        $response->assertJsonPath('duplicated_classes_count', 1);

        $newYear = AcademicYear::where('name', '2026/2027')->first();
        $this->assertCount(1, $newYear->classes);
        $this->assertEquals($class1->name, $newYear->classes->first()->name);
    }

    public function test_wizard_store_rejects_when_future_year_already_exists(): void
    {
        $currentYear = AcademicYear::factory()->current()->create([
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
        ]);

        AcademicYear::factory()->create([
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.academic-years.wizard-store'), $this->wizardPayload());

        $response->assertUnprocessable();
        $response->assertJsonPath('message', __('messages.future_year_already_exists'));
    }

    public function test_wizard_store_requires_name(): void
    {
        AcademicYear::factory()->current()->create();

        $response = $this->actingAs($this->admin)
            ->postJson(
                route('admin.academic-years.wizard-store'),
                $this->wizardPayload(['name' => ''])
            );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
    }

    public function test_wizard_store_creates_semesters(): void
    {
        AcademicYear::factory()->current()->create([
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.academic-years.wizard-store'), $this->wizardPayload());

        $year = AcademicYear::where('name', '2026/2027')->first();
        $this->assertCount(2, $year->semesters);
    }

    // ---------------------------------------------------------------
    // Archive Policy
    // ---------------------------------------------------------------

    public function test_teacher_cannot_archive(): void
    {
        $year = AcademicYear::factory()->current()->create();

        $response = $this->actingAs($this->teacher)
            ->post(route('admin.academic-years.archive', $year));

        $response->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Cache invalidation
    // ---------------------------------------------------------------

    public function test_create_invalidates_academic_years_cache(): void
    {
        \Illuminate\Support\Facades\Cache::put(
            \App\Services\Core\CacheService::KEY_ACADEMIC_YEARS_RECENT,
            ['stale'],
            3600
        );
        \Illuminate\Support\Facades\Cache::put(
            \App\Services\Core\CacheService::KEY_ACADEMIC_YEARS_RECENT.':admin',
            ['stale'],
            3600
        );

        $this->actingAs($this->admin)->post(route('admin.academic-years.store'), $this->validPayload());

        $this->assertNull(\Illuminate\Support\Facades\Cache::get(\App\Services\Core\CacheService::KEY_ACADEMIC_YEARS_RECENT));
        $this->assertNull(\Illuminate\Support\Facades\Cache::get(\App\Services\Core\CacheService::KEY_ACADEMIC_YEARS_RECENT.':admin'));
    }

    public function test_set_current_invalidates_academic_years_cache(): void
    {
        $year = AcademicYear::factory()->create();

        \Illuminate\Support\Facades\Cache::put(
            \App\Services\Core\CacheService::KEY_ACADEMIC_YEARS_RECENT.':admin',
            ['stale'],
            3600
        );

        $this->actingAs($this->admin)->post(route('admin.academic-years.set-current', $year));

        $this->assertNull(\Illuminate\Support\Facades\Cache::get(\App\Services\Core\CacheService::KEY_ACADEMIC_YEARS_RECENT.':admin'));
    }

    public function test_archive_invalidates_academic_years_cache(): void
    {
        $year = AcademicYear::factory()->current()->create();

        \Illuminate\Support\Facades\Cache::put(
            \App\Services\Core\CacheService::KEY_ACADEMIC_YEARS_RECENT.':admin',
            ['stale'],
            3600
        );

        $this->actingAs($this->admin)->post(route('admin.academic-years.archive', $year));

        $this->assertNull(\Illuminate\Support\Facades\Cache::get(\App\Services\Core\CacheService::KEY_ACADEMIC_YEARS_RECENT.':admin'));
    }

    public function test_destroy_invalidates_academic_years_cache(): void
    {
        $year = AcademicYear::factory()->create(['is_current' => false]);

        \Illuminate\Support\Facades\Cache::put(
            \App\Services\Core\CacheService::KEY_ACADEMIC_YEARS_RECENT.':admin',
            ['stale'],
            3600
        );

        $this->actingAs($this->admin)->delete(route('admin.academic-years.destroy', $year));

        $this->assertNull(\Illuminate\Support\Facades\Cache::get(\App\Services\Core\CacheService::KEY_ACADEMIC_YEARS_RECENT.':admin'));
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Build a valid academic year payload for store/update requests.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-06-30',
            'is_current' => false,
            'semesters' => [
                [
                    'name' => 'Semestre 1',
                    'start_date' => '2025-09-01',
                    'end_date' => '2026-01-31',
                ],
                [
                    'name' => 'Semestre 2',
                    'start_date' => '2026-02-01',
                    'end_date' => '2026-06-30',
                ],
            ],
        ], $overrides);
    }
}
