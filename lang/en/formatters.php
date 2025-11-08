<?php

return [
    // Duration formatting
    'duration_min' => ':value min',
    'duration_hours' => ':value h',
    'duration_hours_min' => ':hours h :minutes min',

    // Exam status
    'exam_status_active' => 'Active',
    'exam_status_inactive' => 'Inactive',

    // Question types
    'question_type_multiple' => 'Multiple Choice',
    'question_type_one_choice' => 'Single Choice',
    'question_type_boolean' => 'True/False',
    'question_type_text' => 'Free Response',

    // User roles
    'role_admin' => 'Administrator',
    'role_super_admin' => 'Super Administrator',
    'role_teacher' => 'Teacher',
    'role_student' => 'Student',

    // Assignment statuses
    'assignment_graded' => 'Graded',
    'assignment_submitted' => 'Submitted',
    'assignment_not_started' => 'Not started',
    'assignment_not_assigned' => 'Not started',
    'assignment_all_statuses' => 'All statuses',

    // Security violations
    'security_tab_switch' => 'Tab switch detected',
    'security_fullscreen_exit' => 'Fullscreen exit detected',
    'security_violation_default' => 'Security violation detected',

    // Deadline warnings
    'deadline_exam_finished' => 'Exam finished',
    'deadline_minutes_remaining' => ':minutes minutes remaining',
    'deadline_hours_remaining' => ':hours hours remaining',
    'deadline_days_remaining' => ':days days remaining',
    'deadline_day_remaining' => ':days day remaining',

    // Relative time
    'relative_time_now' => 'Just now',
    'relative_time_minutes_ago' => ':minutes min ago',
    'relative_time_hours_ago' => ':hours h ago',
    'relative_time_days_ago' => ':days day ago|:days days ago',

    // Exam utils
    'partial_score_mcq' => 'Partial score (MCQ): :score / :total points',
    'score_format' => ':score / :total points',
    'correction_in_progress' => 'Correction in progress',
    'not_graded' => 'Not graded',

    // Validation
    'validation_required' => 'This field is required',
    'validation_min_length' => 'Minimum :min characters required',
    'validation_max_length' => 'Maximum :max characters allowed',
    'validation_invalid_format' => 'Invalid format',
    'validation_min_value' => 'The value must be at least :min',
    'validation_max_value' => 'The value cannot exceed :max',
    'validation_invalid_question_type' => 'Invalid question type',
    'validation_no_file_selected' => 'No file selected',
    'validation_file_type_not_allowed' => 'File type not allowed. Accepted types: :types',
    'validation_file_too_large' => 'File too large. Maximum size: :maxMB MB',

    'student_status_enrolled' => 'Enrolled',
    'student_status_left' => 'Left',

    'active' => 'Active',
    'inactive' => 'Inactive',
];
