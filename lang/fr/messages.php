<?php

return [
    // General
    'locale_updated' => 'Langue modifiée avec succès',
    'changes_saved' => 'Modifications enregistrées avec succès',
    'profile_updated' => 'Profil mis à jour avec succès',
    'error_occurred' => 'Une erreur est survenue',
    'error_updating_profile' => 'Erreur lors de la mise à jour du profil',
    'unauthorized' => 'Action non autorisée',
    'unauthenticated' => 'Utilisateur non authentifié',
    'operation_failed' => 'L\'opération a échoué',
    'not_member_of_class' => 'Vous n\'êtes pas membre de cette classe',
    'assessment_not_assigned' => 'Cette évaluation ne vous est pas assignée',
    'assessment_not_available' => 'Cette évaluation n\'est pas disponible',
    'assessment_not_accessible' => 'Cette évaluation n\'est pas accessible pour le moment',
    'assessment_not_found_or_submitted' => 'Évaluation introuvable ou déjà soumise',
    'assessment_already_completed' => 'Vous avez déjà terminé cette évaluation',

    // Roles & Permissions
    'role_created' => 'Le rôle a été créé avec succès',
    'role_updated' => 'Le rôle a été modifié avec succès',
    'role_deleted' => 'Le rôle a été supprimé avec succès',
    'role_update_failed' => 'Erreur lors de la mise à jour du rôle',
    'role_delete_failed' => 'Erreur lors de la suppression du rôle',
    'role_cannot_rename_system' => 'Les rôles système ne peuvent pas être renommés',
    'role_cannot_delete_system' => 'Les rôles système ne peuvent pas être supprimés',
    'role_cannot_delete_assigned' => 'Impossible de supprimer ce rôle car il est assigné à des utilisateurs',
    'permissions_updated' => 'Les permissions ont été mises à jour avec succès',
    'permission_created' => 'La permission a été créée avec succès',
    'permission_deleted' => 'La permission a été supprimée avec succès',
    'permission_delete_failed' => 'Erreur lors de la suppression de la permission',
    'permission_cannot_delete_assigned' => 'Impossible de supprimer cette permission car elle est assignée à des rôles',

    // Levels
    'level_created' => 'Le niveau a été créé avec succès',
    'level_updated' => 'Le niveau a été modifié avec succès',
    'level_deleted' => 'Le niveau a été supprimé avec succès',
    'level_delete_failed' => 'Erreur lors de la suppression du niveau',
    'level_activated' => 'Le niveau a été activé avec succès',
    'level_deactivated' => 'Le niveau a été désactivé avec succès',
    'level_cannot_delete_with_classes' => 'Impossible de supprimer ce niveau car il contient des classes',

    // Users
    'user_created' => 'L\'utilisateur a été créé avec succès',
    'user_updated' => 'L\'utilisateur a été modifié avec succès',
    'user_deleted' => 'L\'utilisateur a été supprimé avec succès',
    'user_restored' => 'L\'utilisateur a été restauré avec succès',
    'user_activated' => 'L\'utilisateur a été activé avec succès',
    'user_deactivated' => 'L\'utilisateur a été désactivé avec succès',
    'user_cannot_delete_self' => 'Vous ne pouvez pas supprimer votre propre compte',
    'user_delete_failed' => 'Erreur lors de la suppression de l\'utilisateur',
    'user_force_delete_failed' => 'Erreur lors de la suppression définitive de l\'utilisateur',

    // Enrollments
    'enrollment_created' => 'L\'inscription a été créée avec succès',
    'enrollment_deleted' => 'L\'inscription a été supprimée avec succès',
    'enrollment_reactivated' => 'L\'inscription a été réactivée avec succès',
    'enrollment_not_in_selected_year' => 'Cette inscription n\'appartient pas à l\'année académique sélectionnée',
    'student_transferred' => 'L\'étudiant a été transféré avec succès',
    'student_withdrawn' => 'L\'étudiant a été retiré avec succès',

    // Classes
    'class_not_in_selected_year' => 'Cette classe n\'appartient pas à l\'année académique sélectionnée',

    // Assessments
    'assessment_created' => 'L\'évaluation a été créée avec succès',
    'assessment_updated' => 'L\'évaluation a été modifiée avec succès',
    'assessment_deleted' => 'L\'évaluation a été supprimée avec succès',
    'assessment_duplicated' => 'L\'évaluation a été dupliquée avec succès',
    'assessment_published' => 'L\'évaluation a été publiée avec succès',
    'assessment_unpublished' => 'L\'évaluation a été dépubliée avec succès',
    'assessment_submitted' => 'L\'évaluation a été soumise avec succès',
    'assessment_must_start_before_submit' => 'Vous devez commencer l\'évaluation avant de la soumettre',
    'answers_saved' => 'Réponses enregistrées',
    'error_saving_answers' => 'Erreur lors de l\'enregistrement des réponses',
    'security_violation_processed' => 'Violation de sécurité enregistrée',

    // Assignments
    'classes_assigned_to_assessment' => 'Assignation terminée : :count classe(s) assignée(s)',
    'classes_already_assigned' => '(:already_assigned déjà assignée(s))',
    'class_removed_from_assessment' => 'L\'évaluation a été retirée de la classe avec succès',
    'unable_to_remove_class' => 'Impossible de retirer l\'évaluation de cette classe',

    // Corrections
    'scores_saved' => 'Correction enregistrée avec succès ! :updated_answers réponse(s) mise(s) à jour. Score total : :total_score points',
    'error_saving_correction' => 'Erreur lors de l\'enregistrement de la correction',
    'score_updated' => 'Note mise à jour avec succès',
    'error_updating_score' => 'Erreur lors de la mise à jour de la note',

    // Form labels for validation
    'assessment_title' => 'titre de l\'évaluation',
    'assessment_type' => 'type d\'évaluation',
    'scheduled_date' => 'date planifiée',
    'duration' => 'durée',
    'coefficient' => 'coefficient',
    'class_subject' => 'classe & matière',
];
