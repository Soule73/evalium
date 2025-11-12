<?php

declare(strict_types=1);

return [
    // Page titles
    'page_titles' => [
        'index' => 'Exams',
        'create' => 'Create Exam',
        'edit' => 'Edit Exam',
        'show' => 'Exam Details',
        'assign' => 'Assign Exam',
        'assignments' => 'Assignments',
        'group_details' => 'Group Details',
        'student_results' => 'Results - :student - :exam',
        'student_review' => 'Correction - :student - :exam',
        'management' => 'Exam Management',
    ],

    // Index page
    'index' => [
        'title' => 'Exam Management',
        'subtitle' => 'Create, manage and assign your exams to students.',
        'new_exam' => 'New Exam',
    ],

    // Create page
    'create' => [
        'title' => 'Create Exam',
        'subtitle' => 'Configure your exam settings and add your questions.',
        'cancel' => 'Cancel',
        'submit' => 'Create Exam',
        'at_least_one_question' => 'You must add at least one question',
        'question_content_required' => 'Question :number must have content',
        'validation_errors' => 'Validation errors',
    ],

    // Edit page
    'edit' => [
        'title' => 'Edit Exam',
        'subtitle' => 'Modify your exam settings and adjust your questions. Total: :points point:plural',
        'cancel' => 'Cancel',
        'submit' => 'Edit Exam',
    ],

    // Show page
    'show' => [
        'title' => 'Exam Details and Management',
        'toggle_active' => 'Active',
        'toggle_inactive' => 'Inactive',
        'duplicate' => 'Duplicate',
        'edit' => 'Edit',
        'view_assignments' => 'View Assignments',
        'questions' => 'Questions',
        'total_points' => 'Total Points',
        'duration' => 'Duration',
        'assigned_groups' => 'Assigned Groups',
        'concerned_students' => 'Concerned Students',
        'assigned_groups_section' => 'Assigned Groups',
        'assigned_groups_subtitle' => ':count group(s) have access to this exam',
        'exam_questions' => 'Exam Questions',
        'no_questions' => 'No questions added to this exam.',
        'add_questions' => 'Add Questions',
        'answer_choices' => 'Answer Choices:',
        'free_text_info' => 'Free text question - manual correction required',
        'duplicate_modal_title' => 'Duplicate Exam',
        'duplicate_modal_message' => 'Do you really want to duplicate the exam ":title"? A copy will be created with all questions.',
        'duplicate_confirm' => 'Duplicate',
        'remove_group_title' => 'Remove Group',
        'remove_group_message' => 'Are you sure you want to remove exam ":exam" from group ":group"?',
        'remove' => 'Remove',
        'view_details' => 'View Details',
    ],

    // Assign page
    'assign' => [
        'title' => 'Assign Exam: :title',
        'exam_info' => 'Exam Information',
        'exam_info_subtitle' => 'Details of the exam to be assigned',
        'cancel' => 'Cancel',
        'duration_label' => 'Duration: :duration minutes',
        'assigned_groups_title' => 'Assigned Groups',
        'assigned_groups_subtitle' => ':count group(s) have access to this exam',
        'no_assigned_groups_title' => 'No Assigned Groups',
        'no_assigned_groups_subtitle' => 'No group has access to this exam yet',
        'select_groups_title' => 'Assign Exam to Groups',
        'select_groups_subtitle' => 'Select the groups you want to give access to this exam',
        'assign_to_groups' => 'Assign to :count group:plural',
        'active_students' => ':count active student(s)',
        'search_placeholder' => 'Search for a group...',
        'all_assigned' => 'All Groups Assigned',
        'all_assigned_subtitle' => 'All active groups already have access to this exam',
        'no_groups_found' => 'No Groups Found',
        'no_groups_found_subtitle' => 'No groups match your search criteria.',
        'reset_search' => 'Reset Search',
        'confirm_title' => 'Confirm Assignment',
        'confirm_message' => 'You are about to assign this exam to :groups group(s), which will give access to approximately :students student(s).',
        'confirm_button' => 'Confirm Assignment',
        'exam_label' => 'Exam:',
        'duration_info' => 'Duration:',
        'minutes' => 'minutes',
    ],

    // Assignments page
    'assignments' => [
        'title' => 'Exam Assignments',
        'assign_new_groups' => 'Assign to New Groups',
        'groups_list_title' => 'Assigned Groups (:count)',
        'groups_list_subtitle' => 'List of groups with access to this exam',
        'no_groups_title' => 'No Groups Assigned',
        'no_groups_message' => 'Start by assigning this exam to student groups.',
        'assign_groups_button' => 'Assign Groups',
    ],

    // Group Details page
    'group_details' => [
        'title' => ':group - :exam',
        'subtitle' => 'Exam details: :exam',
        'back' => 'Back',
        'level' => 'Level',
        'active_students' => 'Active Students',
        'exam_duration' => 'Exam Duration',
        'questions_count' => 'Number of Questions',
        'not_defined' => 'Not Defined',
        'minutes' => 'minutes',
        'stats_title' => 'Group Statistics',
        'average_score' => 'Group Average Score',
        'students_title' => 'Group Students',
        'students_subtitle' => 'Complete list of students and their progress',
        'search_placeholder' => 'Search by name or email...',
        'no_students_title' => 'No Students in This Group',
        'no_students_subtitle' => 'This group does not contain any active students',
    ],

    // Student Results page
    'student_results' => [
        'title' => 'Results - :student - :exam',
        'copy_title' => ':student\'s Copy',
        'exam_active' => 'Exam Active',
        'exam_disabled' => 'Exam Disabled',
        'correct_exam' => 'Grade Exam',
        'edit_correction' => 'Edit Grading',
        'back_to_assignments' => 'Back to Assignments',
        'answers_detail' => 'Answer Details',
        'teacher_notes' => 'Notes',
    ],

    // Student Review page
    'student_review' => [
        'title' => 'Correction - :student - :exam',
        'correction_title' => ':student\'s Exam Correction',
        'correction_mode' => 'Correction Mode',
        'save_grades' => 'Save Grades',
        'saving' => 'Saving...',
        'back_to_assignments' => 'Back to Assignments',
        'auto_corrected_info' => 'Multiple choice questions are automatically graded but you can adjust grades if necessary.',
        'questions_correction' => 'Questions and Correction',
        'correction_summary' => 'Correction Summary',
        'total_score' => 'Total Score',
        'percentage' => 'Percentage',
        'status' => 'Status',
        'confirm_save_title' => 'Confirm Saving Grades',
        'confirm_save_message' => 'You are about to save the grades for :student\'s exam.',
        'teacher_notes_label' => 'Teacher Notes (optional)',
        'teacher_notes_placeholder' => 'Add your comments or observations about this submission...',
        'teacher_notes_help' => 'These notes will be visible to the student along with their results.',
        'cancel' => 'Cancel',
        'confirm_save' => 'Confirm and Save',
        'question_score_label' => 'Score for this question (max: :max points)',
        'auto_graded_info' => 'Automatically calculated grade - editable if needed',
        'manual_grading_required' => 'Manual grading required',
        'question_comment_label' => 'Comment for this question:',
        'question_comment_placeholder' => 'Add your comments for this answer...',
    ],

    // Common
    'common' => [
        'points' => 'point',
        'points_plural' => 'points',
        's' => 's',
        'group' => 'Group',
    ],
];
