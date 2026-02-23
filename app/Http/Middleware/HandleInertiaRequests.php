<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;

/**
 * Middleware to handle Inertia.js requests.
 *
 * This class extends the base Middleware and is responsible for sharing
 * data between Laravel and Inertia.js, as well as handling specific
 * request logic required by Inertia.
 */
class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'permissions' => $user ? $user->getAllPermissions()->pluck('name')->toArray() : [],
                'roles' => $user ? $user->getRoleNames()->toArray() : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->pull('success'),
                'error' => fn () => $request->session()->pull('error'),
                'warning' => fn () => $request->session()->pull('warning'),
                'info' => fn () => $request->session()->pull('info'),
                'has_new_user' => fn () => $request->session()->pull('has_new_user'),
            ],
            'academic_year' => [
                'selected' => $this->getSelectedAcademicYear($request),
                'recent' => $this->getRecentAcademicYears($user),
            ],
            'locale' => app()->getLocale(),
            'language' => $this->getTranslations(),
            'notifications' => Inertia::lazy(fn () => [
                'unread_count' => $user?->unreadNotifications()->count() ?? 0,
            ]),
        ];
    }

    /**
     * Get the selected academic year from session or default to current.
     */
    protected function getSelectedAcademicYear(Request $request): ?array
    {
        $academicYearId = $request->session()->get('academic_year_id');

        if (! $academicYearId) {
            $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();

            return $currentYear ? $currentYear->toArray() : null;
        }

        $academicYear = \App\Models\AcademicYear::find($academicYearId);

        return $academicYear ? $academicYear->toArray() : null;
    }

    /**
     * Get the available academic years for the selector.
     *
     * For admins: current year + next year (if created) + up to 3 previous years.
     * For others: the 3 most recent years.
     */
    protected function getRecentAcademicYears(?\App\Models\User $user = null): array
    {
        $isAdmin = $user && $user->hasRole('admin');
        $cacheKey = $isAdmin
            ? \App\Services\Core\CacheService::KEY_ACADEMIC_YEARS_RECENT.':admin'
            : \App\Services\Core\CacheService::KEY_ACADEMIC_YEARS_RECENT;

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($isAdmin) {
            if (! $isAdmin) {
                return \App\Models\AcademicYear::orderBy('start_date', 'desc')
                    ->take(3)
                    ->get()
                    ->toArray();
            }

            $currentYear = \App\Models\AcademicYear::where('is_current', true)->first();

            if (! $currentYear) {
                return \App\Models\AcademicYear::orderBy('start_date', 'desc')
                    ->take(5)
                    ->get()
                    ->toArray();
            }

            $futureYear = \App\Models\AcademicYear::where('start_date', '>', $currentYear->end_date)
                ->orderBy('start_date')
                ->first();

            $previousYears = \App\Models\AcademicYear::where('end_date', '<', $currentYear->start_date)
                ->orderBy('start_date', 'desc')
                ->take(3)
                ->get();

            $years = collect([$futureYear, $currentYear])
                ->filter()
                ->merge($previousYears)
                ->unique('id')
                ->sortByDesc('start_date')
                ->values();

            return $years->toArray();
        });
    }

    /**
     * Get all translations for the current locale.
     *
     * Loads all PHP translation files recursively (including subdirectories)
     * and JSON translations. Subdirectory files are namespaced as
     * "subdirectory/filename" (e.g. lang/en/commons/ui.php → "commons/ui").
     *
     * @return array<string, mixed>
     */
    protected function getTranslations(): array
    {
        $locale = app()->getLocale();

        return \Illuminate\Support\Facades\Cache::remember("translations:{$locale}", 3600, function () use ($locale) {
            $translations = [];

            $langPath = lang_path($locale);

            if (is_dir($langPath)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($langPath, \FilesystemIterator::SKIP_DOTS)
                );

                foreach ($iterator as $file) {
                    if ($file->getExtension() !== 'php') {
                        continue;
                    }

                    $relativePath = ltrim(
                        str_replace([$langPath, '\\', '.php'], ['', '/', ''], $file->getPathname()),
                        '/'
                    );

                    $translations[$relativePath] = require $file->getPathname();
                }
            }

            $jsonFile = lang_path($locale.'.json');
            if (file_exists($jsonFile)) {
                $jsonTranslations = json_decode(file_get_contents($jsonFile), true);
                $translations = array_merge($translations, ['json' => $jsonTranslations]);
            }

            return $translations;
        });
    }
}
