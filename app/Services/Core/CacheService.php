<?php

namespace App\Services\Core;

use Illuminate\Support\Facades\Cache;

/**
 * Cache Service - Centralized cache management
 *
 * Provides consistent cache operations across the application.
 * Single Responsibility: Manage cache keys and TTL centrally.
 */
class CacheService
{
    private const DEFAULT_TTL = 3600;

    public const KEY_LEVELS_ALL = 'levels:all';

    public const KEY_SUBJECTS_ALL = 'subjects:all';

    public const KEY_ACADEMIC_YEARS_RECENT = 'academic_years:recent';

    public const KEY_ACADEMIC_YEAR_CURRENT = 'academic_year:current';

    public const KEY_ASSESSMENT_STATS = 'assessment:%d:stats';

    public const KEY_CLASS_STATS = 'class:%d:stats';

    public const KEY_STUDENT_PROGRESS = 'student:%d:progress';

    /**
     * Get cached data or execute callback
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return Cache::remember($key, $ttl ?? self::DEFAULT_TTL, $callback);
    }

    /**
     * Store data in cache
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        return Cache::put($key, $value, $ttl ?? self::DEFAULT_TTL);
    }

    /**
     * Get cached data
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    /**
     * Forget (invalidate) cached data
     */
    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Forget multiple keys matching pattern
     *
     * Note: Pattern matching requires direct redis access.
     * Falls back to no-op if not using redis store.
     */
    public function forgetPattern(string $pattern): void
    {
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = \Illuminate\Support\Facades\Redis::connection(
                    config('cache.stores.redis.connection', 'default')
                );

                $prefix = Cache::getStore()->getPrefix();
                $cursor = null;

                do {
                    [$cursor, $keys] = $redis->scan($cursor ?? '0', ['match' => $prefix.$pattern, 'count' => 100]);

                    foreach ($keys as $key) {
                        $cleanKey = str_replace($prefix, '', $key);
                        Cache::forget($cleanKey);
                    }
                } while ($cursor && $cursor !== '0');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('forgetPattern failed: '.$e->getMessage());
        }
    }

    /**
     * Generate assessment stats cache key
     */
    public function assessmentStatsKey(int $assessmentId): string
    {
        return sprintf(self::KEY_ASSESSMENT_STATS, $assessmentId);
    }

    /**
     * Generate class stats cache key
     */
    public function classStatsKey(int $classId): string
    {
        return sprintf(self::KEY_CLASS_STATS, $classId);
    }

    /**
     * Generate student progress cache key
     */
    public function studentProgressKey(int $studentId): string
    {
        return sprintf(self::KEY_STUDENT_PROGRESS, $studentId);
    }

    /**
     * Invalidate assessment-related caches
     */
    public function invalidateAssessmentCaches(int $assessmentId): void
    {
        $this->forget($this->assessmentStatsKey($assessmentId));
    }

    /**
     * Invalidate class-related caches
     */
    public function invalidateClassCaches(int $classId): void
    {
        $this->forget($this->classStatsKey($classId));
    }

    /**
     * Invalidate student-related caches
     */
    public function invalidateStudentCaches(int $studentId): void
    {
        $this->forget($this->studentProgressKey($studentId));
    }

    /**
     * Invalidate all levels caches
     */
    public function invalidateLevelsCaches(): void
    {
        $this->forget(self::KEY_LEVELS_ALL);
    }

    /**
     * Invalidate all subjects caches
     */
    public function invalidateSubjectsCaches(): void
    {
        $this->forget(self::KEY_SUBJECTS_ALL);
    }

    /**
     * Invalidate all academic years caches
     */
    public function invalidateAcademicYearsCaches(): void
    {
        $this->forget(self::KEY_ACADEMIC_YEARS_RECENT);
        $this->forget(self::KEY_ACADEMIC_YEAR_CURRENT);
    }
}
