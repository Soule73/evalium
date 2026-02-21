<?php

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface UserRepositoryInterface
{
    /**
     * Get paginated users with optional role, status and search filters.
     */
    public function getUserWithPagination(array $filters, int $perPage, User $currentUser): LengthAwarePaginator;

    /**
     * Get roles available for assignment by the current user.
     */
    public function getAvailableRoles(User $currentUser): Collection;
}
