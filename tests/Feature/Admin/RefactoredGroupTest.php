<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\Group;
use App\Models\User;
use App\Models\Level;
use App\Services\Admin\GroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RefactoredGroupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_level_model_works()
    {
        $bts1 = Level::where('code', 'bts_1')->first();
        $master2 = Level::where('code', 'master_2')->first();

        $this->assertEquals('BTS 1ère année', $bts1->name);
        $this->assertEquals('Master 2ème année', $master2->name);

        $options = Level::options();
        $this->assertArrayHasKey($bts1->id, $options);
        $this->assertEquals('BTS 1ère année', $options[$bts1->id]);
    }

    public function test_group_service_creates_group_with_level()
    {
        $service = new GroupService();
        $bts1 = Level::where('code', 'bts_1')->first();

        $data = [
            'name' => 'Test BTS Group',
            'description' => 'Description test',
            'level_id' => $bts1->id,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addMonths(9)->format('Y-m-d'),
            'max_students' => 30,
            'academic_year' => '2025-2026',
            'is_active' => true,
        ];

        $group = $service->createGroup($data);

        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals($bts1->id, $group->level_id);

        // Recharger avec la relation level
        $group->load('level');
        $this->assertNotNull($group->level);
        $this->assertEquals('BTS 1ère année', $group->level->name);
    }

    public function test_group_service_pagination_works()
    {
        $licence1 = Level::where('code', 'licence_1')->first();
        $master1 = Level::where('code', 'master_1')->first();

        // Supprimer les groupes existants pour éviter les conflits
        Group::query()->delete();

        Group::factory()->count(5)->create(['level_id' => $licence1->id]);
        Group::factory()->count(3)->create(['level_id' => $master1->id]);

        $service = new GroupService();

        $result = $service->getGroupsWithPagination(['level_id' => $licence1->id], 10);

        $this->assertEquals(5, $result->total());

        foreach ($result->items() as $group) {
            $this->assertEquals($licence1->id, $group->level_id);
        }
    }

    public function test_group_service_assigns_students_following_solid_principles()
    {
        $group = Group::factory()->create(['max_students' => 2]);
        $students = User::factory()->count(3)->create();

        foreach ($students as $student) {
            $student->assignRole('student');
        }

        $service = new GroupService();

        $result = $service->assignStudentsToGroup($group, $students->pluck('id')->toArray());

        $this->assertEquals(2, $result['assigned_count']);
        $this->assertEquals(0, $result['already_assigned_count']);
        $this->assertCount(1, $result['errors']);
    }

    public function test_service_follows_dependency_injection_principle()
    {
        $this->assertInstanceOf(GroupService::class, app(GroupService::class));

        $service = app(GroupService::class);
        $formData = $service->getFormData();

        $this->assertArrayHasKey('levels', $formData);
        $this->assertArrayHasKey('available_students', $formData);
        $this->assertIsArray($formData['levels']);
    }
}
