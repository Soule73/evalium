<?php

declare(strict_types=1);

/**
 * Shared status labels for assessments, enrollments and users.
 * Frontend key: t('commons/status.graded'), t('commons/status.all_statuses'), etc.
 */
return [
  // Assessment / assignment statuses
  'not_started' => 'Not Started',
  'in_progress' => 'In Progress',
  'completed' => 'Completed',
  'submitted' => 'Submitted',
  'graded' => 'Graded',
  'published' => 'Published',
  'draft' => 'Draft',
  'archived' => 'Archived',

  // User / general statuses
  'active' => 'Active',
  'inactive' => 'Inactive',
  'deleted' => 'Deleted',

  // Enrollment statuses
  'withdrawn' => 'Withdrawn',
  'transferred' => 'Transferred',

  // Assessment types (display labels)
  'supervised' => 'Supervised',
  'homework' => 'Homework',

  // Generic filter options
  'all_statuses' => 'All Statuses',
  'all_roles' => 'All Roles',
  'all_classes' => 'All Classes',
  'all_subjects' => 'All Subjects',
  'all_years' => 'All Years',
  'all_types' => 'All Types',
  'all' => 'All',
];
