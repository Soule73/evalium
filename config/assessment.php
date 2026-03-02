<?php

/**
 * Assessment Configuration
 *
 * Controls assessment-related behavior: development mode, timing enforcement,
 * and file upload constraints for student answers.
 *
 * Environment Variables:
 *  - EXAM_DEV_MODE              : Disable all exam security (default: false)
 *  - EXAM_GRACE_PERIOD_SECONDS  : Grace period after timer expires (default: 30)
 *  - EXAM_FILE_MAX_SIZE_KB      : Max upload size in KB (default: 10240 = 10MB)
 *  - EXAM_FILE_ALLOWED_EXTENSIONS : Comma-separated allowed extensions
 *
 * Consumed by:
 *  - App\Http\Controllers\Student\StudentAssessmentController (dev_mode)
 *  - App\Services\Student\StudentAssessmentService            (timing.grace_period_seconds)
 *  - App\Services\Student\FileAnswerService                   (file_uploads.*)
 *  - App\Http\Requests\Student\UploadFileAnswerRequest        (file_uploads.*)
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Development Mode
    |--------------------------------------------------------------------------
    |
    | When true, all security features are disabled for local development.
    | Must be false in production.
    |
    | @env EXAM_DEV_MODE
    | @see App\Http\Controllers\Student\StudentAssessmentController::take()
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
    | @env EXAM_GRACE_PERIOD_SECONDS
    | @see App\Services\Student\StudentAssessmentService::isAssessmentExpired()
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
    | @env EXAM_FILE_MAX_SIZE_KB
    | @env EXAM_FILE_ALLOWED_EXTENSIONS
    | @see App\Services\Student\FileAnswerService::validateFile()
    | @see App\Http\Requests\Student\UploadFileAnswerRequest::rules()
    |
    */

    'file_uploads' => [
        'max_size_kb' => (int) env('EXAM_FILE_MAX_SIZE_KB', 10240),
        'allowed_extensions' => array_filter(
            array_map('trim', explode(',', env('EXAM_FILE_ALLOWED_EXTENSIONS', 'pdf,doc,docx,xlsx,zip,jpg,jpeg,png')))
        ),
    ],
];
