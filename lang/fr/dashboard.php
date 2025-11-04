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
        'exams_created' => 'Examens créés',
        'questions_created' => 'Questions créées',
        'students_evaluated' => 'Étudiants évalués',
        'average_score' => 'Note moyen',
        'recent_exams' => 'Examens récents',
        'recent_exams_subtitle' => 'Gérez vos examens et suivez les performances de vos étudiants.',
        'create_exam' => 'Créer un examen',
        'view_all_exams' => 'Voir tous les examens',
    ],

    // Student Dashboard
    'student' => [
        'greeting' => 'Bonjour, :name !',
        'total_exams' => 'Total Examens',
        'pending_exams' => 'Examens en attente',
        'completed_exams' => 'Examens terminés',
        'average_score' => 'Note moyen',
        'assigned_exams' => 'Examens assignés',
        'view_my_exams' => 'Voir mes examens',
        'view_all_exams' => 'Voir tous les examens',
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
