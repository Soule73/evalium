<?php

declare(strict_types=1);

return [
    // Page titles
    'title' => [
        'admin' => 'Administrator dashboard',
        'teacher' => 'Teacher dashboard',
        'student' => 'Student dashboard',
        'unified' => 'Dashboard',
    ],

    // Admin Dashboard
    'admin' => [
        'total_users' => 'Total users',
        'students' => 'Students',
        'teachers' => 'Teachers',
    ],

    // Teacher Dashboard
    'teacher' => [
        'exams_created' => 'Exams created',
        'questions_created' => 'Questions created',
        'students_evaluated' => 'Students evaluated',
        'average_score' => 'Average score',
        'recent_exams' => 'Recent exams',
        'recent_exams_subtitle' => 'Manage your exams and track your students\' performance.',
        'create_exam' => 'Create exam',
        'view_all_exams' => 'View all exams',
    ],

    // Student Dashboard
    'student' => [
        'greeting' => 'Hello, :name!',
        'total_exams' => 'Total Exams',
        'pending_exams' => 'Pending exams',
        'completed_exams' => 'Completed exams',
        'average_score' => 'Average score',
        'assigned_exams' => 'Assigned exams',
        'view_my_exams' => 'View my exams',
        'view_all_exams' => 'View all exams',
    ],

    // Unified Dashboard
    'unified' => [
        'my_account' => 'My account',
        'name' => 'Name',
        'email' => 'Email',
        'permissions' => 'Permissions',
        'active_permissions' => ':count active permissions',
    ],
];
