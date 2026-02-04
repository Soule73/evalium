<?php

declare(strict_types=1);

return [
    'assessments' => [
        'index' => [
            'title' => 'Mes Évaluations',
            'heading' => 'Gestion des Évaluations',
            'description' => 'Créez et gérez vos évaluations (examens, devoirs, projets, etc.)',
            'create_button' => 'Créer une Évaluation',
            'no_assessments' => 'Aucune évaluation pour le moment',
            'create_first' => 'Créer votre première évaluation',
        ],

        'create' => [
            'title' => 'Créer une Évaluation',
            'heading' => 'Nouvelle Évaluation',
            'description' => 'Créer une nouvelle évaluation avec des questions',
            'basic_info' => 'Informations de Base',
            'questions_section' => 'Questions',
            'add_question' => 'Ajouter une Question',
            'add_first_question' => 'Ajouter la Première Question',
            'no_questions' => 'Aucune question ajoutée pour le moment',
            'question_number' => 'Question n°:number',
            'expand' => 'Développer',
            'collapse' => 'Réduire',
            'add_choice' => 'Ajouter un Choix',
            'submit' => 'Créer l\'Évaluation',
        ],

        'edit' => [
            'title' => 'Modifier l\'Évaluation',
            'heading' => 'Modifier l\'Évaluation',
            'description' => 'Modifiez les informations de l\'évaluation et les questions',
            'basic_info' => 'Informations de Base',
            'questions_section' => 'Questions',
            'add_question' => 'Ajouter une Question',
            'add_first_question' => 'Ajouter la Première Question',
            'no_questions' => 'Aucune question ajoutée pour le moment',
            'question_number' => 'Question n°:number',
            'expand' => 'Développer',
            'collapse' => 'Réduire',
            'add_choice' => 'Ajouter un Choix',
            'submit' => 'Mettre à Jour l\'Évaluation',
        ],

        'show' => [
            'title' => 'Détails de l\'Évaluation',
            'subtitle' => 'Voir les informations et statistiques de l\'évaluation',
            'basic_info' => 'Informations de Base',
            'type' => 'Type',
            'date' => 'Date',
            'duration' => 'Durée',
            'coefficient' => 'Coefficient',
            'total_assigned' => 'Étudiants Assignés',
            'completed' => 'Terminés',
            'completion_rate' => 'complétés',
            'average_score' => 'Moyenne',
            'highest' => 'Max',
            'lowest' => 'Min',
            'questions' => 'Questions',
            'no_questions' => 'Aucune question dans cette évaluation',
            'question_number' => 'Question n°:number',
            'points' => 'pts',
            'choices' => 'choix',
            'manage_assignments' => 'Gérer les Affectations d\'Étudiants',
            'view_grading' => 'Voir la Correction',
        ],

        'form' => [
            'class_subject' => 'Classe - Matière',
            'title' => 'Titre',
            'description' => 'Description',
            'type' => 'Type',
            'coefficient' => 'Coefficient',
            'duration' => 'Durée (minutes)',
            'assessment_date' => 'Date de l\'Évaluation',
            'publish_immediately' => 'Publier immédiatement',
            'is_published' => 'Publié',
            'question_content' => 'Question',
            'question_type' => 'Type de Question',
            'points' => 'Points',
            'choices' => 'Choix',
            'choice_placeholder' => 'Entrez le texte du choix',
        ],

        'table' => [
            'title' => 'Titre',
            'type' => 'Type',
            'date' => 'Date',
            'duration' => 'Durée',
            'coefficient' => 'Coefficient',
            'status' => 'Statut',
            'completion' => 'Complétion',
        ],

        'filters' => [
            'search_placeholder' => 'Rechercher par titre, classe ou matière...',
            'all_types' => 'Tous les Types',
            'all_status' => 'Tous les Statuts',
            'published' => 'Publié',
            'draft' => 'Brouillon',
        ],

        'types' => [
            'devoir' => 'Devoir',
            'examen' => 'Examen',
            'tp' => 'Travaux Pratiques',
            'controle' => 'Contrôle',
            'projet' => 'Projet',
        ],

        'question_types' => [
            'one_choice' => 'Choix Unique',
            'multiple' => 'Choix Multiple',
            'text' => 'Réponse Textuelle',
            'boolean' => 'Vrai/Faux',
        ],

        'card' => [
            'questions' => 'questions',
            'coefficient' => 'Coef',
            'completion' => 'Complétion',
        ],

        'minutes' => 'min',
        'confirm_delete' => 'Êtes-vous sûr de vouloir supprimer cette évaluation ? Cette action est irréversible.',
        'publish' => 'Publier',
        'unpublish' => 'Dépublier',
        'duplicate' => 'Dupliquer',
    ],
];
