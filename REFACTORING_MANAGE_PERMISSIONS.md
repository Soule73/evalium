# Refactorisation des permissions - Suppression des permissions "manage"

## Objectif

Simplifier le système de permissions en supprimant les permissions globales `manage users`, `manage groups`, `manage levels`, et `manage roles`, et en utilisant à la place les permissions spécifiques CRUD (`view`, `create`, `update`, `delete`).

## Permissions supprimées

```typescript
❌ 'manage users'   → Remplacé par: view users, create users, update users, delete users
❌ 'manage groups'  → Remplacé par: view groups, create groups, update groups, delete groups  
❌ 'manage levels'  → Remplacé par: view levels, create levels, update levels, delete levels
❌ 'manage roles'   → Remplacé par: view roles, create roles, update roles, delete roles
```

## Permissions conservées

Les permissions `manage` suivantes sont conservées car elles ont une signification spécifique :

```typescript
✅ 'manage students'       - Gestion spécifique des étudiants
✅ 'manage teachers'       - Gestion spécifique des enseignants  
✅ 'manage admins'         - Gestion spécifique des administrateurs
✅ 'manage group students' - Assigner/retirer des étudiants d'un groupe
```

## Modifications du Seeder

### database/seeders/RoleAndPermissionSeeder.php

**Permissions retirées de la liste :**
- `manage users`
- `manage groups`
- `manage levels`
- `manage roles`

**Total permissions :** 65 → 61 permissions

**Rôle Admin - Avant (31 permissions) → Après (28 permissions) :**

```diff
// Users
'view users',
'create users',
'update users',
+'delete users',
-'manage users',
'manage students',
'manage teachers',
'toggle user status',

// Groups
'view groups',
'create groups',
'update groups',
'delete groups',
-'manage groups',
'manage group students',
'assign group exams',
'toggle group status',

// Levels
'view levels',
'create levels',
'update levels',
'delete levels',
-'manage levels',

// Roles
'view roles',
'create roles',
'update roles',
'delete roles',
-'manage roles',
```

## Modifications Frontend

### 1. Sidebar.tsx

**Avant :**
```typescript
const canManageUsers = hasPermission(auth.permissions, 'manage users');
const canManageGroups = hasPermission(auth.permissions, 'manage groups');
const canManageRoles = hasPermission(auth.permissions, 'manage roles');

const hasAdminCapabilities = canManageUsers || canManageGroups || canManageRoles;

if (hasAdminCapabilities) {
    if (canManageUsers) {
        navItems.push({ name: 'Utilisateurs', ... });
    }
    if (canManageGroups) {
        navItems.push({ name: 'Groupes', ... });
        navItems.push({ name: 'Niveaux', ... });
    }
    if (canManageRoles) {
        navItems.push({ name: 'Rôles & Permissions', ... });
    }
}
```

**Après :**
```typescript
const canViewUsers = hasPermission(auth.permissions, 'view users');
const canViewGroups = hasPermission(auth.permissions, 'view groups');
const canViewRoles = hasPermission(auth.permissions, 'view roles');
const canViewLevels = hasPermission(auth.permissions, 'view levels');

const hasAdminCapabilities = canViewUsers || canViewGroups || canViewRoles || canViewLevels;

if (hasAdminCapabilities) {
    if (canViewUsers) {
        navItems.push({ name: 'Utilisateurs', ... });
    }
    if (canViewGroups) {
        navItems.push({ name: 'Groupes', ... });
    }
    if (canViewLevels) {
        navItems.push({ name: 'Niveaux', ... });
    }
    if (canViewRoles) {
        navItems.push({ name: 'Rôles & Permissions', ... });
    }
}
```

### 2. Pages Admin

#### Users/Index.tsx
```diff
-const canCreateUsers = hasPermission(auth.permissions, 'manage users');
-const canUpdateUsers = hasPermission(auth.permissions, 'manage users');
-const canToggleUserStatus = hasPermission(auth.permissions, 'manage users');
+const canCreateUsers = hasPermission(auth.permissions, 'create users');
+const canUpdateUsers = hasPermission(auth.permissions, 'update users');
+const canToggleUserStatus = hasPermission(auth.permissions, 'toggle user status');
```

#### Groups/Index.tsx
```diff
-const canManageGroups = hasPermission(auth.permissions, 'manage groups');
+const canCreateGroups = hasPermission(auth.permissions, 'create groups');
 const canViewGroups = hasPermission(auth.permissions, 'view groups');
 const canUpdateGroups = hasPermission(auth.permissions, 'update groups');
 const canToggleStatus = hasPermission(auth.permissions, 'toggle group status');

-actions={canManageGroups && (
+actions={canCreateGroups && (
     <Button onClick={handleCreateGroup}>
         Créer un groupe
     </Button>
)}
```

#### Groups/AssignStudents.tsx
```diff
-const canManageGroups = hasPermission(auth.permissions, 'manage groups');
+const canManageGroupStudents = hasPermission(auth.permissions, 'manage group students');

-enableSelection: canManageGroups,
-selectionActions: canManageGroups ? (selectedIds) => (
+enableSelection: canManageGroupStudents,
+selectionActions: canManageGroupStudents ? (selectedIds) => (
     <Button onClick={() => handleAssignStudents(selectedIds)}>
         Assigner ({selectedIds.length})
     </Button>
) : undefined,

-{!canManageGroups ? (
+{!canManageGroupStudents ? (
     <div>Permission insuffisante</div>
) : (
     <DataTable ... />
)}
```

#### Levels/Index.tsx
```diff
-const canManageGroups = hasPermission(auth.permissions, 'manage groups');
+const canCreateLevels = hasPermission(auth.permissions, 'create levels');
+const canUpdateLevels = hasPermission(auth.permissions, 'update levels');
+const canDeleteLevels = hasPermission(auth.permissions, 'delete levels');

// Toggle statut
-render: (level) => canManageGroups ? (
+render: (level) => canUpdateLevels ? (
     <Toggle ... />
) : (
     <Badge ... />
)

// Actions
-render: (level) => canManageGroups ? (
+render: (level) => (canUpdateLevels || canDeleteLevels) ? (
     <div className="flex gap-2">
+        {canUpdateLevels && (
             <Button onClick={() => handleEdit(level.id)}>
                 Modifier
             </Button>
+        )}
+        {canDeleteLevels && (
             <Button onClick={() => setDeleteModal(...)}>
                 Supprimer
             </Button>
+        )}
     </div>
) : null

// Bouton Créer
-actions={canManageGroups && (
+actions={canCreateLevels && (
     <Button onClick={handleCreate}>
         Nouveau niveau
     </Button>
)}
```

#### Roles/Index.tsx
```diff
-const canManageRoles = hasPermission(auth.permissions, 'manage roles');
+const canCreateRoles = hasPermission(auth.permissions, 'create roles');
+const canUpdateRoles = hasPermission(auth.permissions, 'update roles');
+const canDeleteRoles = hasPermission(auth.permissions, 'delete roles');

// Actions
-render: (role) => canManageRoles ? (
+render: (role) => (canUpdateRoles || canDeleteRoles) ? (
     <div className="flex gap-2">
+        {canUpdateRoles && (
             <Button onClick={() => handleEdit(role.id)}>
                 {isSystemRole(role.name) ? 'Voir' : 'Modifier'}
             </Button>
+        )}
+        {!isSystemRole(role.name) && canDeleteRoles && (
             <Button onClick={() => setDeleteModal(...)}>
                 Supprimer
             </Button>
+        )}
     </div>
) : null

// Bouton Créer
-actions={canManageRoles && (
+actions={canCreateRoles && (
     <Button onClick={handleCreate}>
         Nouveau rôle
     </Button>
)}
```

## Résultats après synchronisation

### Permissions par rôle

**Super Admin : 65 permissions** (toutes)

**Admin : 28 permissions** (-3 permissions)
- ✅ Users : view, create, update, delete, toggle status, manage students, manage teachers
- ✅ Groups : view, create, update, delete, manage students, assign exams, toggle status
- ✅ Levels : view, create, update, delete
- ✅ Roles : view, create, update, delete
- ✅ Exams : view, view any, view results
- ✅ Dashboard : admin, reports

**Teacher : 23 permissions** (inchangé)
**Student : 7 permissions** (inchangé)

## Avantages de cette approche

### 1. **Granularité fine**
```typescript
// Avant : Tout ou rien
if (hasPermission('manage users')) {
    // Peut tout faire : créer, modifier, supprimer
}

// Après : Permissions spécifiques
if (hasPermission('create users')) { /* Créer uniquement */ }
if (hasPermission('update users')) { /* Modifier uniquement */ }
if (hasPermission('delete users')) { /* Supprimer uniquement */ }
```

### 2. **Rôles personnalisés flexibles**
Permet de créer des rôles avec des permissions très spécifiques :
- **Modérateur** : `view users`, `update users` (peut modifier mais pas créer ni supprimer)
- **Observateur** : `view users`, `view groups`, `view levels` (lecture seule)
- **Gestionnaire de groupes** : `view groups`, `create groups`, `update groups`, `manage group students`

### 3. **Interface utilisateur adaptée**
Chaque action est conditionnée individuellement :
```typescript
{canViewUsers && <Link to="/users">Voir les utilisateurs</Link>}
{canCreateUsers && <Button>Créer un utilisateur</Button>}
{canUpdateUsers && <Button>Modifier</Button>}
{canDeleteUsers && <Button>Supprimer</Button>}
```

### 4. **Cohérence avec REST/CRUD**
Les permissions suivent le pattern standard :
- `view` → GET/READ
- `create` → POST/CREATE
- `update` → PUT/PATCH/UPDATE
- `delete` → DELETE/DESTROY

### 5. **Meilleure auditabilité**
Plus facile de savoir exactement ce qu'un utilisateur peut faire :
```
Admin John:
  ✅ create users
  ✅ view users
  ❌ delete users (pas cette permission)
```

## Impact sur les rôles personnalisés existants

⚠️ **Important** : Si des rôles personnalisés ont été créés avec les anciennes permissions `manage`, ils continueront de fonctionner car ces permissions existent toujours dans la base de données. Cependant :

1. **Recommandation** : Mettre à jour manuellement ces rôles pour utiliser les nouvelles permissions
2. **Migration douce** : Les anciennes permissions ne sont plus assignées aux rôles système mais restent disponibles
3. **Nettoyage futur** : Ces permissions peuvent être supprimées définitivement après vérification qu'aucun rôle personnalisé ne les utilise

## Tests de validation

### Test 1 : Admin peut créer mais pas supprimer
```php
$admin = User::factory()->create();
$admin->assignRole('admin');

// ✅ Devrait réussir
$this->actingAs($admin)->post('/users', [...]); // create users

// ✅ Devrait réussir  
$this->actingAs($admin)->get('/users'); // view users

// ✅ Devrait réussir
$this->actingAs($admin)->put('/users/1', [...]); // update users

// ✅ Devrait réussir
$this->actingAs($admin)->delete('/users/1'); // delete users (Admin a cette permission)
```

### Test 2 : Rôle personnalisé avec permissions limitées
```php
$moderator = Role::create(['name' => 'moderator']);
$moderator->givePermissionTo(['view users', 'update users']);

$user = User::factory()->create();
$user->assignRole('moderator');

// ✅ Devrait réussir
$this->actingAs($user)->get('/users'); // view users

// ✅ Devrait réussir
$this->actingAs($user)->put('/users/1', [...]); // update users

// ❌ Devrait échouer (403)
$this->actingAs($user)->post('/users', [...]); // pas create users

// ❌ Devrait échouer (403)
$this->actingAs($user)->delete('/users/1'); // pas delete users
```

## Compilation TypeScript

✅ **0 erreurs**
✅ **Tous les types validés**
✅ **Toutes les permissions correctement importées**

## Checklist de déploiement

- [x] Seeder mis à jour (permissions supprimées de la liste et des rôles)
- [x] Sidebar.tsx mis à jour (utilise `view` au lieu de `manage`)
- [x] Users/Index.tsx mis à jour
- [x] Groups/Index.tsx mis à jour
- [x] Groups/AssignStudents.tsx mis à jour
- [x] Levels/Index.tsx mis à jour
- [x] Roles/Index.tsx mis à jour
- [x] Seeder exécuté avec succès
- [x] Permissions vérifiées (Admin : 28 permissions)
- [x] 0 erreurs TypeScript
- [ ] Tests fonctionnels exécutés
- [ ] Documentation utilisateur mise à jour

## Commandes utiles

```bash
# Re-synchroniser les permissions
php artisan db:seed --class=RoleAndPermissionSeeder

# Vérifier les permissions d'un rôle
php artisan tinker
>>> Role::findByName('admin')->permissions->pluck('name');

# Nettoyer les permissions inutilisées (optionnel, après validation)
php artisan tinker
>>> Permission::whereIn('name', [
...     'manage users', 'manage groups', 'manage levels', 'manage roles'
... ])->delete();
```

## Prochaines étapes

1. ✅ Mise à jour du seeder et du frontend
2. ✅ Synchronisation des permissions
3. 📝 Tests fonctionnels manuels
4. 📝 Tests automatisés
5. 📝 Documentation utilisateur
6. 📝 Formation des administrateurs
7. 📝 (Optionnel) Suppression définitive des anciennes permissions `manage`
