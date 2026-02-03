<?php

declare(strict_types=1);

return [
  'page_titles' => [
    'index' => 'Assessments',
    'create' => 'Create Assessment',
    'edit' => 'Edit Assessment',
    'show' => 'Assessment Details',
    'assign' => 'Assign Assessment',
    'assignments' => 'Assignments',
    'class_details' => 'Class Details',
    'student_results' => 'Results - :student - :assessment',
    'student_review' => 'Correction - :student - :assessment',
    'management' => 'Assessment Management',
  ],

  'index' => [
    'title' => 'Assessment Management',
    'subtitle' => 'Create, manage and assign your assessments to classes.',
    'new_assessment' => 'New Assessment',
  ],

  'create' => [
    'title' => 'Create Assessment',
    'subtitle' => 'Configure your assessment settings and add your questions.',
    'cancel' => 'Cancel',
    'submit' => 'Create Assessment',
  ],

  'edit' => [
    'title' => 'Edit Assessment',
    'subtitle' => 'Modify your assessment settings and questions.',
    'subtitle_with_points' => ':points point:plural total',
    'cancel' => 'Cancel',
    'submit' => 'Save Changes',
  ],

  'common' => [
    's' => 's',
    'subtitle' => 'View and manage your assessment.',
    'edit_button' => 'Edit Assessment',
    'delete_button' => 'Delete Assessment',
    'assign_button' => 'Assign to Classes',
    'duplicate_button' => 'Duplicate',
    'view_results_button' => 'View Results',
    'view_assignments' => 'View Assignments',
    'duplicate' => 'Duplicate',
    'edit' => 'Edit',
    'toggle_published' => 'Published',
    'toggle_unpublished' => 'Unpublished',
    'questions' => 'Questions',
    'total_points' => 'Total Points',
    'duration' => 'Duration',
    'assigned_classes' => 'Assigned Classes',
    'concerned_students' => 'Concerned Students',
    'assigned_classes_section' => 'Assigned Classes',
    'assigned_classes_subtitle' => ':count classes assigned',
    'assessment_questions' => 'Assessment Questions',
    'no_questions' => 'No questions added yet',
    'add_questions' => 'Add Questions',
    'answer_choices' => 'Answer Choices',
    'free_text_info' => 'Free text answer expected',
    'duplicate_modal_title' => 'Duplicate Assessment',
    'duplicate_modal_message' => 'Create a copy of ":title"?',
    'duplicate_confirm' => 'Duplicate',
    'tabs' => [
      'overview' => 'Overview',
      'questions' => 'Questions',
      'assignments' => 'Assignments',
      'statistics' => 'Statistics',
    ],
  ],

  'assign' => [
    'title' => 'Assign Assessment',
    'subtitle' => 'Select the classes that will take this assessment.',
    'select_classes' => 'Select Classes',
    'assigned_classes' => 'Assigned Classes',
    'no_classes' => 'No classes selected',
    'assign_button' => 'Assign',
    'cancel_button' => 'Cancel',
  ],

  'assignments' => [
    'title' => 'Assignments',
    'subtitle' => 'Manage assessment assignments to classes.',
    'class_name' => 'Class',
    'students_count' => 'Students',
    'completed' => 'Completed',
    'in_progress' => 'In Progress',
    'not_started' => 'Not Started',
    'actions' => 'Actions',
    'view_details' => 'View Details',
    'unassign' => 'Unassign',
  ],

  'delete_confirmation' => [
    'title' => 'Delete Assessment',
    'message' => 'Are you sure you want to delete this assessment? This action cannot be undone.',
    'confirm' => 'Delete',
    'cancel' => 'Cancel',
  ],
];
