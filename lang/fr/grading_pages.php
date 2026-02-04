<?php

declare(strict_types=1);

return [
    'page_titles' => [
        'index' => 'Correction',
        'show' => 'Corriger étudiant',
    ],

    'index' => [
        'title' => 'Correction - :assessment',
        'section_title' => 'Soumissions étudiants',
        'section_subtitle' => ':count soumission(s) à réviser',
        'student_label' => 'Étudiant',
        'status_label' => 'Statut',
        'score_label' => 'Note',
        'submitted_at' => 'Soumis le',
        'actions_label' => 'Actions',
        'not_submitted' => 'Non soumis',
        'graded' => 'Corrigé',
        'pending' => 'En attente',
        'grade' => 'Corriger',
        'review' => 'Réviser',
        'back_to_assessment' => 'Retour à l\'évaluation',
        'empty_title' => 'Aucune soumission',
        'empty_subtitle' => 'Les étudiants n\'ont pas encore soumis leurs évaluations.',
    ],

    'show' => [
        'title' => 'Correction - :student - :assessment',
        'correction_title' => 'Correction de :student',
        'back_to_grading' => 'Retour aux corrections',
        'save_grades' => 'Enregistrer les notes',
        'saving' => 'Enregistrement...',
        'total_score' => 'Note totale',
        'percentage' => 'Pourcentage',
        'status' => 'Statut',
        'questions_correction' => 'Questions & Correction',
        'question_score_label' => 'Note (max :max)',
        'auto_graded_info' => 'Corrigé automatiquement',
        'manual_grading_required' => 'Correction manuelle requise',
        'question_comment_label' => 'Commentaire',
        'question_comment_placeholder' => 'Ajouter un commentaire pour cette réponse...',
        'teacher_notes_label' => 'Notes générales',
        'teacher_notes_placeholder' => 'Ajouter des notes générales pour l\'étudiant...',
        'teacher_notes_help' => 'Ces notes seront visibles par l\'étudiant',
        'confirm_save_title' => 'Enregistrer les notes',
        'confirm_save_message' => 'Enregistrer les notes et commentaires pour :student ?',
        'confirm_save' => 'Enregistrer',
        'cancel' => 'Annuler',
    ],
];
