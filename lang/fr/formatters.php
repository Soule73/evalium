<?php

return [
    // Duration formatting
    'duration_min' => ':value min',
    'duration_hours' => ':value h',
    'duration_hours_min' => ':hours h :minutes min',

    // Exam status
    'exam_status_active' => 'Actif',
    'exam_status_inactive' => 'Inactif',

    // Question types
    'question_type_multiple' => 'Choix multiples',
    'question_type_one_choice' => 'Choix unique',
    'question_type_boolean' => 'Vrai/Faux',
    'question_type_text' => 'Réponse libre',

    // User roles
    'role_admin' => 'Administrateur',
    'role_super_admin' => 'Super Administrateur',
    'role_teacher' => 'Enseignant',
    'role_student' => 'Étudiant',

    // Assignment statuses
    'assignment_graded' => 'Noté',
    'assignment_submitted' => 'Soumis',
    'assignment_not_started' => 'Non commencé',
    'assignment_not_assigned' => 'Non commencé',
    'assignment_all_statuses' => 'Tous les statuts',

    // Security violations
    'security_tab_switch' => 'Changement d\'onglet détecté',
    'security_fullscreen_exit' => 'Sortie du mode plein écran détectée',
    'security_violation_default' => 'Violation de sécurité détectée',

    // Deadline warnings
    'deadline_exam_finished' => 'Examen terminé',
    'deadline_minutes_remaining' => ':minutes minutes restantes',
    'deadline_hours_remaining' => ':hours heures restantes',
    'deadline_days_remaining' => ':days jours restants',
    'deadline_day_remaining' => ':days jour restant',

    // Relative time
    'relative_time_now' => 'À l\'instant',
    'relative_time_minutes_ago' => 'Il y a :minutes min',
    'relative_time_hours_ago' => 'Il y a :hours h',
    'relative_time_days_ago' => 'Il y a :days jour|Il y a :days jours',

    // Exam utils
    'partial_score_mcq' => 'Note partielle (QCM) : :score / :total points',
    'score_format' => ':score / :total points',
    'correction_in_progress' => 'En cours de correction',
    'not_graded' => 'Non noté',

    // Validation
    'validation_required' => 'Ce champ est requis',
    'validation_min_length' => 'Minimum :min caractères requis',
    'validation_max_length' => 'Maximum :max caractères autorisés',
    'validation_invalid_format' => 'Format invalide',
    'validation_min_value' => 'La valeur doit être au moins :min',
    'validation_max_value' => 'La valeur ne peut pas dépasser :max',
    'validation_invalid_question_type' => 'Type de question invalide',
    'validation_no_file_selected' => 'Aucun fichier sélectionné',
    'validation_file_type_not_allowed' => 'Type de fichier non autorisé. Types acceptés: :types',
    'validation_file_too_large' => 'Fichier trop volumineux. Taille maximale: :maxMB MB',
];
