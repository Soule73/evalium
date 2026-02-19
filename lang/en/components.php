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
        'select_all' => 'Select All',
        'select_level' => 'Select Level',
        'level_placeholder' => 'Select a level',
    ],

    'choice_editor' => [
        'placeholders' => 'Enter your answer...',
        'simple' => 'Simple',
        'preview' => 'Preview',
        'markdown' => 'Markdown',
        'hide' => 'Hide',
        'preview_label' => 'Preview:',
        'no_content' => 'No content',
        'switch_simple' => 'Switch to simple editor',
        'switch_markdown' => 'Switch to Markdown editor',
        'hide_preview' => 'Hide preview',
        'show_preview' => 'Show preview',
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
        'title' => 'Exam Questions',
        'subtitle' => 'Add and configure your exam questions.',
        'add_question' => 'Add Question',
        'no_questions_title' => 'No questions added',
        'no_questions_subtitle' => 'Start by adding your first question to create the exam',
        'delete_confirm' => 'Delete',
        'delete_cancel' => 'Cancel',
        'delete_notice' => 'This action can be undone via the deletion history.',
        'history_button' => 'History (:count)',
    ],

    // DeleteHistoryModal
    'delete_history_modal' => [
        'title' => 'Deletion History',
        'clear_history' => 'Clear History',
        'no_items' => 'No deleted items',
        'questions_tab' => 'Questions',
        'choices_tab' => 'Choices',
        'no_questions' => 'No deleted questions',
        'no_choices' => 'No deleted choices',
        'restore' => 'Restore',
        'close' => 'Close',
        'deleted_on' => 'Deleted on',
        'point' => 'point',
        'points' => 'points',
        'correct_choice' => 'Correct choice',
        'incorrect_choice' => 'Incorrect choice',
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

    // StudentAssessmentAssignmentList
    'student_assessment_list' => [
        'title_unavailable' => 'Title unavailable',
        'pending' => 'Pending',
        'not_graded' => 'Not graded',
        'not_submitted' => 'Not submitted',
        'view' => 'View',
        'view_assessment' => 'View assessment',
        'submitted_on' => 'Submitted on',
        'assessment' => 'Assessment',
        'date' => 'Date',
        'duration' => 'Duration',
        'score' => 'Score',
        'status' => 'Status',
        'actions' => 'Actions',
        'search_assessment' => 'Search an assessment...',
        'search_admin' => 'Search by assessment title or student name...',
        'search_student' => 'Search by assessment title...',
        'no_assessment_assigned_title' => 'No assessment assigned',
        'no_assessment_assigned_admin' => 'No assessment has been assigned to students.',
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

    'assessment_list' => [
        'view_assessment' => 'View',
        'view_assessment_title' => 'View assessment',
        'assessment_label' => 'Assessment',
        'duration_label' => 'Duration',
        'class_label' => 'Class',
        'teacher_label' => 'Teacher',
        'status_label' => 'Status',
        'created_on' => 'Created on',
        'actions_label' => 'Actions',
        'status_published' => 'Published',
        'status_unpublished' => 'Unpublished',
        'search_placeholder' => 'Search by title or description...',
        'empty_title' => 'No assessments created',
        'empty_subtitle' => 'Start by creating your first assessment.',
        'empty_search_title' => 'No assessments found',
        'empty_search_subtitle' => 'Try modifying your search or filter criteria.',
        'reset_filters' => 'Reset filters',
        'all_classes' => 'All Classes',
    ],

    'assignment_list' => [
        'student' => 'Student',
        'status' => 'Status',
        'score' => 'Score',
        'submitted_at' => 'Submitted at',
        'actions' => 'Actions',
        'status_not_started' => 'Not started',
        'status_in_progress' => 'In progress',
        'status_pending_grading' => 'Pending grading',
        'status_graded' => 'Graded',
        'grade' => 'Grade',
        'edit_grade' => 'Edit grade',
        'view_result' => 'View result',
        'search_students' => 'Search students...',
        'no_students' => 'No students assigned',
        'no_students_description' => 'Students will appear here once enrolled in this class.',
        'allow_retry' => 'Allow Retry',
        'reopen_modal_title' => 'Reopen Assignment',
        'reopen_modal_message' => 'You are about to reopen the assessment for :student. The student will be able to continue from where they left off with only the remaining time.',
        'reopen_confirm' => 'Reopen',
        'reopen_reason_label' => 'Reason for reopening',
        'reopen_reason_placeholder' => 'e.g., Student experienced a power outage during the exam...',
        'reopen_error' => 'Failed to reopen the assignment',
    ],

    'assessment_header' => [
        'questions_count' => 'Questions',
        'created_on' => 'Created at',
        'duration' => 'Duration',
        'subject' => 'Subject',
        'class' => 'Class',
    ],

    'assessment_general_config' => [
        'title' => 'General Settings',
        'published_label' => 'Published',
        'assessment_title_label' => 'Assessment Title',
        'type_label' => 'Assessment Type',
        'type_homework' => 'Assignment',
        'type_exam' => 'Exam',
        'type_practical' => 'Practical Work',
        'type_quiz' => 'Quiz',
        'type_project' => 'Project',
        'type_assignment' => 'Assignment',
        'duration_label' => 'Duration (minutes)',
        'due_date_label' => 'Due Date',
        'delivery_mode_label' => 'Delivery Mode',
        'delivery_mode_supervised' => 'Supervised',
        'delivery_mode_homework' => 'Homework',
        'class_subject_label' => 'Class & Subject',
        'class_subject_placeholder' => 'Select class and subject',
        'scheduled_date_label' => 'Scheduled Date',
        'description_label' => 'Description',
        'description_placeholder' => 'Enter assessment description...',
        'description_help' => 'Markdown formatting supported',
        'options_title' => 'Options',
        'shuffle_questions_label' => 'Shuffle questions',
        'show_results_immediately_label' => 'Show results immediately',
        'show_correct_answers_label' => 'Reveal correct answers after grading',
        'allow_late_submission_label' => 'Allow late submission',
        'one_question_per_page_label' => 'One question per page',
        'file_upload_title' => 'File Upload Settings',
        'max_files_label' => 'Maximum number of files',
        'max_files_help' => 'Leave empty or 0 to disable file uploads',
        'max_file_size_label' => 'Maximum file size (KB)',
        'allowed_extensions_label' => 'Allowed extensions',
        'allowed_extensions_help' => 'Comma-separated list (e.g. pdf,docx,jpg)',
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

    'file_list' => [
        'file_name' => 'File Name',
        'file_type' => 'Type',
        'uploaded_at' => 'Uploaded At',
        'preview' => 'Preview',
        'download' => 'Download',
        'delete' => 'Delete',
        'close' => 'Close',
        'no_files' => 'No files attached.',
        'preview_not_available' => 'Preview is not available for this file type.',
        'type_image' => 'Image',
        'type_pdf' => 'PDF',
        'type_document' => 'Document',
        'type_archive' => 'Archive',
        'type_other' => 'Other',
    ],
];
