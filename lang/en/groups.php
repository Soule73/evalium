<?php

declare(strict_types=1);

return [
    // List and display
    'title' => 'Groups',
    'list' => 'Group list',
    'my_groups' => 'My groups',
    'no_groups' => 'No groups available',
    'no_groups_found' => 'No groups found',

    // CRUD actions
    'create' => 'Create group',
    'edit' => 'Edit group',
    'delete' => 'Delete group',
    'view' => 'View group',
    'manage' => 'Manage groups',

    // Success messages
    'created' => 'Group created successfully!',
    'updated' => 'Group updated successfully!',
    'deleted' => 'Group deleted successfully!',
    'activated' => 'Group activated successfully!',
    'deactivated' => 'Group deactivated successfully!',

    // Error messages
    'not_found' => 'Group not found',
    'unauthorized' => 'You are not authorized to access this group',
    'cannot_delete_with_students' => 'Cannot delete a group containing students',
    'cannot_delete_with_exams' => 'Cannot delete a group with assigned exams',

    // Students
    'students' => 'Students',
    'student_count' => 'Student count',
    'add_students' => 'Add students',
    'remove_students' => 'Remove students',
    'assign_students' => 'Assign students',
    'students_added' => 'Students added successfully!',
    'students_removed' => 'Students removed successfully!',
    'student_removed' => 'Student removed successfully!',
    'no_students' => 'No students in this group',
    'students_assigned' => ':count student(s) assigned successfully!',

    // Bulk actions
    'bulk_activate' => 'Activate selected groups',
    'bulk_deactivate' => 'Deactivate selected groups',
    'bulk_delete' => 'Delete selected groups',
    'bulk_remove_students' => 'Remove selected students',
    'groups_activated' => ':count group(s) activated successfully!',
    'groups_deactivated' => ':count group(s) deactivated successfully!',
    'bulk_students_removed' => ':count student(s) removed successfully!',

    // Details
    'name' => 'Group name',
    'description' => 'Description',
    'status' => 'Status',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'created_at' => 'Created at',
    'updated_at' => 'Updated at',
];
