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
    'not_member_of_class' => 'You are not a member of this class',
    'assessment_not_assigned' => 'This assessment is not assigned to you',
    'assessment_not_available' => 'This assessment is not available',
    'assessment_not_accessible' => 'This assessment is not accessible at this time',
    'assessment_not_found_or_submitted' => 'Assessment not found or already submitted',
    'assessment_already_completed' => 'You have already completed this assessment',

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
    'level_cannot_delete_with_classes' => 'Cannot delete this level as it contains classes',

    // Users
    'user_created' => 'User created successfully',
    'user_updated' => 'User updated successfully',
    'user_deleted' => 'User deleted successfully',
    'user_restored' => 'User restored successfully',
    'user_activated' => 'User activated successfully',
    'user_deactivated' => 'User deactivated successfully',
    'user_cannot_delete_self' => 'You cannot delete your own account',
    'user_delete_failed' => 'Error deleting user',
    'user_force_delete_failed' => 'Error permanently deleting user',

    // Enrollments
    'enrollment_created' => 'Enrollment created successfully',
    'enrollment_deleted' => 'Enrollment deleted successfully',
    'enrollment_reactivated' => 'Enrollment reactivated successfully',
    'enrollment_not_in_selected_year' => 'This enrollment does not belong to the selected academic year',
    'student_transferred' => 'Student transferred successfully',
    'student_withdrawn' => 'Student withdrawn successfully',

    // Classes
    'class_not_in_selected_year' => 'This class does not belong to the selected academic year',

    // Assessments
    'assessment_created' => 'Assessment created successfully',
    'assessment_updated' => 'Assessment updated successfully',
    'assessment_deleted' => 'Assessment deleted successfully',
    'assessment_duplicated' => 'Assessment duplicated successfully',
    'assessment_published' => 'Assessment published successfully',
    'assessment_unpublished' => 'Assessment unpublished successfully',
    'assessment_submitted' => 'Assessment submitted successfully',
    'assessment_must_start_before_submit' => 'You must start the assessment before submitting it',
    'answers_saved' => 'Answers saved',
    'error_saving_answers' => 'Error saving answers',
    'security_violation_processed' => 'Security violation processed',

    // Assignments
    'classes_assigned_to_assessment' => 'Assignment completed: :count class(es) assigned',
    'classes_already_assigned' => '(:already_assigned already assigned)',
    'class_removed_from_assessment' => 'Assessment removed from class successfully',
    'unable_to_remove_class' => 'Unable to remove assessment from this class',

    // Corrections
    'scores_saved' => 'Correction saved successfully! :updated_answers answer(s) updated. Total score: :total_score points',
    'error_saving_correction' => 'Error saving correction',
    'score_updated' => 'Score updated successfully',
    'error_updating_score' => 'Error updating score',

    // Form labels for validation
    'assessment_title' => 'assessment title',
    'assessment_type' => 'assessment type',
    'scheduled_date' => 'scheduled date',
    'duration' => 'duration',
    'coefficient' => 'coefficient',
    'class_subject' => 'class & subject',
];
