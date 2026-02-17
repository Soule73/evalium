<?php

declare(strict_types=1);

return [
    // Titres des pages
    'title' => [
        'admin' => 'Tableau de bord administrateur',
        'teacher' => 'Tableau de bord enseignant',
        'student' => 'Tableau de bord étudiant',
        'unified' => 'Tableau de bord',
    ],

    // Admin Dashboard
    'admin' => [
        'total_users' => 'Total utilisateurs',
        'students' => 'Étudiants',
        'teachers' => 'Enseignants',
    ],

    // Teacher Dashboard
    'teacher' => [
        'assessments_created' => 'Évaluations créées',
        'questions_created' => 'Questions créées',
        'students_evaluated' => 'Étudiants évalués',
        'average_score' => 'Note moyenne',
        'recent_assessments' => 'Évaluations récentes',
        'recent_assessments_subtitle' => 'Gérez vos évaluations et suivez les performances de vos étudiants.',
        'create_assessment' => 'Nouvelle évaluation',
        'view_all_assessments' => 'Voir toutes les évaluations',
        'total_classes' => 'Total classes',
        'total_subjects' => 'Total matières',
        'total_assessments' => 'Total évaluations',
        'upcoming_assessments' => 'Évaluations à venir',
        'active_assignments' => 'Affectations actives',
        'active_assignments_subtitle' => 'Vos affectations actuelles de classes et matières.',
        'view_all_classes' => 'Voir toutes les classes',
        'no_active_assignments' => 'Aucune affectation active pour le moment.',
        'past_assessments' => 'Évaluations passées',
        'past_assessments_subtitle' => 'Évaluations qui ont déjà eu lieu.',
        'no_past_assessments' => 'Aucune évaluation passée.',
        'no_recent_assessments' => 'Aucune évaluation récente.',
        'upcoming_assessments_section' => 'Évaluations à venir',
        'upcoming_assessments_subtitle' => 'Évaluations programmées dans un avenir proche.',
        'no_upcoming_assessments' => 'Aucune évaluation à venir.',
        'assessment_title' => 'Titre',
        'class' => 'Classe',
        'subject' => 'Matière',
        'scheduled_at' => 'Planifiée le',
    ],

    // Student Dashboard
    'student' => [
        'greeting' => 'Bonjour, :name !',
        'total_assessments' => 'Total Évaluations',
        'pending_assessments' => 'Évaluations en attente',
        'completed_assessments' => 'Évaluations terminées',
        'graded_assessments' => 'Évaluations corrigées',
        'average_score' => 'Note moyenne',
        'assigned_assessments' => 'Évaluations assignées',
        'view_my_assessments' => 'Voir mes évaluations',
        'view_all_assessments' => 'Voir toutes les évaluations',
        'subjects_overview' => 'Aperçu des matières',
        'completed' => 'terminées',
        'status' => [
            'not_submitted' => 'Non rendu',
            'submitted' => 'Rendu',
            'graded' => 'Corrigé',
        ],
        'table' => [
            'title' => 'Titre',
            'subject' => 'Matière',
            'submitted_at' => 'Rendu le',
            'status' => 'Statut',
            'actions' => 'Actions',
            'view' => 'Voir',
        ],
        'no_assessments' => 'Aucune évaluation',
        'no_assessments_subtitle' => 'Vous n\'avez aucune évaluation assignée pour le moment.',
    ],

    // Unified Dashboard
    'unified' => [
        'my_account' => 'Mon compte',
        'name' => 'Nom',
        'email' => 'Email',
        'permissions' => 'Permissions',
        'active_permissions' => ':count permissions actives',
    ],
];
