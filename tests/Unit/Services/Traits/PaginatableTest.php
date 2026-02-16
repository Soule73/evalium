<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Traits;

use App\Models\User;
use App\Services\Traits\Paginatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginatableTest extends TestCase
{
    use RefreshDatabase;

    private object $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new class
        {
            use Paginatable;

            public function exposePaginateWithFilters($query, $filters, $appends)
            {
                return $this->paginateWithFilters($query, $filters, $appends);
            }

            public function exposePaginateWithStandardFilters($query, $filters)
            {
                return $this->paginateWithStandardFilters($query, $filters);
            }

            public function exposePaginateQuery($query, $perPage = 10)
            {
                return $this->paginateQuery($query, $perPage);
            }
        };
    }

    public function test_paginate_with_filters_returns_paginator(): void
    {
        User::factory()->count(20)->create();

        $result = $this->service->exposePaginateWithFilters(
            User::query(),
            ['per_page' => 5, 'page' => 1],
            []
        );

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(5, $result->perPage());
        $this->assertEquals(20, $result->total());
    }

    public function test_paginate_with_filters_respects_per_page(): void
    {
        User::factory()->count(15)->create();

        $result = $this->service->exposePaginateWithFilters(
            User::query(),
            ['per_page' => 10],
            []
        );

        $this->assertEquals(10, $result->count());
    }

    public function test_paginate_with_filters_uses_default_per_page(): void
    {
        User::factory()->count(15)->create();

        $result = $this->service->exposePaginateWithFilters(
            User::query(),
            [],
            []
        );

        $this->assertEquals(10, $result->perPage());
    }

    public function test_paginate_with_filters_appends_data(): void
    {
        User::factory()->count(5)->create();

        $result = $this->service->exposePaginateWithFilters(
            User::query(),
            [],
            ['search' => 'test', 'status' => 'active']
        );

        $query = parse_url($result->url(1), PHP_URL_QUERY);
        parse_str($query, $params);

        $this->assertEquals('test', $params['search']);
        $this->assertEquals('active', $params['status']);
    }

    public function test_paginate_with_standard_filters_appends_non_null_filters(): void
    {
        User::factory()->count(5)->create();

        $result = $this->service->exposePaginateWithStandardFilters(
            User::query(),
            ['search' => 'john', 'status' => 'active']
        );

        $query = parse_url($result->url(1), PHP_URL_QUERY);
        parse_str($query, $params);

        $this->assertArrayHasKey('search', $params);
        $this->assertArrayHasKey('status', $params);
    }

    public function test_paginate_with_standard_filters_excludes_default_per_page(): void
    {
        User::factory()->count(5)->create();

        $result = $this->service->exposePaginateWithStandardFilters(
            User::query(),
            ['per_page' => 10]
        );

        $query = parse_url($result->url(1), PHP_URL_QUERY);
        parse_str($query, $params);

        $this->assertArrayNotHasKey('per_page', $params);
    }

    public function test_paginate_with_standard_filters_includes_custom_per_page(): void
    {
        User::factory()->count(30)->create();

        $result = $this->service->exposePaginateWithStandardFilters(
            User::query(),
            ['per_page' => 20]
        );

        $this->assertEquals(20, $result->perPage());

        $query = parse_url($result->url(1), PHP_URL_QUERY);
        parse_str($query, $params);

        $this->assertArrayHasKey('per_page', $params);
        $this->assertEquals('20', $params['per_page']);
    }

    public function test_simple_paginate_with_default_per_page(): void
    {
        User::factory()->count(15)->create();

        $result = $this->service->exposePaginateQuery(User::query());

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(10, $result->perPage());
    }

    public function test_simple_paginate_with_custom_per_page(): void
    {
        User::factory()->count(15)->create();

        $result = $this->service->exposePaginateQuery(User::query(), 5);

        $this->assertEquals(5, $result->perPage());
        $this->assertEquals(5, $result->count());
    }

    public function test_paginate_preserves_query_string(): void
    {
        User::factory()->count(15)->create();

        $result = $this->service->exposePaginateWithFilters(
            User::query(),
            ['per_page' => 5],
            []
        );

        $this->assertStringContainsString('page=', $result->url(2));
    }
}
