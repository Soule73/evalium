<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Exam Security Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the security features during exam sessions.
    | You can disable security features in development environment.
    |
    */

    'security_enabled' => env('EXAM_SECURITY_ENABLED', env('APP_ENV') !== 'local' || ! env('APP_DEBUG', false)),

    /*
    |--------------------------------------------------------------------------
    | Development Mode
    |--------------------------------------------------------------------------
    |
    | When set to true, disables all exam security features for development.
    | This should NEVER be true in production.
    |
    */

    'dev_mode' => env('EXAM_DEV_MODE', env('APP_ENV') === 'local' && env('APP_DEBUG', false)),

    /*
    |--------------------------------------------------------------------------
    | Individual Security Features
    |--------------------------------------------------------------------------
    |
    | Fine-grained control over individual security features.
    | These are overridden by dev_mode when it's enabled.
    |
    */

    'features' => [
        'fullscreen_required' => env('EXAM_FULLSCREEN_REQUIRED', env('APP_ENV') !== 'local' || ! env('APP_DEBUG', false)),
        'tab_switch_detection' => env('EXAM_TAB_SWITCH_DETECTION', env('APP_ENV') !== 'local' || ! env('APP_DEBUG', false)),
        'dev_tools_detection' => env('EXAM_DEV_TOOLS_DETECTION', env('APP_ENV') !== 'local' || ! env('APP_DEBUG', false)),
        'copy_paste_prevention' => env('EXAM_COPY_PASTE_PREVENTION', env('APP_ENV') !== 'local' || ! env('APP_DEBUG', false)),
        'context_menu_disabled' => env('EXAM_CONTEXT_MENU_DISABLED', env('APP_ENV') !== 'local' || ! env('APP_DEBUG', false)),
        'print_prevention' => env('EXAM_PRINT_PREVENTION', env('APP_ENV') !== 'local' || ! env('APP_DEBUG', false)),
    ],

    /*
    |--------------------------------------------------------------------------
    | Timing Settings
    |--------------------------------------------------------------------------
    |
    | Settings related to exam timing and violations.
    |
    */

    'timing' => [
        'min_exam_duration_minutes' => env('EXAM_MIN_DURATION', 2),
        'auto_submit_on_time_end' => env('EXAM_AUTO_SUBMIT', true),
    ],
];
