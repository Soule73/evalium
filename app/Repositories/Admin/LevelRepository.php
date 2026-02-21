<?php

namespace App\Repositories\Admin;

use App\Contracts\Repositories\LevelRepositoryInterface;
use App\Models\Level;
use App\Services\Traits\Paginatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Level Query Service - Handle all read operations for levels.
 *
 * Follows Single Responsibility Principle by separating query concerns
 * from business logic in LevelService.
 */
class LevelRepository implements LevelRepositoryInterface
{
    use Paginatable;

    /**
     * Get paginated list of levels with filtering.
     *
     * @param  array  $params  Filter criteria (search, status, per_page)
     */
    public function getLevelsWithPagination(array $params): LengthAwarePaginator
    {
        $perPage = $params['per_page'] ?? 10;
        $search = $params['search'] ?? null;
        $status = $params['status'] ?? null;

        $query = $this->buildLevelQuery($search, $status)->ordered();

        return $this->paginateQuery($query, $perPage);
    }

    /**
     * Build query for levels with filters.
     *
     * @param  string|null  $search  Search term
     * @param  string|null  $status  Status filter (null, '0', '1')
     */
    private function buildLevelQuery(?string $search, ?string $status): Builder
    {
        $query = Level::query()->withCount(['classes']);

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
