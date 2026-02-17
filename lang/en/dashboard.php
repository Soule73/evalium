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
        'assessments_created' => 'Assessments created',
        'questions_created' => 'Questions created',
        'students_evaluated' => 'Students evaluated',
        'average_score' => 'Average score',
        'recent_assessments' => 'Recent assessments',
        'recent_assessments_subtitle' => 'Manage your assessments and track your students\' performance.',
        'create_assessment' => 'New assessment',
        'view_all_assessments' => 'View all assessments',
        'total_classes' => 'Total classes',
        'total_subjects' => 'Total subjects',
        'total_assessments' => 'Total assessments',
        'upcoming_assessments' => 'Upcoming assessments',
        'active_assignments' => 'Active assignments',
        'active_assignments_subtitle' => 'Your current class and subject assignments.',
        'view_all_classes' => 'View all classes',
        'no_active_assignments' => 'No active assignments at the moment.',
        'past_assessments' => 'Past assessments',
        'past_assessments_subtitle' => 'Assessments that have already taken place.',
        'no_past_assessments' => 'No past assessments.',
        'no_recent_assessments' => 'No recent assessments.',
        'upcoming_assessments_section' => 'Upcoming assessments',
        'upcoming_assessments_subtitle' => 'Assessments scheduled for the near future.',
        'no_upcoming_assessments' => 'No upcoming assessments.',
        'assessment_title' => 'Title',
        'class' => 'Class',
        'subject' => 'Subject',
        'scheduled_at' => 'Scheduled at',
    ],

    // Student Dashboard
    'student' => [
        'greeting' => 'Hello, :name!',
        'total_assessments' => 'Total Assessments',
        'pending_assessments' => 'Pending assessments',
        'completed_assessments' => 'Completed assessments',
        'graded_assessments' => 'Graded assessments',
        'average_score' => 'Average score',
        'assigned_assessments' => 'Assigned assessments',
        'view_my_assessments' => 'View my assessments',
        'view_all_assessments' => 'View all assessments',
        'subjects_overview' => 'Subjects Overview',
        'completed' => 'completed',
        'status' => [
            'not_submitted' => 'Not Submitted',
            'submitted' => 'Submitted',
            'graded' => 'Graded',
        ],
        'table' => [
            'title' => 'Title',
            'subject' => 'Subject',
            'submitted_at' => 'Submitted At',
            'status' => 'Status',
            'actions' => 'Actions',
            'view' => 'View',
        ],
        'no_assessments' => 'No Assessments',
        'no_assessments_subtitle' => 'You have no assessments assigned yet.',
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
