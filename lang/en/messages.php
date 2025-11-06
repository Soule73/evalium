<?php

return [
    // General
    'locale_updated' => 'Language updated successfully',
    'changes_saved' => 'Changes saved successfully',
    'profile_updated' => 'Profile updated successfully',
    'error_occurred' => 'An error occurred',
    'error_updating_profile' => 'Error updating profile',
    'unauthorized' => 'Unauthorized action',
    'unauthenticated' => 'User not authenticated',
    'operation_failed' => 'Operation failed',
    'not_member_of_group' => 'You are not a member of this group',
    'exam_not_assigned' => 'This exam is not assigned to you',
    'exam_not_available' => 'This exam is not available',
    'exam_not_accessible' => 'This exam is not accessible at this time',
    'exam_not_found_or_submitted' => 'Exam not found or already submitted',
    'exam_already_completed' => 'You have already completed this exam',

    // Roles & Permissions
    'role_created' => 'Role created successfully',
    'role_updated' => 'Role updated successfully',
    'role_deleted' => 'Role deleted successfully',
    'role_update_failed' => 'Error updating role',
    'role_delete_failed' => 'Error deleting role',
    'role_cannot_rename_system' => 'System roles cannot be renamed',
    'role_cannot_delete_system' => 'System roles cannot be deleted',
    'role_cannot_delete_assigned' => 'Cannot delete this role as it is assigned to users',
    'permissions_updated' => 'Permissions updated successfully',
    'permission_created' => 'Permission created successfully',
    'permission_deleted' => 'Permission deleted successfully',
    'permission_delete_failed' => 'Error deleting permission',
    'permission_cannot_delete_assigned' => 'Cannot delete this permission as it is assigned to roles',

    // Levels
    'level_created' => 'Level created successfully',
    'level_updated' => 'Level updated successfully',
    'level_deleted' => 'Level deleted successfully',
    'level_delete_failed' => 'Error deleting level',
    'level_activated' => 'Level activated successfully',
    'level_deactivated' => 'Level deactivated successfully',
    'level_cannot_delete_with_groups' => 'Cannot delete this level as it contains groups',

    // Groups
    'group_created' => 'Group created successfully',
    'group_updated' => 'Group updated successfully',
    'group_deleted' => 'Group deleted successfully',
    'group_activated' => 'Group activated successfully',
    'group_deactivated' => 'Group deactivated successfully',
    'groups_activated' => ':count group(s) activated successfully',
    'groups_deactivated' => ':count group(s) deactivated successfully',
    'student_removed' => 'Student removed from group successfully',
    'students_removed' => ':count student(s) removed successfully',
    'students_assigned' => ':count student(s) assigned successfully',

    // Users
    'user_created' => 'User created successfully',
    'user_updated' => 'User updated successfully',
    'user_deleted' => 'User deleted successfully',
    'user_restored' => 'User restored successfully',
    'user_activated' => 'User activated successfully',
    'user_deactivated' => 'User deactivated successfully',
    'group_changed' => 'Group changed successfully',

    // Exams
    'exam_created' => 'Exam created successfully',
    'exam_updated' => 'Exam updated successfully',
    'exam_deleted' => 'Exam deleted successfully',
    'exam_duplicated' => 'Exam duplicated successfully',
    'exam_activated' => 'Exam activated successfully',
    'exam_deactivated' => 'Exam deactivated successfully',
    'exam_submitted' => 'Exam submitted successfully',
    'exam_must_start_before_submit' => 'You must start the exam before submitting it',
    'answers_saved' => 'Answers saved',
    'error_saving_answers' => 'Error saving answers',
    'security_violation_processed' => 'Security violation processed',

    // Assignments
    'groups_assigned_to_exam' => 'Assignment completed: :count group(s) assigned',
    'groups_already_assigned' => '(:already_assigned already assigned)',
    'group_removed_from_exam' => 'Exam removed from group successfully',
    'unable_to_remove_group' => 'Unable to remove exam from this group',

    // Corrections
    'scores_saved' => 'Correction saved successfully! :updated_answers answer(s) updated. Total score: :total_score points',
    'error_saving_correction' => 'Error saving correction',
    'score_updated' => 'Score updated successfully',
    'error_updating_score' => 'Error updating score',
];
