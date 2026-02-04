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
        'average_score' => 'Note moyen',
        'recent_assessments' => 'Évaluations récentes',
        'recent_assessments_subtitle' => 'Gérez vos évaluations et suivez les performances de vos étudiants.',
        'create_assessment' => 'Créer une évaluation',
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
        'recent_assessments' => 'Évaluations récentes',
        'recent_assessments_subtitle' => 'Vos évaluations les plus récemment programmées.',
        'view_all_assessments' => 'Voir toutes les évaluations',
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
        'average_score' => 'Note moyen',
        'assigned_assessments' => 'Évaluations assignées',
        'view_my_assessments' => 'Voir mes évaluations',
        'view_all_assessments' => 'Voir toutes les évaluations',
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
