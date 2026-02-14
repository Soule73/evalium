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
    'user_has_no_valid_role' => 'L\'utilisateur n\'a pas de rôle valide assigné',
    'not_member_of_class' => 'Vous n\'êtes pas membre de cette classe',
    'assessment_not_assigned' => 'Cette évaluation ne vous est pas assignée',
    'assessment_not_available' => 'Cette évaluation n\'est pas disponible',
    'assessment_not_accessible' => 'Cette évaluation n\'est pas accessible pour le moment',
    'assessment_not_found_or_submitted' => 'Évaluation introuvable ou déjà soumise',
    'assessment_already_completed' => 'Vous avez déjà terminé cette évaluation',
    'assessment_not_published' => 'Cette évaluation n\'est pas encore publiée',
    'assessment_not_started' => 'Cette évaluation n\'a pas encore commencé',
    'assessment_ended' => 'Cette évaluation est terminée',
    'assessment_due_date_passed' => 'La date limite de cette évaluation est dépassée',
    'assessment_time_expired' => 'Votre temps est écoulé. L\'évaluation a été soumise automatiquement.',

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
    'security_violations_not_applicable' => 'Les violations de sécurité ne sont pas applicables pour ce mode d\'évaluation',

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

    // Classes
    'class_created' => 'Classe créée avec succès',
    'class_updated' => 'Classe mise à jour avec succès',
    'class_deleted' => 'Classe supprimée avec succès',

    // Class Subjects
    'class_subject_created' => 'Matière assignée à la classe avec succès',
    'class_subject_deleted' => 'Matière retirée de la classe avec succès',
    'class_subject_has_assessments' => 'Impossible de supprimer : cette assignation a des évaluations',
    'class_and_subject_required' => 'La classe et la matière sont requises',
    'teacher_replaced' => 'Enseignant remplacé avec succès',
    'coefficient_updated' => 'Coefficient mis à jour avec succès',
    'assignment_terminated' => 'Assignation terminée avec succès',

    // Subjects
    'subject_created' => 'Matière créée avec succès',
    'subject_updated' => 'Matière mise à jour avec succès',
    'subject_deleted' => 'Matière supprimée avec succès',
    'subject_has_class_subjects' => 'Impossible de supprimer : cette matière est assignée à des classes',

    // Academic Years
    'academic_year_created' => 'Année académique créée avec succès',
    'academic_year_updated' => 'Année académique mise à jour avec succès',
    'academic_year_deleted' => 'Année académique supprimée avec succès',
    'academic_year_set_current' => 'Année académique définie comme courante avec succès',
    'academic_year_archived' => 'Année académique archivée avec succès',
    'resource_wrong_academic_year' => 'Cette ressource n\'appartient pas à l\'année académique sélectionnée',

    // Grading
    'grade_saved' => 'Note enregistrée avec succès',
    'assessment_already_submitted' => 'Cette évaluation a déjà été soumise',

    // Validation - Form labels
    'assessment_title' => 'titre de l\'évaluation',
    'assessment_type' => 'type d\'évaluation',
    'delivery_mode' => 'mode de passation',
    'scheduled_date' => 'date planifiée',
    'duration' => 'durée',
    'due_date' => 'date limite',
    'coefficient' => 'coefficient',
    'class_subject' => 'classe & matière',
    'academic_year' => 'année académique',
    'academic_year_name' => 'nom de l\'année académique',
    'level' => 'niveau',
    'class_name' => 'nom de la classe',
    'class' => 'classe',
    'max_students' => 'nombre maximum d\'étudiants',
    'subject' => 'matière',
    'subject_name' => 'nom de la matière',
    'subject_code' => 'code de la matière',
    'teacher' => 'enseignant',
    'student' => 'Étudiant',
    'scores' => 'notes',
    'score' => 'note',
    'question' => 'question',
    'start_date' => 'date de début',
    'end_date' => 'date de fin',
    'valid_from' => 'valide à partir de',
    'valid_to' => 'valide jusqu\'à',
    'new_class' => 'nouvelle classe',
    'new_teacher' => 'nouvel enseignant',
    'effective_date' => 'date d\'effet',

    // Custom validation messages
    'student_already_enrolled' => 'Cet étudiant est déjà inscrit dans cette classe',
    'new_class_must_be_different' => 'La nouvelle classe doit être différente de la classe actuelle',
    'new_teacher_must_be_different' => 'Le nouvel enseignant doit être différent de l\'enseignant actuel',

    'file' => 'fichier',
    'file_too_large' => 'Le fichier est trop volumineux',
    'file_extension_not_allowed' => 'Ce type de fichier n\'est pas autorisé',
    'file_upload_limit_reached' => 'Vous avez atteint le nombre maximum de fichiers',
    'file_uploads_not_allowed' => 'L\'envoi de fichiers n\'est pas autorisé pour cette évaluation',
    'file_uploaded' => 'Fichier envoyé avec succès',
    'file_deleted' => 'Fichier supprimé avec succès',

    'assignment_reopened' => 'Copie rouverte avec succès',
    'assignment_cannot_reopen_not_supervised' => 'Seules les évaluations surveillées peuvent être rouvertes',
    'assignment_cannot_reopen_not_started' => 'Cette copie n\'a pas encore été commencée',
    'assignment_cannot_reopen_time_fully_elapsed' => 'Impossible de rouvrir : le temps imparti est entièrement écoulé',
    'assignment_cannot_reopen_not_interrupted' => 'Cette copie n\'a pas été interrompue',

    'cannot_access_assessment' => 'Vous ne pouvez pas accéder à cette évaluation',
    'do_not_own_attachment' => 'Ce fichier ne vous appartient pas',
    'answers_saved' => 'Réponses enregistrées avec succès',
    'security_violation_recorded' => 'Violation de sécurité enregistrée',
    'delivery_mode_supervised' => 'Surveillé',
    'delivery_mode_homework' => 'Devoir maison',
];
