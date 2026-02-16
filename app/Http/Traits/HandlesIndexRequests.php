<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;

/**
 * Trait for standardized index request handling in controllers
 *
 * Provides consistent parameter extraction and validation for list endpoints.
 */
trait HandlesIndexRequests
{
    /**
     * Extract standard index parameters from request
     */
    protected function extractIndexParams(Request $request, array $allowedFilters = []): array
    {
        $filters = $request->only($allowedFilters);
        $perPage = $this->getPerPageFromRequest($request);

        return [
            'filters' => $filters,
            'per_page' => $perPage,
        ];
    }

    /**
     * Get per page value from request with validation
     */
    protected function getPerPageFromRequest(Request $request, ?int $max = null): int
    {
        $defaultPerPage = config('app.pagination.default_per_page', 15);
        $maxPerPage = $max ?? config('app.pagination.max_per_page', 100);

        $perPage = $request->input('per_page', $defaultPerPage);

        return min(max((int) $perPage, 1), $maxPerPage);
    }

    /**
     * Extract filters with default values
     */
    protected function extractFiltersWithDefaults(Request $request, array $defaults = []): array
    {
        $filters = array_merge($defaults, $request->only(array_keys($defaults)));

        return array_filter($filters, fn ($value) => $value !== null && $value !== '');
    }

    /**
     * Build filter array for service layer
     */
    protected function buildServiceFilters(Request $request, array $allowedFilters): array
    {
        $filters = $request->only($allowedFilters);

        return array_filter($filters, function ($value) {
            return $value !== null && $value !== '' && $value !== [];
        });
    }
}
