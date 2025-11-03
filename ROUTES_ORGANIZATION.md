# Organisation des Routes - Examena

Ce document décrit l'organisation structurée du fichier `routes/web.php`.

## Principes d'Organisation

### 1. Structure Hiérarchique
Les routes sont organisées par **groupes logiques** avec une hiérarchie claire :
- **Public Routes** : Routes accessibles sans authentification
- **Authentication Routes** : Routes de connexion/déconnexion
- **Authenticated Routes** : Routes nécessitant une authentification
  - **Student Routes** : Basées sur le rôle (middleware `role:student`)
  - **Exam Routes** : Basées sur les permissions
  - **Admin Routes** : Basées sur les permissions

### 2. Ordre des Routes Paramétrées
**RÈGLE CRITIQUE** : Les routes spécifiques doivent TOUJOURS être définies AVANT les routes paramétrées.

✅ **BON ORDRE** :
```php
Route::get('/exams/create', ...);      // Route spécifique
Route::get('/exams/{exam}', ...);      // Route paramétrée
```

❌ **MAUVAIS ORDRE** :
```php
Route::get('/exams/{exam}', ...);      // Route paramétrée
Route::get('/exams/create', ...);      // Route spécifique (ne sera jamais atteinte!)
```

**Pourquoi ?** Laravel matche les routes dans l'ordre de définition. Si `/exams/{exam}` est avant `/exams/create`, Laravel capturera "create" comme valeur du paramètre `{exam}`.

### 3. Groupement par Ressource
Chaque ressource utilise des groupes avec :
- **`prefix()`** : Préfixe d'URL commun
- **`name()`** : Préfixe des noms de routes
- **`controller()`** : Contrôleur commun

Exemple :
```php
Route::prefix('admin/users')
    ->name('admin.users.')
    ->controller(UserManagementController::class)
    ->group(function () {
        // Toutes les routes utilisent ce préfixe et ce contrôleur
    });
```

### 4. Application des Middleware
Les middleware sont appliqués :
- Au niveau du **groupe** quand ils s'appliquent à toutes les routes
- Au niveau de la **route individuelle** pour des permissions spécifiques

```php
Route::middleware('auth')->group(function () {
    // Toutes ces routes nécessitent l'authentification
    
    Route::get('/users', 'index')
        ->middleware('permission:view users'); // Permission supplémentaire
});
```

## Structure Détaillée

### 1. Routes Publiques
```
GET  /                  welcome              Page d'accueil
```

### 2. Routes d'Authentification
```
GET   /login           login                Formulaire de connexion
POST  /login           login.attempt        Traitement de la connexion
```

### 3. Routes Authentifiées

#### 3.1 Dashboard & Profil
```
GET   /dashboard       dashboard            Dashboard principal
GET   /profile         profile              Voir le profil
PUT   /profile/{user}  profile.update       Mettre à jour le profil
POST  /logout          logout               Déconnexion
```

#### 3.2 Routes Étudiant (Role-Based)
**Middleware** : `role:student` (basé sur le rôle, non assignable via permissions)

```
GET   /student/exams/{exam}/take                  student.exams.take
POST  /student/exams/{exam}/save-answers          student.exams.save-answers
POST  /student/exams/{exam}/security-violation    student.exams.security-violation
POST  /student/exams/{exam}/abandon               student.exams.abandon
POST  /student/exams/{exam}/submit                student.exams.submit
```

#### 3.3 Routes Examens (Permission-Based)

##### CRUD Examens
**Contrôleur** : `Exam\ExamController`

| Méthode | URI | Nom | Permission | Description |
|---------|-----|-----|------------|-------------|
| GET | `/exams` | exams.index | view exams | Liste des examens |
| GET | `/exams/create` | exams.create | create exams | Formulaire de création |
| POST | `/exams` | exams.store | create exams | Créer un examen |
| POST | `/exams/{exam}/duplicate` | exams.duplicate | create exams | Dupliquer un examen |
| PATCH | `/exams/{exam}/toggle-active` | exams.toggle-active | publish exams | Publier/dépublier |
| GET | `/exams/{exam}/edit` | exams.edit | update exams | Formulaire d'édition |
| PUT | `/exams/{exam}` | exams.update | update exams | Mettre à jour |
| DELETE | `/exams/{exam}` | exams.destroy | delete exams | Supprimer |
| GET | `/exams/{exam}` | exams.show | view exams | Voir un examen |

**Note** : `exams.show` est défini EN DERNIER pour éviter les conflits avec les autres routes paramétrées.

##### Assignations d'Examens
**Contrôleur** : `Exam\AssignmentController`

| Méthode | URI | Nom | Permission |
|---------|-----|-----|------------|
| GET | `/exams/{exam}/assign` | exams.assign | assign exams |
| POST | `/exams/{exam}/assign` | exams.assign.store | create assignments |
| GET | `/exams/{exam}/assignments` | exams.groups | view assignments |
| DELETE | `/exams/{exam}/assignments/{user}` | exams.assignment.remove | delete assignments |

##### Assignations de Groupes
**Contrôleur** : `Exam\GroupAssignmentController`

| Méthode | URI | Nom | Permission |
|---------|-----|-----|------------|
| POST | `/exams/{exam}/assign-groups` | exams.assign.groups | assign group exams |
| DELETE | `/exams/{exam}/groups/{group}` | exams.groups.remove | assign group exams |
| GET | `/exams/{exam}/groups/{group}/details` | exams.group.show | view assignments |

##### Correction d'Examens
**Contrôleur** : `Exam\CorrectionController`

| Méthode | URI | Nom | Permission |
|---------|-----|-----|------------|
| GET | `/exams/{exam}/review/{student}` | exams.review | correct exams |
| POST | `/exams/{exam}/review/{student}` | exams.review.save | grade answers |
| POST | `/exams/{exam}/score/update` | exams.score.update | grade assignments |

##### Résultats et Statistiques
**Contrôleur** : `Exam\ResultsController`

| Méthode | URI | Nom | Permission |
|---------|-----|-----|------------|
| GET | `/exams/{exam}/results/{student}` | exams.results | view exam results |
| GET | `/exams/{exam}/stats` | exams.stats | view reports |

#### 3.4 Routes Admin (Permission-Based)

Toutes les routes admin utilisent le préfixe `/admin` et `admin.` pour les noms.

##### Gestion des Utilisateurs
**Contrôleur** : `Admin\UserManagementController`
**Préfixe** : `/admin/users` | `admin.users.`

| Méthode | URI | Nom | Permission |
|---------|-----|-----|------------|
| GET | `/` | index | view users |
| GET | `/students/{user}` | show.student | view users |
| GET | `/teachers/{user}` | show.teacher | view users |
| POST | `/` | store | create users |
| PUT | `/{user}` | update | update users |
| DELETE | `/{user}` | destroy | delete users |
| PATCH | `/{user}/toggle-status` | toggle-status | toggle user status |
| PUT | `/{user}/change-group` | change-group | manage students |
| POST | `/{id}/restore` | restore | restore users |
| DELETE | `/{id}/force` | force-delete | force delete users |

##### Gestion des Groupes
**Contrôleur** : `Admin\GroupController`
**Préfixe** : `/admin/groups` | `admin.groups.`

| Méthode | URI | Nom | Permission |
|---------|-----|-----|------------|
| GET | `/` | index | view groups |
| GET | `/create` | create | create groups |
| POST | `/` | store | create groups |
| POST | `/bulk-activate` | bulk-activate | toggle group status |
| POST | `/bulk-deactivate` | bulk-deactivate | toggle group status |
| GET | `/{group}` | show | view groups |
| GET | `/{group}/edit` | edit | update groups |
| PUT | `/{group}` | update | update groups |
| DELETE | `/{group}` | destroy | delete groups |
| GET | `/{group}/assign-students` | assign-students | manage group students |
| POST | `/{group}/assign-students` | store-students | manage group students |
| POST | `/{group}/bulk-remove-students` | bulk-remove-students | manage group students |
| DELETE | `/{group}/students/{student}` | remove-student | manage group students |

##### Gestion des Niveaux
**Contrôleur** : `Admin\LevelManagementController`
**Préfixe** : `/admin/levels` | `admin.levels.`

| Méthode | URI | Nom | Permission |
|---------|-----|-----|------------|
| GET | `/` | index | view levels |
| GET | `/create` | create | create levels |
| POST | `/` | store | create levels |
| GET | `/{level}/edit` | edit | update levels |
| PUT | `/{level}` | update | update levels |
| DELETE | `/{level}` | destroy | delete levels |
| PATCH | `/{level}/toggle-status` | toggle-status | update levels |

##### Gestion des Rôles et Permissions
**Contrôleur** : `Admin\RolePermissionController`
**Préfixe** : `/admin/roles` | `admin.roles.`

| Méthode | URI | Nom | Permission |
|---------|-----|-----|------------|
| GET | `/` | index | view roles |
| GET | `/create` | create | create roles |
| POST | `/` | store | create roles |
| GET | `/permissions` | permissions.index | view permissions |
| POST | `/permissions` | permissions.store | create permissions |
| DELETE | `/permissions/{permission}` | permissions.destroy | delete permissions |
| GET | `/{role}/edit` | edit | update roles |
| PUT | `/{role}` | update | update roles |
| DELETE | `/{role}` | destroy | delete roles |
| POST | `/{role}/sync-permissions` | sync-permissions | assign permissions |

## Bonnes Pratiques

### ✅ À FAIRE
1. **Toujours** définir les routes spécifiques AVANT les routes paramétrées
2. **Utiliser** `prefix()`, `name()`, et `controller()` pour regrouper logiquement
3. **Appliquer** les middleware au niveau du groupe quand possible
4. **Organiser** les routes par ressource (users, groups, levels, etc.)
5. **Documenter** les permissions requises dans les commentaires

### ❌ À ÉVITER
1. ❌ Définir des routes paramétrées avant des routes spécifiques
2. ❌ Dupliquer les préfixes et contrôleurs dans chaque route
3. ❌ Mélanger différentes ressources dans le même groupe
4. ❌ Appliquer des middleware identiques sur chaque route individuellement
5. ❌ Créer des noms de routes incohérents

## Vérification des Routes

Pour vérifier que les routes sont correctement enregistrées :

```bash
# Lister toutes les routes
php artisan route:list

# Filtrer par préfixe
php artisan route:list --path=exams
php artisan route:list --path=admin

# Filtrer par nom
php artisan route:list --name=exams.create

# Filtrer par méthode
php artisan route:list --method=GET
```

## Résolution de Problèmes

### Problème : Route 404 "Not Found"
**Symptôme** : Une route comme `/exams/create` retourne 404

**Causes possibles** :
1. Route paramétrée définie avant la route spécifique
2. Route manquante dans `web.php`
3. Middleware bloquant l'accès

**Solution** :
1. Vérifier l'ordre des routes (spécifiques avant paramétrées)
2. Vérifier que la route existe : `php artisan route:list --name=nom.route`
3. Vérifier les permissions/rôles requis

### Problème : Conflit de Routes
**Symptôme** : Plusieurs routes matchent la même URL

**Solution** :
1. Utiliser `php artisan route:list` pour voir toutes les routes
2. Réorganiser pour que les plus spécifiques soient en premier
3. Utiliser des préfixes différents si nécessaire

## Maintenance

### Ajouter une Nouvelle Route
1. Identifier le groupe logique (Exam, Admin, Student, etc.)
2. Placer la route dans le bon groupe avec `prefix()`, `name()`, `controller()`
3. Respecter l'ordre : spécifiques avant paramétrées
4. Appliquer les middleware appropriés
5. Tester avec `php artisan route:list`

### Modifier une Route Existante
1. Localiser la route dans `routes/web.php`
2. Effectuer les modifications
3. Vérifier qu'aucun conflit n'a été créé
4. Tester avec `php artisan route:list`
5. Tester l'accès à la route

---

**Dernière mise à jour** : Date actuelle  
**Contributeurs** : Équipe Examena
