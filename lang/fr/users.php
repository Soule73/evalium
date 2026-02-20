<?php

declare(strict_types=1);

return [
    // Titre et navigation
    'title' => 'Utilisateurs',
    'list' => 'Liste des utilisateurs',
    'manage' => 'Gérer les utilisateurs',
    'profile' => 'Profil',

    // Rôles
    'student' => 'Étudiant',
    'teacher' => 'Enseignant',
    'admin' => 'Administrateur',
    'super_admin' => 'Super Admin',
    'unknown' => 'Inconnu',
    'students' => 'Étudiants',
    'teachers' => 'Enseignants',
    'role' => 'Rôle',

    // Actions CRUD
    'create' => 'Créer un utilisateur',
    'edit' => 'Modifier l\'utilisateur',
    'delete' => 'Supprimer l\'utilisateur',
    'view' => 'Voir l\'utilisateur',
    'restore' => 'Restaurer l\'utilisateur',
    'force_delete' => 'Supprimer définitivement',

    // Messages de succès
    'created' => 'Utilisateur créé avec succès !',
    'updated' => 'Utilisateur mis à jour avec succès !',
    'deleted' => 'Utilisateur supprimé avec succès !',
    'restored' => 'Utilisateur restauré avec succès !',
    'force_deleted' => 'Utilisateur supprimé définitivement !',
    'status_toggled' => 'Statut de l\'utilisateur modifié avec succès !',

    // Messages d'erreur
    'not_found' => 'Utilisateur introuvable',
    'unauthorized' => 'Vous n\'êtes pas autorisé à gérer cet utilisateur',
    'cannot_delete_self' => 'Vous ne pouvez pas supprimer votre propre compte',
    'cannot_delete_admin' => 'Impossible de supprimer un administrateur',
    'email_exists' => 'Cette adresse e-mail est déjà utilisée',

    // Statut
    'status' => 'Statut',
    'active' => 'Actif',
    'inactive' => 'Inactif',
    'toggle_status' => 'Changer le statut',
    'activate' => 'Activer',
    'deactivate' => 'Désactiver',

    // Informations
    'name' => 'Nom',
    'email' => 'E-mail',
    'password' => 'Mot de passe',
    'group' => 'Groupe',
    'change_group' => 'Changer de groupe',
    'no_group' => 'Aucun groupe',
    'created_at' => 'Créé le',
    'updated_at' => 'Mis à jour le',
    'deleted_at' => 'Supprimé le',

    // Filtres
    'filter_by_role' => 'Filtrer par rôle',
    'filter_by_status' => 'Filtrer par statut',
    'filter_by_group' => 'Filtrer par groupe',
    'all_users' => 'Tous les utilisateurs',
    'active_only' => 'Actifs uniquement',
];
