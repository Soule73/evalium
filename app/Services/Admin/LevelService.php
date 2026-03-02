<?php

namespace App\Services\Admin;

use App\Contracts\Services\LevelServiceInterface;
use App\Exceptions\LevelException;
use App\Models\Level;
use App\Services\Core\CacheService;
use Illuminate\Support\Facades\Cache;

/**
 * Level Service - Handle level CRUD operations and cache management
 *
 * Single Responsibility: Manage academic level lifecycle and related cache
 * Dependencies: Level Model, CacheService
 */
class LevelService implements LevelServiceInterface
{
    /**
     * Cache key for active classes with levels
     */
    private const CACHE_KEY_CLASSES = 'classes_active_with_levels';

    public function __construct(
        private readonly CacheService $cacheService,
    ) {}

    /**
     * Create a new level
     *
     * @param  array  $data  Level data (name, code, description, ordre, is_active)
     */
    public function createLevel(array $data): Level
    {
        $level = Level::create($data);

        $this->invalidateLevelCaches();

        return $level;
    }

    /**
     * Update an existing level
     *
     * @param  Level  $level  Level to update
     * @param  array  $data  Updated level data
     */
    public function updateLevel(Level $level, array $data): Level
    {
        $level->update($data);

        $this->invalidateLevelCaches();

        return $level->refresh();
    }

    /**
     * Delete a level (if no classes are associated)
     *
     * @param  Level  $level  Level to delete
     *
     * @throws \Exception If level has associated classes
     */
    public function deleteLevel(Level $level): bool
    {
        if ($level->classes()->exists()) {
            throw LevelException::hasClasses();
        }

        $level->delete();

        $this->invalidateLevelCaches();

        return true;
    }

    /**
     * Toggle the active status of a level
     *
     * @param  Level  $level  Level to toggle
     */
    public function toggleStatus(Level $level): Level
    {
        $level->update([
            'is_active' => ! $level->is_active,
        ]);

        $this->invalidateLevelCaches();

        return $level->refresh();
    }

    /**
     * Invalidate all level-related caches when levels are modified.
     *
     * @see CacheService::KEY_LEVELS_ALL  Used by ClassRepository and SubjectRepository for level dropdowns
     */
    private function invalidateLevelCaches(): void
    {
        Cache::forget(self::CACHE_KEY_CLASSES);
        $this->cacheService->invalidateLevelsCaches();
    }
}
