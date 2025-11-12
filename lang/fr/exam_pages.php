<?php

declare(strict_types=1);

return [
    // Pages titles
    'page_titles' => [
        'index' => 'Examens',
        'create' => 'Créer un examen',
        'edit' => 'Modifier l\'examen',
        'show' => 'Détails de l\'examen',
        'assign' => 'Assigner l\'examen',
        'assignments' => 'Assignations',
        'group_details' => 'Détails du groupe',
        'student_results' => 'Résultats - :student - :exam',
        'student_review' => 'Correction - :student - :exam',
        'management' => 'Gestion des examens',
    ],

    // Index page
    'index' => [
        'title' => 'Gestion des examens',
        'subtitle' => 'Créez, gérez et assignez vos examens aux étudiants.',
        'new_exam' => 'Nouvel examen',
    ],

    // Create page
    'create' => [
        'title' => 'Créer un examen',
        'subtitle' => 'Configurez les paramètres de votre examen et ajoutez vos questions.',
        'cancel' => 'Annuler',
        'submit' => 'Créer l\'examen',
        'at_least_one_question' => 'Vous devez ajouter au moins une question',
        'question_content_required' => 'La question :number doit avoir un contenu',
        'validation_errors' => 'Erreurs de validation',
    ],

    // Edit page
    'edit' => [
        'title' => 'Modifier l\'examen',
        'subtitle' => 'Modifiez les paramètres de votre examen et ajustez vos questions. Total: :points point:plural',
        'cancel' => 'Annuler',
        'submit' => 'Modifier l\'examen',
    ],

    // Show page
    'show' => [
        'title' => 'Détails et gestion de l\'examen',
        'toggle_active' => 'Actif',
        'toggle_inactive' => 'Inactif',
        'duplicate' => 'Dupliquer',
        'edit' => 'Modifier',
        'view_assignments' => 'Voir les assignations',
        'questions' => 'Questions',
        'total_points' => 'Points totaux',
        'duration' => 'Durée',
        'assigned_groups' => 'Groupes assignés',
        'concerned_students' => 'Étudiants concernés',
        'assigned_groups_section' => 'Groupes assignés',
        'assigned_groups_subtitle' => ':count groupe(s) ont accès à cet examen',
        'exam_questions' => 'Questions de l\'examen',
        'no_questions' => 'Aucune question ajoutée à cet examen.',
        'add_questions' => 'Ajouter des questions',
        'answer_choices' => 'Choix de réponse :',
        'free_text_info' => 'Question à réponse libre - correction manuelle requise',
        'duplicate_modal_title' => 'Dupliquer l\'examen',
        'duplicate_modal_message' => 'Voulez-vous vraiment dupliquer l\'examen ":title" ? Une copie sera créée avec toutes les questions.',
        'duplicate_confirm' => 'Dupliquer',
        'remove_group_title' => 'Retirer le groupe',
        'remove_group_message' => 'Êtes-vous sûr de vouloir retirer l\'examen ":exam" du groupe ":group" ?',
        'remove' => 'Retirer',
        'view_details' => 'Voir détails',
    ],

    // Assign page
    'assign' => [
        'title' => 'Assigner l\'examen: :title',
        'exam_info' => 'Informations sur l\'examen',
        'exam_info_subtitle' => 'Détails de l\'examen à assigner',
        'cancel' => 'Annuler',
        'duration_label' => 'Durée: :duration minutes',
        'assigned_groups_title' => 'Groupes assignés',
        'assigned_groups_subtitle' => ':count groupe(s) ont accès à cet examen',
        'no_assigned_groups_title' => 'Aucun groupe assigné',
        'no_assigned_groups_subtitle' => 'Aucun groupe n\'a encore accès à cet examen',
        'select_groups_title' => 'Assigner l\'examen à des groupes',
        'select_groups_subtitle' => 'Sélectionnez les groupes auxquels vous souhaitez donner accès à cet examen',
        'assign_to_groups' => 'Assigner à :count groupe:plural',
        'active_students' => ':count étudiant(s) actif(s)',
        'search_placeholder' => 'Rechercher un groupe...',
        'all_assigned' => 'Tous les groupes assignés',
        'all_assigned_subtitle' => 'Tous les groupes actifs ont déjà accès à cet examen',
        'no_groups_found' => 'Aucun groupe trouvé',
        'no_groups_found_subtitle' => 'Aucun groupe ne correspond à vos critères de recherche.',
        'reset_search' => 'Réinitialiser la recherche',
        'confirm_title' => 'Confirmer l\'assignation',
        'confirm_message' => 'Vous êtes sur le point d\'assigner cet examen à :groups groupe(s), ce qui donnera accès à environ :students étudiant(s).',
        'confirm_button' => 'Confirmer l\'assignation',
        'exam_label' => 'Examen :',
        'duration_info' => 'Durée :',
        'minutes' => 'minutes',
    ],

    // Assignments page
    'assignments' => [
        'title' => 'Assignations de l\'examen',
        'assign_new_groups' => 'Assigner à de nouveaux groupes',
        'groups_list_title' => 'Groupes assignés (:count)',
        'groups_list_subtitle' => 'Liste des groupes ayant accès à cet examen',
        'no_groups_title' => 'Aucun groupe assigné',
        'no_groups_message' => 'Commencez par assigner cet examen à des groupes d\'étudiants.',
        'assign_groups_button' => 'Assigner des groupes',
    ],

    // Group Details page
    'group_details' => [
        'title' => ':group - :exam',
        'subtitle' => 'Détails de l\'examen: :exam',
        'back' => 'Retour',
        'level' => 'Niveau',
        'active_students' => 'Étudiants actifs',
        'exam_duration' => 'Durée de l\'examen',
        'questions_count' => 'Nombre de questions',
        'not_defined' => 'Non défini',
        'minutes' => 'minutes',
        'stats_title' => 'Statistiques du groupe',
        'average_score' => 'Note moyenne du groupe',
        'students_title' => 'Étudiants du groupe',
        'students_subtitle' => 'Liste complète des étudiants et leur progression',
        'search_placeholder' => 'Rechercher par nom ou email...',
        'no_students_title' => 'Aucun étudiant dans ce groupe',
        'no_students_subtitle' => 'Ce groupe ne contient aucun étudiant actif',
    ],

    // Student Results page
    'student_results' => [
        'title' => 'Résultats - :student - :exam',
        'copy_title' => 'Copie de :student',
        'exam_active' => 'Examen actif',
        'exam_disabled' => 'Examen désactivé',
        'correct_exam' => 'Corriger l\'examen',
        'edit_correction' => 'Modifier la correction',
        'back_to_assignments' => 'Retour aux assignations',
        'answers_detail' => 'Détail des réponses',
        'teacher_notes' => 'Notes',
    ],

    // Student Review page
    'student_review' => [
        'title' => 'Correction - :student - :exam',
        'correction_title' => 'Correction de l\'examen de :student',
        'correction_mode' => 'Mode correction',
        'save_grades' => 'Sauvegarder les notes',
        'saving' => 'Sauvegarde...',
        'back_to_assignments' => 'Retour aux assignations',
        'auto_corrected_info' => 'Les QCM sont automatiquement corrigées mais vous pouvez ajuster les notes si nécessaire.',
        'questions_correction' => 'Questions et correction',
        'correction_summary' => 'Résumé de la correction',
        'total_score' => 'Note total',
        'percentage' => 'Pourcentage',
        'status' => 'Statut',
        'confirm_save_title' => 'Confirmer la sauvegarde des notes',
        'confirm_save_message' => 'Vous êtes sur le point de sauvegarder les notes pour l\'examen de :student.',
        'teacher_notes_label' => 'Notes du professeur (optionnel)',
        'teacher_notes_placeholder' => 'Ajoutez vos commentaires ou observations sur cette copie...',
        'teacher_notes_help' => 'Ces notes seront visibles par l\'étudiant avec ses résultats.',
        'cancel' => 'Annuler',
        'confirm_save' => 'Confirmer et sauvegarder',
        'question_score_label' => 'Note pour cette question (max: :max points)',
        'auto_graded_info' => 'Note calculé automatiquement - modifiable si nécessaire',
        'manual_grading_required' => 'Correction manuelle requise',
        'question_comment_label' => 'Commentaire pour cette question:',
        'question_comment_placeholder' => 'Ajoutez vos commentaires pour cette réponse...',
    ],

    // Common
    'common' => [
        'points' => 'point',
        'points_plural' => 'points',
        's' => 's',
        'group' => 'Groupe',
    ],
];
