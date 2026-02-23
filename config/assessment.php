<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Development Mode
    |--------------------------------------------------------------------------
    |
    | When true, all security features are disabled for local development.
    | Must be false in production.
    |
    */

    'dev_mode' => env('EXAM_DEV_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Timing Settings
    |--------------------------------------------------------------------------
    |
    | Backend-only timing settings used for server-side deadline enforcement.
    |
    */

    'timing' => [
        'grace_period_seconds' => env('EXAM_GRACE_PERIOD_SECONDS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    |
    | System-level constraints applied to all QuestionType::File answers.
    | These replace the former per-assessment max_files / max_file_size /
    | allowed_extensions columns.
    |
    */

    'file_uploads' => [
        'max_size_kb' => (int) env('EXAM_FILE_MAX_SIZE_KB', 10240),
        'allowed_extensions' => explode(',', env('EXAM_FILE_ALLOWED_EXTENSIONS', 'pdf,doc,docx,xlsx,zip,jpg,jpeg,png')),
    ],
];
