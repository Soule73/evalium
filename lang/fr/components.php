<?php

return [
    // ConfirmationModal
    'confirmation_modal' => [
        'confirm' => 'Confirmer',
        'cancel' => 'Annuler',
    ],

    // Select
    'select' => [
        'placeholder' => 'Sélectionner une option',
        'search_placeholder' => 'Rechercher...',
        'no_option_found' => 'Aucune option trouvée',
        'select_level' => 'Sélectionner un niveau',
        'level_placeholder' => 'Sélectionner un niveau académique',
    ],

    // ChoiceEditor
    'choice_editor' => [
        'placeholders' => 'Entrez votre réponse...',
        'simple' => 'Éditeur simple',
        'preview' => 'Aperçu',
        'markdown' => 'Éditeur Markdown',
        'hide' => 'Masquer',
        'preview_label' => 'Aperçu :',
        'no_content' => 'Aucun contenu',
        'switch_simple' => 'Basculer vers l\'éditeur simple',
        'switch_markdown' => 'Basculer vers l\'éditeur Markdown',
        'hide_preview' => 'Masquer l\'aperçu',
        'show_preview' => 'Afficher l\'aperçu',
    ],

    // Toast / FlashToastHandler
    'toast' => [
        'success' => 'Succès',
        'error' => 'Erreur',
        'warning' => 'Attention',
        'info' => 'Information',
        'close' => 'Fermer',
    ],

    // Toggle
    'toggle' => [
        'active' => 'Actif',
        'inactive' => 'Inactif',
    ],

    // RoleForm
    'role_form' => [
        'system_role_badge' => 'Rôle système',
        'system_role_notice' => 'Ce rôle est un rôle système. Vous pouvez uniquement modifier ses permissions.',
        'role_name_label' => 'Nom du rôle',
        'role_name_placeholder' => 'Ex: moderator, editor...',
        'create_button' => 'Créer le rôle',
        'creating' => 'Création...',
        'cancel' => 'Annuler',
    ],

    // PermissionSelector
    'permission_selector' => [
        'label' => 'Permissions (:count sélectionnées)',
        'select_all' => 'Tout sélectionner',
        'deselect_all' => 'Tout désélectionner',
        'sync' => 'Synchroniser',
    ],

    // QuestionsManager
    'questions_manager' => [
        'title' => 'Questions de l\'examen',
        'subtitle' => 'Ajoutez et configurez les questions de votre examen.',
        'add_question' => 'Ajouter une question',
        'no_questions_title' => 'Aucune question pour le moment',
        'no_questions_subtitle' => 'Commencez par ajouter votre première question à cet examen.',
        'delete_confirm' => 'Confirmer la suppression',
        'delete_cancel' => 'Annuler',
        'delete_notice' => 'Cette action est irréversible.',
        'history_button' => 'Historique (:count)',
    ],

    // DeleteHistoryModal
    'delete_history_modal' => [
        'title' => 'Historique des suppressions',
        'clear_history' => 'Vider l\'historique',
        'no_items' => 'Aucun élément supprimé',
        'questions_tab' => 'Questions',
        'choices_tab' => 'Choix',
        'no_questions' => 'Aucune question supprimée',
        'no_choices' => 'Aucun choix supprimé',
        'restore' => 'Restaurer',
        'close' => 'Fermer',
        'deleted_on' => 'Supprimé le',
        'point' => 'point',
        'points' => 'points',
        'correct_choice' => 'Choix correct',
        'incorrect_choice' => 'Choix incorrect',
    ],

    // SortableQuestionItem
    'question_item' => [
        'question_statement' => 'Énoncé de la question',
        'question_placeholder' => 'Saisissez votre question ici...',
        'question_help' => 'Saisissez clairement l\'énoncé de votre question. Vous pouvez utiliser le formatage Markdown.',
        'answer_options' => 'Options de réponse',
        'add_option' => 'Ajouter une option',
    ],

    // ExamGeneralConfig
    'exam_general_config' => [
        'title' => 'Informations générales',
        'active_label' => 'Examen actif',
        'exam_title_label' => 'Titre de l\'examen',
        'duration_label' => 'Durée (minutes)',
        'start_time_label' => 'Date et heure de début',
        'end_time_label' => 'Date et heure de fin',
        'description_label' => 'Description de l\'examen',
        'description_placeholder' => 'Description de l\'examen...',
        'description_help' => 'Décrivez l\'objectif et les modalités de cet examen. Vous pouvez utiliser le formatage Markdown.',
    ],

    // QuestionRenderer
    'question_renderer' => [
        'no_answer' => 'Aucune réponse fournie',
        'student_answer_label' => 'Réponse de l\'étudiant',
        'your_answer_label' => 'Votre réponse',
        'no_answer_student' => 'L\'étudiant n\'a pas fourni de réponse pour cette question.',
        'no_answer_yours' => 'Vous n\'avez pas fourni de réponse pour cette question.',
        'teacher_feedback' => 'Commentaire du professeur :',
    ],

    // StudentExamAssignmentList
    'student_exam_list' => [
        'title_unavailable' => 'Titre non disponible',
        'pending' => 'En attente',
        'not_graded' => 'Non noté',
        'not_submitted' => 'Non soumis',
        'view' => 'Voir',
        'view_exam' => "Voir l'examen",
        'submitted_on' => 'Soumis le',
        'exam' => 'Examen',
        'date' => 'Date',
        'duration' => 'Durée',
        'score' => 'Note',
        'status' => 'Statut',
        'actions' => 'Actions',
        'search_exam' => 'Rechercher un examen...',
        'search_admin' => 'Rechercher par titre d\'examen ou nom d\'étudiant...',
        'search_student' => 'Rechercher par titre d\'examen...',
        'no_exam_assigned_title' => 'Aucun examen assigné',
        'no_exam_assigned_admin' => 'Aucun examen n\'a été assigné aux étudiants.',
        'no_exam_assigned_student' => 'Vous n\'avez actuellement aucun examen assigné.',
        'no_exam_found_title' => 'Aucun examen trouvé',
        'no_exam_found_subtitle' => 'Aucun examen ne correspond à vos critères de recherche ou de filtre.',
        'reset_filters' => 'Réinitialiser les filtres',
    ],

    // TakeQuestion
    'take_question' => [
        'points' => ':points point(s)',
        'multiple_choice' => 'Choix multiples',
        'one_choice' => 'Choix unique',
        'boolean' => 'Vrai/Faux',
        'text' => 'Réponse texte',
        'true' => 'Vrai',
        'false' => 'Faux',
        'your_answer_placeholder' => 'Tapez votre réponse ici... (Markdown supporté)',
        'your_answer_help' => 'Vous pouvez utiliser la syntaxe Markdown pour formater votre réponse',
    ],

    // AlertSecurityViolation
    'alert_security_violation' => [
        'title' => 'Examen Terminé',
        'subtitle' => 'Votre examen a été automatiquement terminé et soumis en raison d\'une violation des règles de sécurité.',
        'violation_detected' => 'Violation détectée : :reason',
        'teacher_notified' => 'Votre enseignant sera notifié de cette violation',
        'answers_saved' => 'Vos réponses ont été sauvegardées avant la terminaison',
        'will_be_contacted' => 'Vous serez contacté concernant la suite à donner',
        'back_to_exams' => 'Retour aux examens',
    ],

    // ExamInfoSection
    'exam_info_section' => [
        'exam_label' => 'Examen',
        'description_label' => 'Description',
        'teacher_label' => 'Professeur(e)/Créateur(trice)',
        'student_label' => 'Étudiant',
        'email_label' => 'Email',
        'submitted_on' => 'Soumis le',
        'duration_label' => 'Durée',
        'score_assigned' => 'Note attribuée',
        'score_pending' => 'Note (en attente)',
        'score_label' => 'Note',
        'score_final' => 'Note finale',
        'percentage_label' => 'Pourcentage',
        'questions_label' => 'Questions',
        'status_label' => 'Statut',
        'questions_count' => ':count questions',
        'pending_correction' => 'En attente de correction',
        'finished' => 'Terminé',
        'automatic_submission' => 'Soumission Automatique',
        'automatic_submission_message' => 'Cet examen a été soumis automatiquement',
        'violation_detected_label' => 'Violation détectée : :violation',
    ],

    // ExamStatsCards
    'exam_stats_cards' => [
        'total_students' => 'Total étudiants',
        'total_assigned' => 'Total assigné',
        'completed' => 'Terminé',
        'in_progress' => 'En cours',
        'not_started' => 'Non commencé',
    ],

    // question_result_readonly
    'question_result_readonly' => [
        'your_answer_default' => 'Votre réponse :',
        'student_answer' => 'Réponse de l\'étudiant',
        'your_answer' => 'Votre réponse',
        'student_answer_incorrect' => 'Réponse de l\'étudiant (incorrecte)',
        'your_answer_incorrect' => 'Votre réponse (incorrecte)',
        'student_answer_correct' => 'Réponse de l\'étudiant (correcte)',
        'your_answer_correct' => 'Votre réponse (correcte)',
        'correct_answer' => 'Réponse correcte',
        'boolean_true' => 'Vrai',
        'boolean_false' => 'Faux',
        'boolean_true_short' => 'V',
        'boolean_false_short' => 'F',
    ],

    // QuestionReadOnlySection
    'question_readonly_section' => [
        'correct' => 'Correct',
        'incorrect' => 'Incorrect',
    ],

    // questionOptions
    'question_options' => [
        'multiple_title' => 'Choix multiples',
        'multiple_subtitle' => 'Plusieurs réponses possibles',
        'one_choice_title' => 'Choix unique',
        'one_choice_subtitle' => 'Une seule réponse correcte',
        'boolean_title' => 'Vrai/Faux',
        'boolean_subtitle' => 'Question booléenne',
        'text_title' => 'Réponse libre',
        'text_subtitle' => 'Texte de réponse libre',
    ],

    // ExamAssignmentColumns
    'exam_assignment_columns' => [
        'student_label' => 'Étudiant',
        'name_unavailable' => 'Nom non disponible',
        'email_unavailable' => 'Email non disponible',
        'status_label' => 'Statut',
        'assigned_on' => 'Assigné le',
        'started_on' => 'Commencé le',
        'completed_on' => 'Terminé le',
        'score_label' => 'Note',
        'actions_label' => 'Actions',
        'view_result' => 'Voir résultat',
        'all_statuses' => 'Tous les statuts',
        'not_started' => 'Non commencé',
        'submitted' => 'Soumis',
        'graded' => 'Noté',
    ],

    // GroupTableConfig
    'group_table_config' => [
        'group_label' => 'Groupe',
        'active_students_count' => ':count étudiant(s) actif(s)',
        'actions_label' => 'Actions',
        'view_details' => 'Voir détails',
        'remove' => 'Retirer',
        'search_placeholder' => 'Rechercher un groupe...',
        'empty_title' => 'Aucun groupe assigné',
        'empty_subtitle' => 'Cet examen n\'est pas encore assigné à des groupes',
    ],

    // ExamHeader
    'exam_header' => [
        'questions_count' => ':count question|:count questions',
        'created_on' => 'Créé le :date',
    ],

    // ExamList
    'exam_list' => [
        'view_exam' => 'Voir',
        'view_exam_title' => 'Voir l\'examen',
        'exam_label' => 'Examen',
        'duration_label' => 'Durée',
        'status_label' => 'Statut',
        'created_on' => 'Créé le',
        'actions_label' => 'Actions',
        'status_active' => 'Actif',
        'status_inactive' => 'Inactif',
        'search_placeholder' => 'Rechercher par titre ou description...',
        'empty_title' => 'Aucun examen créé',
        'empty_subtitle' => 'Commencez par créer votre premier examen pour vos étudiants.',
        'empty_search_title' => 'Aucun examen trouvé',
        'empty_search_subtitle' => 'Essayez de modifier vos critères de recherche ou de filtrage.',
        'reset_filters' => 'Réinitialiser les filtres',
    ],

    'assessment_list' => [
        'view_assessment' => 'Voir',
        'view_assessment_title' => 'Voir l\'évaluation',
        'assessment_label' => 'Évaluation',
        'duration_label' => 'Durée',
        'status_label' => 'Statut',
        'created_on' => 'Créé le',
        'actions_label' => 'Actions',
        'status_published' => 'Publié',
        'status_unpublished' => 'Non publié',
        'search_placeholder' => 'Rechercher par titre ou description...',
        'empty_title' => 'Aucune évaluation créée',
        'empty_subtitle' => 'Commencez par créer votre première évaluation.',
        'empty_search_title' => 'Aucune évaluation trouvée',
        'empty_search_subtitle' => 'Essayez de modifier vos critères de recherche ou de filtrage.',
        'reset_filters' => 'Réinitialiser les filtres',
    ],

    'assessment_header' => [
        'questions_count' => ':count questions',
        'created_on' => 'Créé le :date',
    ],

    'assessment_general_config' => [
        'title' => 'Paramètres généraux',
        'published_label' => 'Publié',
        'assessment_title_label' => 'Titre de l\'évaluation',
        'type_label' => 'Type d\'évaluation',
        'type_assignment' => 'Devoir',
        'type_quiz' => 'Quiz',
        'type_exam' => 'Examen',
        'duration_label' => 'Durée (minutes)',
        'class_subject_label' => 'Classe & Matière',
        'class_subject_placeholder' => 'Sélectionner classe et matière',
        'scheduled_date_label' => 'Date planifiée',
        'description_label' => 'Description',
        'description_placeholder' => 'Entrez la description de l\'évaluation...',
        'description_help' => 'Formatage Markdown supporté',
    ],

    // FullscreenModal
    'fullscreen_modal' => [
        'title' => 'Mode plein écran requis',
        'description_line1' => 'Pour des raisons de sécurité, cet examen doit être passé en mode plein écran.',
        'description_line2' => 'Cliquez sur le bouton ci-dessous pour entrer en mode plein écran.',
        'button' => 'Entrer en plein écran',
    ],

    // DataTable
    'datatable' => [
        'items_selected' => ':count élément|:count éléments',
        'items_selected_suffix' => 'sélectionné|sélectionnés',
        'deselect_all' => 'Désélectionner tout',
        'select_all' => 'Sélectionner tout',
        'select_item' => 'Sélectionner l\'élément :id',
        'reset_filters_default' => 'Réinitialiser les filtres',
        'showing_records' => 'Affichage de :from à :to sur :total résultats',
        'per_page' => 'Par page:',
        'previous' => 'Précédent',
        'next' => 'Suivant',
    ],
];
