<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait for standardized pagination across services
 *
 * Provides consistent pagination behavior with configurable defaults.
 * Apply standard filters (search, sort) automatically.
 */
trait PaginatesResources
{
  /**
   * Paginate a query with standard filters
   */
  protected function paginateQuery(
    Builder $query,
    array $filters = [],
    ?int $perPage = null
  ): LengthAwarePaginator {
    $perPage = $perPage ?? $this->getDefaultPerPage();

    $this->applyStandardFilters($query, $filters);

    return $query->paginate($perPage)->withQueryString();
  }

  /**
   * Apply standard filters (search, sort)
   */
  protected function applyStandardFilters(Builder $query, array $filters): void
  {
    if (! empty($filters['search'])) {
      $this->applySearchFilter($query, $filters['search']);
    }

    if (! empty($filters['sort'])) {
      $direction = $filters['direction'] ?? 'asc';
      $this->applySortFilter($query, $filters['sort'], $direction);
    }
  }

  /**
   * Get default per page value from config
   */
  protected function getDefaultPerPage(): int
  {
    $entityName = $this->getEntityName();

    $entityDefault = config("app.pagination.entities.{$entityName}.default");

    if ($entityDefault !== null) {
      return $entityDefault;
    }

    return config('app.pagination.default_per_page', 15);
  }

  /**
   * Get entity name for configuration lookup
   */
  protected function getEntityName(): string
  {
    return 'default';
  }

  /**
   * Apply search filter to query
   * Must be implemented by concrete service
   */
  abstract protected function applySearchFilter(Builder $query, string $search): void;

  /**
   * Apply sort filter to query
   */
  protected function applySortFilter(Builder $query, string $sort, string $direction = 'asc'): void
  {
    if (in_array($direction, ['asc', 'desc'])) {
      $query->orderBy($sort, $direction);
    }
  }
}
