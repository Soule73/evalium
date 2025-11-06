<?php

namespace Tests\Unit\Services\Admin;

use Tests\TestCase;
use App\Models\Level;
use App\Models\Group;
use App\Services\Admin\LevelService;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LevelServiceTest extends TestCase
{
    use RefreshDatabase;

    private LevelService $levelService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->levelService = app(LevelService::class);
    }

    #[Test]
    public function it_can_get_levels_with_pagination(): void
    {
        // Créer des niveaux
        Level::factory()->count(15)->create();

        $result = $this->levelService->getLevelsWithPagination([
            'per_page' => 10,
        ]);

        $this->assertCount(10, $result->items());
        $this->assertEquals(15, $result->total());
    }

    #[Test]
    public function it_can_filter_levels_by_search(): void
    {
        Level::factory()->create(['name' => 'Licence 1', 'code' => 'L1']);
        Level::factory()->create(['name' => 'Licence 2', 'code' => 'L2']);
        Level::factory()->create(['name' => 'Master 1', 'code' => 'M1']);

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
        Level::factory()->create(['is_active' => true]);
        Level::factory()->create(['is_active' => true]);
        Level::factory()->create(['is_active' => false]);

        // Filtrer par actif
        $activeResult = $this->levelService->getLevelsWithPagination([
            'status' => '1',
            'per_page' => 10,
        ]);

        $this->assertCount(2, $activeResult->items());
        $activeItems = collect($activeResult->items());
        $this->assertTrue($activeItems->every(fn($level) => $level->is_active));

        // Filtrer par inactif
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

        $this->assertInstanceOf(Level::class, $level);
        $this->assertEquals('Licence 1', $level->name);
        $this->assertEquals('L1', $level->code);
        $this->assertTrue($level->is_active);
        $this->assertDatabaseHas('levels', ['code' => 'L1']);
    }

    #[Test]
    public function it_invalidates_cache_when_creating_level(): void
    {
        // Mettre une valeur en cache
        Cache::put('groups_active_with_levels', 'test_value', 60);
        $this->assertEquals('test_value', Cache::get('groups_active_with_levels'));

        // Créer un niveau
        $this->levelService->createLevel([
            'name' => 'Test Level',
            'code' => 'TL',
            'ordre' => 1,
        ]);

        // Vérifier que le cache est invalidé
        $this->assertNull(Cache::get('groups_active_with_levels'));
    }

    #[Test]
    public function it_can_update_level(): void
    {
        $level = Level::factory()->create([
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
        $level = Level::factory()->create();

        Cache::put('groups_active_with_levels', 'test_value', 60);

        $this->levelService->updateLevel($level, ['name' => 'Updated Name']);

        $this->assertNull(Cache::get('groups_active_with_levels'));
    }

    #[Test]
    public function it_cannot_delete_level_with_groups(): void
    {
        $level = Level::factory()->create();
        Group::factory()->create(['level_id' => $level->id]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(__('messages.level_cannot_delete_with_groups'));

        $this->levelService->deleteLevel($level);

        // Vérifier que le niveau n'a pas été supprimé
        $this->assertDatabaseHas('levels', ['id' => $level->id]);
    }

    #[Test]
    public function it_can_delete_level_without_groups(): void
    {
        $level = Level::factory()->create();

        $result = $this->levelService->deleteLevel($level);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('levels', ['id' => $level->id]);
    }

    #[Test]
    public function it_invalidates_cache_when_deleting_level(): void
    {
        $level = Level::factory()->create();

        Cache::put('groups_active_with_levels', 'test_value', 60);

        $this->levelService->deleteLevel($level);

        $this->assertNull(Cache::get('groups_active_with_levels'));
    }

    #[Test]
    public function it_can_toggle_level_status(): void
    {
        $level = Level::factory()->create(['is_active' => true]);

        // Désactiver
        $toggledLevel = $this->levelService->toggleStatus($level);
        $this->assertFalse($toggledLevel->is_active);

        // Réactiver
        $toggledAgain = $this->levelService->toggleStatus($toggledLevel);
        $this->assertTrue($toggledAgain->is_active);
    }

    #[Test]
    public function it_invalidates_cache_when_toggling_status(): void
    {
        $level = Level::factory()->create(['is_active' => true]);

        Cache::put('groups_active_with_levels', 'test_value', 60);

        $this->levelService->toggleStatus($level);

        $this->assertNull(Cache::get('groups_active_with_levels'));
    }

    #[Test]
    public function it_loads_groups_count_when_getting_levels(): void
    {
        $level = Level::factory()->create();
        Group::factory()->count(3)->create(['level_id' => $level->id, 'is_active' => true]);
        Group::factory()->create(['level_id' => $level->id, 'is_active' => false]);

        $result = $this->levelService->getLevelsWithPagination(['per_page' => 10]);

        $items = collect($result->items());
        $fetchedLevel = $items->first();
        $this->assertEquals(4, $fetchedLevel->groups_count);
        $this->assertEquals(3, $fetchedLevel->active_groups_count);
    }
}
