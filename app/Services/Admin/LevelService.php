<?php

namespace App\Services\Admin;

use App\Contracts\Services\LevelServiceInterface;
use App\Models\Level;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Level Service - Handle level CRUD operations and cache management
 *
 * Single Responsibility: Manage academic level lifecycle and related cache
 * Dependencies: Level Model
 */
class LevelService implements LevelServiceInterface
{
    /**
     * Cache key for active classes with levels
     */
    private const CACHE_KEY_CLASSES = 'classes_active_with_levels';

    /**
     * Create a new level
     *
     * @param  array  $data  Level data (name, code, description, ordre, is_active)
     */
    public function createLevel(array $data): Level
    {
        try {
            $level = Level::create($data);

            $this->invalidateClassesCache();

            return $level;
        } catch (\Exception $e) {
            Log::error('Failed to create level', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Update an existing level
     *
     * @param  Level  $level  Level to update
     * @param  array  $data  Updated level data
     */
    public function updateLevel(Level $level, array $data): Level
    {
        try {
            $level->update($data);

            $this->invalidateClassesCache();

            return $level->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to update level', [
                'level_id' => $level->id,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
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
        if ($level->classes()->count() > 0) {
            throw new \Exception(__('messages.level_cannot_delete_with_classes'));
        }

        try {
            $level->delete();

            $this->invalidateClassesCache();

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete level', [
                'level_id' => $level->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Toggle the active status of a level
     *
     * @param  Level  $level  Level to toggle
     */
    public function toggleStatus(Level $level): Level
    {
        try {
            $level->update([
                'is_active' => ! $level->is_active,
            ]);

            $this->invalidateClassesCache();

            return $level->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to toggle level status', [
                'level_id' => $level->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Invalidate classes cache when levels are modified
     */
    private function invalidateClassesCache(): void
    {
        Cache::forget(self::CACHE_KEY_CLASSES);
    }
}
