<?php

declare(strict_types=1);

namespace App\Services\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Paginatable Trait
 *
 * Provides reusable pagination logic with query string preservation
 * and custom append data support.
 */
trait Paginatable
{
    /**
     * Paginate a query or relation with consistent defaults
     *
     * @param  Builder|Relation  $query  The query builder or relation to paginate
     * @param  array  $filters  Pagination filters (per_page, page, etc.)
     * @param  array  $appends  Additional data to append to pagination
     */
    protected function paginateWithFilters(
        Builder|Relation $query,
        array $filters = [],
        array $appends = []
    ): LengthAwarePaginator {
        $perPage = $filters['per_page'] ?? 10;
        $page = $filters['page'] ?? 1;
        $pageName = $filters['page_name'] ?? 'page';

        $paginator = $query->paginate(
            perPage: (int) $perPage,
            columns: ['*'],
            pageName: $pageName,
            page: (int) $page
        );

        $paginator->withQueryString();

        if (! empty($appends)) {
            $paginator->appends($appends);
        }

        return $paginator;
    }

    /**
     * Paginate with standard filter appends
     *
     * Automatically appends search and status filters commonly used in admin panels
     *
     * @param  Builder|Relation  $query  The query builder or relation to paginate
     * @param  array  $filters  Pagination and filter options
     */
    protected function paginateWithStandardFilters(
        Builder|Relation $query,
        array $filters = []
    ): LengthAwarePaginator {
        $perPage = $filters['per_page'] ?? 10;

        $appends = array_filter([
            'search' => $filters['search'] ?? null,
            'status' => $filters['status'] ?? null,
            'per_page' => $perPage !== 10 ? $perPage : null,
        ]);

        return $this->paginateWithFilters($query, $filters, $appends);
    }

    /**
     * Simple pagination without appends
     *
     * @param  Builder|Relation  $query  The query builder or relation
     * @param  int  $perPage  Items per page
     */
    protected function simplePaginate(
        Builder|Relation $query,
        int $perPage = 10
    ): LengthAwarePaginator {
        return $query->paginate($perPage)->withQueryString();
    }
}
