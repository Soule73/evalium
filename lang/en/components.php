<?php

return [
    // ConfirmationModal
    'confirmation_modal' => [
        'confirm' => 'Confirm',
        'cancel' => 'Cancel',
    ],

    // Select
    'select' => [
        'placeholder' => 'Select an option',
        'search_placeholder' => 'Search...',
        'no_option_found' => 'No option found',
    ],

    // Toast / FlashToastHandler
    'toast' => [
        'success' => 'Success',
        'error' => 'Error',
        'warning' => 'Warning',
        'info' => 'Information',
        'close' => 'Close',
    ],

    // Toggle
    'toggle' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    // RoleForm
    'role_form' => [
        'system_role_badge' => 'System Role',
        'system_role_notice' => 'This is a system role. You can only modify its permissions.',
        'role_name_label' => 'Role Name',
        'role_name_placeholder' => 'E.g.: moderator, editor...',
        'create_button' => 'Create Role',
        'creating' => 'Creating...',
        'cancel' => 'Cancel',
    ],

    // PermissionSelector
    'permission_selector' => [
        'label' => 'Permissions (:count selected)',
        'select_all' => 'Select All',
        'deselect_all' => 'Deselect All',
        'sync' => 'Synchronize',
    ],

    // QuestionsManager
    'questions_manager' => [
        'add_question' => 'Add Question',
        'no_questions_title' => 'No questions added',
        'no_questions_subtitle' => 'Start by adding your first question to create the exam',
        'delete_confirm' => 'Delete',
        'delete_cancel' => 'Cancel',
        'delete_notice' => 'This action can be undone via the deletion history.',
    ],

    // SortableQuestionItem
    'question_item' => [
        'question_statement' => 'Question Statement',
        'question_placeholder' => 'Enter your question here...',
        'question_help' => 'Enter your question statement clearly. You can use Markdown formatting.',
        'answer_options' => 'Answer Options',
        'add_option' => 'Add Option',
    ],

    // ExamGeneralConfig
    'exam_general_config' => [
        'title' => 'General Information',
        'active_label' => 'Active Exam',
        'exam_title_label' => 'Exam Title',
        'duration_label' => 'Duration (minutes)',
        'start_time_label' => 'Start Date and Time',
        'end_time_label' => 'End Date and Time',
        'description_label' => 'Exam Description',
        'description_placeholder' => 'Exam description...',
        'description_help' => 'Describe the objective and terms of this exam. You can use Markdown formatting.',
    ],

    // QuestionRenderer
    'question_renderer' => [
        'no_answer' => 'No answer provided',
        'student_answer_label' => 'Student\'s answer',
        'your_answer_label' => 'Your answer',
        'no_answer_student' => 'The student did not provide an answer for this question.',
        'no_answer_yours' => 'You did not provide an answer for this question.',
        'teacher_feedback' => 'Teacher\'s feedback:',
    ],

    // StudentExamAssignmentList
    'student_exam_list' => [
        'title_unavailable' => 'Title unavailable',
        'pending' => 'Pending',
        'not_graded' => 'Not graded',
        'not_submitted' => 'Not submitted',
        'view' => 'View',
        'view_exam' => 'View exam',
        'submitted_on' => 'Submitted on',
        'exam' => 'Exam',
        'date' => 'Date',
        'duration' => 'Duration',
        'score' => 'Score',
        'status' => 'Status',
        'actions' => 'Actions',
        'search_exam' => 'Search an exam...',
        'search_admin' => 'Search by exam title or student name...',
        'search_student' => 'Search by exam title...',
        'no_exam_assigned_title' => 'No exam assigned',
        'no_exam_assigned_admin' => 'No exam has been assigned to students.',
        'no_exam_assigned_student' => 'You currently have no exam assigned.',
        'no_exam_found_title' => 'No exam found',
        'no_exam_found_subtitle' => 'No exam matches your search or filter criteria.',
        'reset_filters' => 'Reset filters',
    ],

    // TakeQuestion
    'take_question' => [
        'points' => ':points point(s)',
        'multiple_choice' => 'Multiple Choice',
        'one_choice' => 'Single Choice',
        'boolean' => 'True/False',
        'text' => 'Text Answer',
        'true' => 'True',
        'false' => 'False',
        'your_answer_placeholder' => 'Type your answer here... (Markdown supported)',
        'your_answer_help' => 'You can use Markdown syntax to format your answer',
    ],

    // AlertSecurityViolation
    'alert_security_violation' => [
        'title' => 'Exam Terminated',
        'subtitle' => 'Your exam has been automatically terminated and submitted due to a security rule violation.',
        'violation_detected' => 'Violation detected: :reason',
        'teacher_notified' => 'Your teacher will be notified of this violation',
        'answers_saved' => 'Your answers were saved before termination',
        'will_be_contacted' => 'You will be contacted regarding the next steps',
        'back_to_exams' => 'Back to Exams',
    ],

    // ExamInfoSection
    'exam_info_section' => [
        'exam_label' => 'Exam',
        'description_label' => 'Description',
        'teacher_label' => 'Teacher/Creator',
        'student_label' => 'Student',
        'email_label' => 'Email',
        'submitted_on' => 'Submitted on',
        'duration_label' => 'Duration',
        'score_assigned' => 'Assigned Score',
        'score_pending' => 'Score (pending)',
        'score_label' => 'Score',
        'score_final' => 'Final Score',
        'percentage_label' => 'Percentage',
        'questions_label' => 'Questions',
        'status_label' => 'Status',
        'questions_count' => ':count questions',
        'pending_correction' => 'Pending correction',
        'finished' => 'Finished',
        'automatic_submission' => 'Automatic Submission',
        'automatic_submission_message' => 'This exam was submitted automatically',
        'violation_detected_label' => 'Violation detected: :violation',
    ],

    // ExamStatsCards
    'exam_stats_cards' => [
        'total_students' => 'Total Students',
        'total_assigned' => 'Total Assigned',
        'completed' => 'Completed',
        'in_progress' => 'In Progress',
        'not_started' => 'Not Started',
    ],

    // question_result_readonly
    'question_result_readonly' => [
        'your_answer_default' => 'Your answer:',
        'student_answer' => 'Student answer',
        'your_answer' => 'Your answer',
        'student_answer_incorrect' => 'Student answer (incorrect)',
        'your_answer_incorrect' => 'Your answer (incorrect)',
        'student_answer_correct' => 'Student answer (correct)',
        'your_answer_correct' => 'Your answer (correct)',
        'correct_answer' => 'Correct answer',
        'boolean_true' => 'True',
        'boolean_false' => 'False',
        'boolean_true_short' => 'T',
        'boolean_false_short' => 'F',
    ],

    // QuestionReadOnlySection
    'question_readonly_section' => [
        'correct' => 'Correct',
        'incorrect' => 'Incorrect',
    ],

    // questionOptions
    'question_options' => [
        'multiple_title' => 'Multiple Choice',
        'multiple_subtitle' => 'Multiple correct answers',
        'one_choice_title' => 'Single Choice',
        'one_choice_subtitle' => 'Only one correct answer',
        'boolean_title' => 'True/False',
        'boolean_subtitle' => 'Boolean question',
        'text_title' => 'Free Response',
        'text_subtitle' => 'Free text response',
    ],

    // ExamAssignmentColumns
    'exam_assignment_columns' => [
        'student_label' => 'Student',
        'name_unavailable' => 'Name unavailable',
        'email_unavailable' => 'Email unavailable',
        'status_label' => 'Status',
        'assigned_on' => 'Assigned on',
        'started_on' => 'Started on',
        'completed_on' => 'Completed on',
        'score_label' => 'Score',
        'actions_label' => 'Actions',
        'view_result' => 'View Result',
        'all_statuses' => 'All statuses',
        'not_started' => 'Not started',
        'submitted' => 'Submitted',
        'graded' => 'Graded',
    ],

    // GroupTableConfig
    'group_table_config' => [
        'group_label' => 'Group',
        'active_students_count' => ':count active student(s)',
        'actions_label' => 'Actions',
        'view_details' => 'View Details',
        'remove' => 'Remove',
        'search_placeholder' => 'Search for a group...',
        'empty_title' => 'No groups assigned',
        'empty_subtitle' => 'This exam is not yet assigned to any groups',
    ],

    // ExamHeader
    'exam_header' => [
        'questions_count' => ':count question|:count questions',
        'created_on' => 'Created on :date',
    ],

    // ExamList
    'exam_list' => [
        'view_exam' => 'View',
        'view_exam_title' => 'View exam',
        'exam_label' => 'Exam',
        'duration_label' => 'Duration',
        'status_label' => 'Status',
        'created_on' => 'Created on',
        'actions_label' => 'Actions',
        'status_active' => 'Active',
        'status_inactive' => 'Inactive',
        'search_placeholder' => 'Search by title or description...',
        'empty_title' => 'No exams created',
        'empty_subtitle' => 'Start by creating your first exam for your students.',
        'empty_search_title' => 'No exams found',
        'empty_search_subtitle' => 'Try modifying your search or filter criteria.',
        'reset_filters' => 'Reset filters',
    ],

    // FullscreenModal
    'fullscreen_modal' => [
        'title' => 'Full Screen Required',
        'description_line1' => 'For security reasons, this exam must be taken in full screen mode.',
        'description_line2' => 'Click the button below to enter full screen mode.',
        'button' => 'Enter Full Screen',
    ],

    // DataTable
    'datatable' => [
        'items_selected' => ':count item|:count items',
        'items_selected_suffix' => 'selected|selected',
        'deselect_all' => 'Deselect All',
        'select_all' => 'Select All',
        'select_item' => 'Select item :id',
        'reset_filters_default' => 'Reset Filters',
        'showing_records' => 'Showing :from to :to of :total results',
        'per_page' => 'Per page:',
        'previous' => 'Previous',
        'next' => 'Next',
    ],
];
