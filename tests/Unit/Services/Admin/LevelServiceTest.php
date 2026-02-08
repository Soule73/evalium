<?php

namespace Tests\Unit\Services\Admin;

use App\Services\Admin\LevelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\InteractsWithTestData;

class LevelServiceTest extends TestCase
{
    use InteractsWithTestData, RefreshDatabase;

    private LevelService $levelService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        $this->levelService = app(LevelService::class);
    }

    #[Test]
    public function it_can_get_levels_with_pagination(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->createLevel();
        }

        $result = $this->levelService->getLevelsWithPagination([
            'per_page' => 10,
        ]);

        $this->assertCount(10, $result->items());
        $this->assertEquals(15, $result->total());
    }

    #[Test]
    public function it_can_filter_levels_by_search(): void
    {
        $this->createLevel(['name' => 'Licence 1', 'code' => 'L1']);
        $this->createLevel(['name' => 'Licence 2', 'code' => 'L2']);
        $this->createLevel(['name' => 'Master 1', 'code' => 'M1']);

        $result = $this->levelService->getLevelsWithPagination([
            'search' => 'Licence',
            'per_page' => 10,
        ]);

        $this->assertCount(2, $result->items());

        $items = collect($result->items());
        $this->assertTrue($items->every(
            fn($level) => str_contains($level->name, 'Licence')
        ));
    }

    #[Test]
    public function it_can_filter_levels_by_status(): void
    {
        $this->createLevel(['is_active' => true]);
        $this->createLevel(['is_active' => true]);
        $this->createLevel(['is_active' => false]);

        $activeResult = $this->levelService->getLevelsWithPagination([
            'status' => '1',
            'per_page' => 10,
        ]);

        $this->assertCount(2, $activeResult->items());
        $activeItems = collect($activeResult->items());
        $this->assertTrue($activeItems->every(fn($level) => $level->is_active));

        $inactiveResult = $this->levelService->getLevelsWithPagination([
            'status' => '0',
            'per_page' => 10,
        ]);

        $this->assertCount(1, $inactiveResult->items());
        $inactiveItems = collect($inactiveResult->items());
        $this->assertFalse($inactiveItems->first()->is_active);
    }

    #[Test]
    public function it_can_create_level(): void
    {
        $data = [
            'name' => 'Licence 1',
            'code' => 'L1',
            'description' => 'Première année de licence',
            'ordre' => 1,
            'is_active' => true,
        ];

        $level = $this->levelService->createLevel($data);

        $this->assertEquals('Licence 1', $level->name);
        $this->assertEquals('L1', $level->code);
        $this->assertTrue($level->is_active);
        $this->assertDatabaseHas('levels', ['code' => 'L1']);
    }

    #[Test]
    public function it_invalidates_cache_when_creating_level(): void
    {
        Cache::put('classes_active_with_levels', 'test_value', 60);
        $this->assertEquals('test_value', Cache::get('classes_active_with_levels'));

        $this->levelService->createLevel([
            'name' => 'Test Level',
            'code' => 'TL',
            'ordre' => 1,
        ]);

        $this->assertNull(Cache::get('classes_active_with_levels'));
    }

    #[Test]
    public function it_can_update_level(): void
    {
        $level = $this->createLevel([
            'name' => 'Old Name',
            'code' => 'OLD',
        ]);

        $updatedLevel = $this->levelService->updateLevel($level, [
            'name' => 'New Name',
            'code' => 'NEW',
        ]);

        $this->assertEquals('New Name', $updatedLevel->name);
        $this->assertEquals('NEW', $updatedLevel->code);
        $this->assertDatabaseHas('levels', [
            'id' => $level->id,
            'name' => 'New Name',
            'code' => 'NEW',
        ]);
    }

    #[Test]
    public function it_invalidates_cache_when_updating_level(): void
    {
        $level = $this->createLevel();

        Cache::put('classes_active_with_levels', 'test_value', 60);

        $this->levelService->updateLevel($level, ['name' => 'Updated Name']);

        $this->assertNull(Cache::get('classes_active_with_levels'));
    }

    #[Test]
    public function it_cannot_delete_level_with_groups(): void
    {
        $level = $this->createLevel();
        $this->createClassWithStudents(studentCount: 0, classAttributes: ['level_id' => $level->id]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(__('messages.level_cannot_delete_with_classes'));

        $this->levelService->deleteLevel($level);

        $this->assertDatabaseHas('levels', ['id' => $level->id]);
    }

    #[Test]
    public function it_can_delete_level_without_groups(): void
    {
        $level = $this->createLevel();

        $result = $this->levelService->deleteLevel($level);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('levels', ['id' => $level->id]);
    }

    #[Test]
    public function it_invalidates_cache_when_deleting_level(): void
    {
        $level = $this->createLevel();

        Cache::put('classes_active_with_levels', 'test_value', 60);

        $this->levelService->deleteLevel($level);

        $this->assertNull(Cache::get('classes_active_with_levels'));
    }

    #[Test]
    public function it_can_toggle_level_status(): void
    {
        $this->markTestSkipped('is_active column removed from levels table');

        $level = $this->createLevel([]);

        $toggledLevel = $this->levelService->toggleStatus($level);
        $this->assertFalse($toggledLevel->is_active);

        $toggledAgain = $this->levelService->toggleStatus($toggledLevel);
        $this->assertTrue($toggledAgain->is_active);
    }

    #[Test]
    public function it_invalidates_cache_when_toggling_status(): void
    {
        $level = $this->createLevel([]);

        Cache::put('classes_active_with_levels', 'test_value', 60);

        $this->levelService->toggleStatus($level);

        $this->assertNull(Cache::get('classes_active_with_levels'));
    }

    #[Test]
    public function it_loads_classes_count_when_getting_levels(): void
    {
        $level = $this->createLevel();

        $classNames = ['A', 'B', 'C', 'D'];
        foreach ($classNames as $name) {
            $this->createClassWithStudents(studentCount: 0, classAttributes: [
                'level_id' => $level->id,
                'name' => $name,
            ]);
        }

        $result = $this->levelService->getLevelsWithPagination(['per_page' => 10]);

        $items = collect($result->items());
        $fetchedLevel = $items->first();
        $this->assertEquals(4, $fetchedLevel->classes_count);
    }
}
