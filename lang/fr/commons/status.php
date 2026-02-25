<?php

declare(strict_types=1);

/**
 * Shared status labels for assessments, enrollments and users.
 * Frontend key: t('commons/status.graded'), t('commons/status.all_statuses'), etc.
 */
return [
    // Assessment / assignment statuses
    'not_started' => 'Non commencé',
    'in_progress' => 'En cours',
    'completed' => 'Terminé',
    'submitted' => 'Soumis',
    'graded' => 'Noté',
    'published' => 'Publié',
    'draft' => 'Brouillon',
    'archived' => 'Archivé',

    // User / general statuses
    'active' => 'Actif',
    'inactive' => 'Inactif',
    'deleted' => 'Supprimé',

    // Enrollment statuses
    'withdrawn' => 'Retiré',
    'transferred' => 'Transféré',

    // Assessment types (display labels)
    'supervised' => 'Supervisé',
    'homework' => 'Travail à domicile',

    // Generic filter options
    'all_statuses' => 'Tous les statuts',
    'all_roles' => 'Tous les rôles',
    'all_classes' => 'Toutes les classes',
    'all_subjects' => 'Toutes les matières',
    'all_years' => 'Toutes les années',
    'all_types' => 'Tous les types',
    'all' => 'Tous',
];
