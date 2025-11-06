<?php

namespace App\Services\Admin;

use App\Models\Level;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Level Service - Handle level CRUD operations and cache management
 *
 * Single Responsibility: Manage academic level lifecycle and related cache
 * Dependencies: Level Model
 */
class LevelService
{
    /**
     * Cache key for active groups with levels
     */
    private const CACHE_KEY_GROUPS = 'groups_active_with_levels';

    /**
     * Get paginated list of levels with filtering
     *
     * @param  array  $params  Filter criteria (search, status, per_page)
     */
    public function getLevelsWithPagination(array $params): LengthAwarePaginator
    {
        $perPage = $params['per_page'] ?? 10;
        $search = $params['search'] ?? null;
        $status = $params['status'] ?? null;

        $query = $this->buildLevelQuery($search, $status);

        return $query->ordered()->paginate($perPage);
    }

    /**
     * Create a new level
     *
     * @param  array  $data  Level data (name, code, description, ordre, is_active)
     */
    public function createLevel(array $data): Level
    {
        try {
            $level = Level::create($data);

            $this->invalidateGroupsCache();

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

            $this->invalidateGroupsCache();

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
     * Delete a level (if no groups are associated)
     *
     * @param  Level  $level  Level to delete
     *
     * @throws \Exception If level has associated groups
     */
    public function deleteLevel(Level $level): bool
    {
        if ($level->groups()->count() > 0) {
            throw new \Exception(__('messages.level_cannot_delete_with_groups'));
        }

        try {
            $level->delete();

            $this->invalidateGroupsCache();

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

            $this->invalidateGroupsCache();

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
     * Invalidate groups cache when levels are modified
     */
    private function invalidateGroupsCache(): void
    {
        Cache::forget(self::CACHE_KEY_GROUPS);
    }

    /**
     * Build query for levels with filters
     *
     * @param  string|null  $search  Search term
     * @param  string|null  $status  Status filter (null, '0', '1')
     */
    private function buildLevelQuery(?string $search, ?string $status): Builder
    {
        $query = Level::query()->withCount(['groups', 'activeGroups']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status !== null && $status !== '') {
            $query->where('is_active', $status === '1');
        }

        return $query;
    }
}
