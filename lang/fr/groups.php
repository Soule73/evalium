<?php

declare(strict_types=1);

return [
    // Liste et affichage
    'title' => 'Groupes',
    'list' => 'Liste des groupes',
    'my_groups' => 'Mes groupes',
    'no_groups' => 'Aucun groupe disponible',
    'no_groups_found' => 'Aucun groupe trouvé',

    // Actions CRUD
    'create' => 'Créer un groupe',
    'edit' => 'Modifier le groupe',
    'delete' => 'Supprimer le groupe',
    'view' => 'Voir le groupe',
    'manage' => 'Gérer les groupes',

    // Messages de succès
    'created' => 'Groupe créé avec succès !',
    'updated' => 'Groupe mis à jour avec succès !',
    'deleted' => 'Groupe supprimé avec succès !',
    'activated' => 'Groupe activé avec succès !',
    'deactivated' => 'Groupe désactivé avec succès !',

    // Messages d'erreur
    'not_found' => 'Groupe introuvable',
    'unauthorized' => 'Vous n\'êtes pas autorisé à accéder à ce groupe',
    'cannot_delete_with_students' => 'Impossible de supprimer un groupe contenant des étudiants',
    'cannot_delete_with_exams' => 'Impossible de supprimer un groupe ayant des examens assignés',

    // Étudiants
    'students' => 'Étudiants',
    'student_count' => 'Nombre d\'étudiants',
    'add_students' => 'Ajouter des étudiants',
    'remove_students' => 'Retirer des étudiants',
    'assign_students' => 'Assigner des étudiants',
    'students_added' => 'Étudiants ajoutés avec succès !',
    'students_removed' => 'Étudiants retirés avec succès !',
    'student_removed' => 'Étudiant retiré avec succès !',
    'no_students' => 'Aucun étudiant dans ce groupe',
    'students_assigned' => ':count étudiant(s) assigné(s) avec succès !',

    // Actions en masse
    'bulk_activate' => 'Activer les groupes sélectionnés',
    'bulk_deactivate' => 'Désactiver les groupes sélectionnés',
    'bulk_delete' => 'Supprimer les groupes sélectionnés',
    'bulk_remove_students' => 'Retirer les étudiants sélectionnés',
    'groups_activated' => ':count groupe(s) activé(s) avec succès !',
    'groups_deactivated' => ':count groupe(s) désactivé(s) avec succès !',
    'bulk_students_removed' => ':count étudiant(s) retiré(s) avec succès !',

    // Détails
    'name' => 'Nom du groupe',
    'description' => 'Description',
    'status' => 'Statut',
    'active' => 'Actif',
    'inactive' => 'Inactif',
    'created_at' => 'Créé le',
    'updated_at' => 'Mis à jour le',
];
