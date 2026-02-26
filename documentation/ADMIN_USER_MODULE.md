# Admin / User Management Module — Audit & Validation

## Overview

The Admin/User Management module covers user CRUD operations for all roles except students, teacher management as seen from the admin panel, role and permission configuration, and the admin dashboard. It is operated by users with the `admin` or `super_admin` role. `super_admin` has supremacy over all targets; `admin` cannot manage other admins or super admins.

---

## Architecture

### Controllers

| Controller | Namespace | Responsibility |
|---|---|---|
| `UserController` | `Admin` | CRUD, soft delete, restore, force delete, toggle status, secure credentials endpoint |
| `TeacherController` | `Admin` | Teacher creation, listing, show with teaching history, deactivation, deletion |
| `RoleController` | `Admin` | Role index, edit, sync permissions |
| `AdminDashboardController` | `Admin` | Dashboard statistics |

### Services

| Service | Location | Responsibility |
|---|---|---|
| `UserManagementService` | `Services/Admin/` | User store (with random password + notification), update, soft delete, toggle status, restore, force delete |
| `RoleService` | `Services/Admin/` | Role + permission CRUD, lock enforcement, category grouping |
| `AdminDashboardService` | `Services/Admin/` | Aggregate stats: user counts by role, classes, enrollments |

### Policies

| Policy | Key methods |
|---|---|
| `UserPolicy` | `view`, `create`, `update`, `delete`, `restore`, `forceDelete`, `toggleStatus`, `manageStudents`, `manageTeachers`, `manageAdmins` |
| Spatie Role Policy | Managed via Gates registered by Spatie Permission |

### Form Requests

| Request | Authorization | Validation |
|---|---|---|
| `CreateUserRequest` | `can('create', User::class)` | Name, email unique, role, `UserValidationRules` trait |
| `EditUserRequest` | `can('update', $user)` | Same + nullable password (min 8, confirmed) |
| `SyncRolePermissionsRequest` | `can('update', $role)` | Array of permission IDs |

---

## Role Hierarchy & Permission Model

```
super_admin
  └── Can manage admins, super_admins, teachers (create/update/delete/toggle)
  └── Can assign any role
  └── Can rename custom roles and sync permissions on editable roles

admin
  └── Can manage teachers and students (if permissions granted)
  └── Cannot manage other admins or super_admins
  └── Cannot delete or force-delete privileged accounts

teacher / student
  └── Standard role holders; managed by admin/super_admin
  └── Cannot access admin panel
```

Roles `super_admin`, `teacher`, `student` are **locked** — their permissions cannot be modified via the UI (`RoleService::LOCKED_ROLES`). The `admin` role is editable. Roles `super_admin`, `admin`, `teacher`, `student` are **system roles** — they cannot be deleted or renamed.

---

## Data Flow

### User Creation

```
Admin POSTs to admin.users.store
  → CreateUserRequest: authorized via can('create', User::class) → UserPolicy::create()
  → UserController::store()
      → authorize('create', User::class)     (double-checked)
      → UserManagementService::store($validated)
          → DB transaction
          → User::create() + assignRole()
          → Str::random(12) password generated and hashed
          → UserCredentialsNotification dispatched (optional)
      → session()->put('new_user_credentials', ['id', 'name', 'email', 'password'])
      → session()->flash('has_new_user', true)
      → back()->flashSuccess()

Frontend calls GET admin.users.pending-credentials
  → UserController::pendingCredentials()
      → session()->pull('new_user_credentials')   // consumed once, then deleted
      → JsonResponse with credentials (including id for profile link)
```

### Teacher Management

```
Admin POSTs to admin.teachers.store
  → CreateUserRequest authorized
  → TeacherController::store()
      → $validated['role'] = 'teacher'    // role forced server-side
      → UserManagementService::store($validated)
      → session()->put('new_user_credentials', ['id', 'name', 'email', 'password'])
      → back()->flashSuccess()

TeacherController::show($user)
  → Verifies $user->isTeacher()
  → Calls isTeachingInCurrentYear() → checks classSubjects with active semester
  → Loads assessments via AdminAssessmentRepositoryInterface
  → Inertia: Admin/Teachers/Show
```

### Role / Permission Management

```
Admin navigates to admin.roles.index
  → RoleController::index()
      → authorize('viewAny', Role::class)
      → RoleService::getRolesWithPermissionsPaginated()
      → Each role annotated with is_editable flag
      → RoleService::groupPermissionsByCategory() for sidebar grouping
      → Inertia: Admin/Roles/Index

Admin syncs permissions on a role
  → SyncRolePermissionsRequest: authorize via can('update', $role)
  → RoleController::syncPermissions()
      → authorize('update', $role)
      → RoleService::syncRolePermissions()
          → Throws RoleException::permissionsLocked() if role is in LOCKED_ROLES
          → role->syncPermissions($permissionIds)
      → redirect->flashSuccess()
```

---

## Authorization Summary

| Action | Required |
|---|---|
| View users list | `viewAny users` permission |
| Create user | `create users` permission |
| Update own profile | Self-check in UserPolicy |
| Update admin/super_admin | `super_admin` role (enforced in UserPolicy) |
| Delete admin/super_admin | `super_admin` role (enforced in UserPolicy) |
| Toggle status of admin | `super_admin` role |
| Restore soft-deleted user | `restore users` permission |
| Force delete | `force delete users` permission |
| Manage admin accounts (UI gate) | `super_admin` role (see B5 fix below) |
| Sync role permissions | `update roles` permission |
| Locked roles (super_admin, teacher, student) | Cannot sync permissions — `RoleException` |

---

## Architecture Notes

### `UserManagementService` — Clean Transaction Pattern

`store()` and `update()` both run inside `DB::transaction()`. If role assignment fails after user creation, the transaction rolls back — no orphaned users. Exceptions are caught, logged, and re-thrown for the controller to handle.

### Secure Credential Delivery

Passwords are never sent via Inertia shared props. The auth flow uses:

1. Controller puts credentials in `session('new_user_credentials')`  
2. Frontend detects `has_new_user` flash and calls `GET /admin/users/pending-credentials`  
3. `pendingCredentials()` uses `session()->pull()` — one-time read, immediately cleared  
4. Frontend displays the modal and the credentials disappear from server memory  

This prevents credentials from appearing in Inertia's bootstrapped page data or being retrievable on page refresh.

### `AdminDashboardService` — Single Raw SQL for Counts

User counts per role are obtained via a single `DB::select()` with `CASE WHEN` aggregation on the `model_has_roles` pivot — avoids N+1 per role. Academic-year-scoped stats (classes, enrollments) are fetched separately only when an `$academicYearId` is provided.

### `RoleService` — Category Grouping

Permissions are categorized using keyword matching (`CATEGORY_MAPPINGS`). Unknown permissions that match no keyword are silently excluded from grouped display. The category keys use `__("permissions.category_*")` for localization.

---

## Performance Notes

- `getRolesWithPermissionsPaginated()` uses `with('permissions')->withCount('permissions')` — no N+1 on permission loading
- `TeacherController::show()` calls `isTeachingInCurrentYear()` which executes a `whereHas()` subquery — efficient for bounded class sets
- No raw SQL in UserManagementService — Eloquent ORM used throughout

---

## Test Coverage

| Test File | Scenarios |
|---|---|
| `UserPolicyTest` | update/delete/toggleStatus for super_admin vs admin targets; self-protection; manageAdmins gate |