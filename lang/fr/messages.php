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
    'not_member_of_group' => 'Vous n\'êtes pas membre de ce groupe',
    'exam_not_assigned' => 'Cet examen ne vous est pas assigné',
    'exam_not_available' => 'Cet examen n\'est pas disponible',
    'exam_not_accessible' => 'Cet examen n\'est pas accessible pour le moment',
    'exam_not_found_or_submitted' => 'Examen introuvable ou déjà soumis',
    'exam_already_completed' => 'Vous avez déjà terminé cet examen',

    // Roles & Permissions
    'role_created' => 'Le rôle a été créé avec succès',
    'role_updated' => 'Le rôle a été modifié avec succès',
    'role_deleted' => 'Le rôle a été supprimé avec succès',
    'role_cannot_rename_system' => 'Les rôles système ne peuvent pas être renommés',
    'role_cannot_delete_system' => 'Les rôles système ne peuvent pas être supprimés',
    'role_cannot_delete_assigned' => 'Impossible de supprimer ce rôle car il est assigné à des utilisateurs',
    'permissions_updated' => 'Les permissions ont été mises à jour avec succès',
    'permission_created' => 'La permission a été créée avec succès',
    'permission_deleted' => 'La permission a été supprimée avec succès',
    'permission_cannot_delete_assigned' => 'Impossible de supprimer cette permission car elle est assignée à des rôles',

    // Levels
    'level_created' => 'Le niveau a été créé avec succès',
    'level_updated' => 'Le niveau a été modifié avec succès',
    'level_deleted' => 'Le niveau a été supprimé avec succès',
    'level_activated' => 'Le niveau a été activé avec succès',
    'level_deactivated' => 'Le niveau a été désactivé avec succès',
    'level_cannot_delete_with_groups' => 'Impossible de supprimer ce niveau car il contient des groupes',

    // Groups
    'group_created' => 'Le groupe a été créé avec succès',
    'group_updated' => 'Le groupe a été modifié avec succès',
    'group_deleted' => 'Le groupe a été supprimé avec succès',
    'group_activated' => 'Le groupe a été activé avec succès',
    'group_deactivated' => 'Le groupe a été désactivé avec succès',
    'groups_activated' => ':count groupe(s) activé(s) avec succès',
    'groups_deactivated' => ':count groupe(s) désactivé(s) avec succès',
    'student_removed' => 'L\'étudiant a été retiré du groupe avec succès',
    'students_removed' => ':count étudiant(s) retiré(s) avec succès',
    'students_assigned' => ':count étudiant(s) assigné(s) avec succès',

    // Users
    'user_created' => 'L\'utilisateur a été créé avec succès',
    'user_updated' => 'L\'utilisateur a été modifié avec succès',
    'user_deleted' => 'L\'utilisateur a été supprimé avec succès',
    'user_restored' => 'L\'utilisateur a été restauré avec succès',
    'user_activated' => 'L\'utilisateur a été activé avec succès',
    'user_deactivated' => 'L\'utilisateur a été désactivé avec succès',
    'group_changed' => 'Le groupe a été modifié avec succès',

    // Exams
    'exam_created' => 'L\'examen a été créé avec succès',
    'exam_updated' => 'L\'examen a été modifié avec succès',
    'exam_deleted' => 'L\'examen a été supprimé avec succès',
    'exam_duplicated' => 'L\'examen a été dupliqué avec succès',
    'exam_activated' => 'L\'examen a été activé avec succès',
    'exam_deactivated' => 'L\'examen a été désactivé avec succès',
    'exam_submitted' => 'L\'examen a été soumis avec succès',
    'exam_must_start_before_submit' => 'Vous devez commencer l\'examen avant de le soumettre',
    'answers_saved' => 'Réponses enregistrées',
    'error_saving_answers' => 'Erreur lors de l\'enregistrement des réponses',
    'security_violation_processed' => 'Violation de sécurité enregistrée',

    // Assignments
    'groups_assigned_to_exam' => 'Assignation terminée : :count groupe(s) assigné(s)',
    'groups_already_assigned' => '(:already_assigned déjà assigné(s))',
    'group_removed_from_exam' => 'L\'examen a été retiré du groupe avec succès',
    'unable_to_remove_group' => 'Impossible de retirer l\'examen de ce groupe',

    // Corrections
    'scores_saved' => 'Correction enregistrée avec succès ! :updated_answers réponse(s) mise(s) à jour. Score total : :total_score points',
    'error_saving_correction' => 'Erreur lors de l\'enregistrement de la correction',
    'score_updated' => 'Note mise à jour avec succès',
    'error_updating_score' => 'Erreur lors de la mise à jour de la note',
];
