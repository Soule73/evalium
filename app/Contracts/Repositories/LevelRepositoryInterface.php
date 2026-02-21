<?php

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LevelRepositoryInterface
{
    /**
     * Get paginated levels with optional search and status filters.
     */
    public function getLevelsWithPagination(array $params): LengthAwarePaginator;
}
