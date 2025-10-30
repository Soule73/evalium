<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
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
                'success' => fn() => $request->session()->pull('success'),
                'error' => fn() => $request->session()->pull('error'),
                'warning' => fn() => $request->session()->pull('warning'),
                'info' => fn() => $request->session()->pull('info'),
            ],
            'examConfig' => [
                'devMode' => config('exam.dev_mode', false),
                'securityEnabled' => config('exam.security_enabled', true),
                'features' => [
                    'fullscreenRequired' => config('exam.features.fullscreen_required', true),
                    'tabSwitchDetection' => config('exam.features.tab_switch_detection', true),
                    'devToolsDetection' => config('exam.features.dev_tools_detection', true),
                    'copyPastePrevention' => config('exam.features.copy_paste_prevention', true),
                    'contextMenuDisabled' => config('exam.features.context_menu_disabled', true),
                    'printPrevention' => config('exam.features.print_prevention', true),
                ],
                'timing' => [
                    'minExamDurationMinutes' => config('exam.timing.min_exam_duration_minutes', 2),
                    'autoSubmitOnTimeEnd' => config('exam.timing.auto_submit_on_time_end', true),
                ],
            ],
        ];
    }
}
