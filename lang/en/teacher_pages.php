<?php

declare(strict_types=1);

return [
    'assessments' => [
        'index' => [
            'title' => 'My Assessments',
            'heading' => 'Assessments Management',
            'description' => 'Create and manage your assessments (exams, assignments, projects, etc.)',
            'create_button' => 'Create Assessment',
            'no_assessments' => 'No assessments yet',
            'create_first' => 'Create your first assessment',
        ],

        'create' => [
            'title' => 'Create Assessment',
            'heading' => 'New Assessment',
            'description' => 'Create a new assessment with questions',
            'basic_info' => 'Basic Information',
            'questions_section' => 'Questions',
            'add_question' => 'Add Question',
            'add_first_question' => 'Add First Question',
            'no_questions' => 'No questions added yet',
            'question_number' => 'Question #:number',
            'expand' => 'Expand',
            'collapse' => 'Collapse',
            'add_choice' => 'Add Choice',
            'submit' => 'Create Assessment',
        ],

        'edit' => [
            'title' => 'Edit Assessment',
            'heading' => 'Edit Assessment',
            'description' => 'Modify assessment information and questions',
            'basic_info' => 'Basic Information',
            'questions_section' => 'Questions',
            'add_question' => 'Add Question',
            'add_first_question' => 'Add First Question',
            'no_questions' => 'No questions added yet',
            'question_number' => 'Question #:number',
            'expand' => 'Expand',
            'collapse' => 'Collapse',
            'add_choice' => 'Add Choice',
            'submit' => 'Update Assessment',
        ],

        'show' => [
            'title' => 'Assessment Details',
            'subtitle' => 'View assessment information and statistics',
            'basic_info' => 'Basic Information',
            'type' => 'Type',
            'date' => 'Date',
            'duration' => 'Duration',
            'coefficient' => 'Coefficient',
            'total_assigned' => 'Students Assigned',
            'completed' => 'Completed',
            'completion_rate' => 'completion',
            'average_score' => 'Average Score',
            'highest' => 'Highest',
            'lowest' => 'Lowest',
            'questions' => 'Questions',
            'no_questions' => 'No questions in this assessment',
            'question_number' => 'Question #:number',
            'points' => 'pts',
            'choices' => 'choices',
            'manage_assignments' => 'Manage Student Assignments',
            'view_grading' => 'View Grading',
        ],

        'form' => [
            'class_subject' => 'Class - Subject',
            'title' => 'Title',
            'description' => 'Description',
            'type' => 'Type',
            'coefficient' => 'Coefficient',
            'duration' => 'Duration (minutes)',
            'assessment_date' => 'Assessment Date',
            'publish_immediately' => 'Publish immediately',
            'is_published' => 'Published',
            'question_content' => 'Question',
            'question_type' => 'Question Type',
            'points' => 'Points',
            'choices' => 'Choices',
            'choice_placeholder' => 'Enter choice text',
        ],

        'table' => [
            'title' => 'Title',
            'type' => 'Type',
            'date' => 'Date',
            'duration' => 'Duration',
            'coefficient' => 'Coefficient',
            'status' => 'Status',
            'completion' => 'Completion',
        ],

        'filters' => [
            'search_placeholder' => 'Search by title, class, or subject...',
            'all_types' => 'All Types',
            'all_status' => 'All Status',
            'published' => 'Published',
            'draft' => 'Draft',
        ],

        'types' => [
            'devoir' => 'Homework',
            'examen' => 'Exam',
            'tp' => 'Practical Work',
            'controle' => 'Quiz',
            'projet' => 'Project',
        ],

        'question_types' => [
            'one_choice' => 'Single Choice',
            'multiple' => 'Multiple Choice',
            'text' => 'Text Answer',
            'boolean' => 'True/False',
        ],

        'card' => [
            'questions' => 'questions',
            'coefficient' => 'Coef',
            'completion' => 'Completion',
        ],

        'minutes' => 'min',
        'confirm_delete' => 'Are you sure you want to delete this assessment? This action cannot be undone.',
        'publish' => 'Publish',
        'unpublish' => 'Unpublish',
        'duplicate' => 'Duplicate',
    ],
];
