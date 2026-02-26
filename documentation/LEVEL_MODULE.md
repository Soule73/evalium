# Level Module — Technical Documentation

## Overview

The Level module manages educational levels (e.g., `Licence 1`, `Master 2`, `BTS`). Levels are the top-most academic classification and serve as a required foreign key for both `ClassModel` and `Subject`. The module provides full CRUD + active/inactive toggling for admin users.

**Distinctive pattern:** This is the only module in the application that uses the full **Interface / Repository / Service** triad, with both interfaces bound in `AppServiceProvider`. All other modules inject concrete services directly.

---

## Table of Contents

1. [Database Schema](#1-database-schema)
2. [Backend Architecture](#2-backend-architecture)
   - 2.1 [Model](#21-model)
   - 2.2 [Controller](#22-controller)
   - 2.3 [Service Layer](#23-service-layer)
   - 2.4 [Repository Layer](#24-repository-layer)
   - 2.5 [Form Request & Validation](#25-form-request--validation)
   - 2.6 [Policy & Permissions](#26-policy--permissions)
   - 2.7 [Exception](#27-exception)
   - 2.8 [Cache Strategy](#28-cache-strategy)
3. [Frontend Architecture](#3-frontend-architecture)
   - 3.1 [Pages](#31-pages)
   - 3.2 [Components](#32-components)
   - 3.3 [Hook — useListLevels](#33-hook--uselistlevels)
   - 3.4 [TypeScript Types](#34-typescript-types)
4. [Route Map](#4-route-map)
5. [Interface Bindings](#5-interface-bindings)
6. [Testing](#6-testing)
7. [Architectural Decisions](#7-architectural-decisions)

---

## 1. Database Schema

### `levels`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | varchar(255) | Unique |
| `code` | varchar(255) | Unique. Short identifier, e.g. `L1`, `M2` |
| `description` | text | Nullable |
| `order` | integer | Display sort position. Default: 0 |
| `is_active` | boolean | Default: `true` |
| `created_at` / `updated_at` | timestamp | |

### Relations to other tables

- `classes` (`class_models`) — `level_id FK` (HasMany from Level)
- `subjects` — `level_id FK` (HasMany from Level)

A level **cannot be deleted** if it has associated classes (enforced in `LevelService::deleteLevel()` via `LevelException::hasClasses()`).

---

## 2. Backend Architecture

### 2.1 Model

#### `App\Models\Level`

```
app/Models/Level.php
```

- `$fillable`: `name`, `code`, `description`, `order`, `is_active`
- `casts()`: `is_active` → `boolean`, `order` → `integer`
- Cache management is handled exclusively by `LevelService` — the model has no `booted()` cache hooks

**Relationships:**

| Method | Type | Target |
|---|---|---|
| `classes()` | HasMany | `ClassModel` |
| `subjects()` | HasMany | `Subject` |

**Scopes:**

| Scope | Effect |
|---|---|
| `scopeActive($query)` | `WHERE is_active = true` |
| `scopeOrdered($query)` | `ORDER BY order ASC, name ASC` |

---

### 2.2 Controller

#### `App\Http\Controllers\Admin\LevelController`

```
app/Http/Controllers/Admin/LevelController.php
```

Thin controller. Uses traits `AuthorizesRequests` and `HandlesIndexRequests`. Injects two interfaces via constructor: `LevelServiceInterface` (mutations) and `LevelRepositoryInterface` (queries).

| Method | HTTP | Action |
|---|---|---|
| `index(Request)` | GET | Lists levels with filters (search, status, pagination) |
| `create()` | GET | Shows creation form |
| `store(LevelRequest)` | POST | Creates level via service |
| `edit(Level)` | GET | Shows edit form with level data |
| `update(LevelRequest, Level)` | PUT | Updates level via service |
| `destroy(Level)` | DELETE | Deletes level (catches `LevelException` → flash error) |
| `toggleStatus(Level)` | PATCH | Toggles `is_active` via service |

**Authorization pattern** — each method calls `$this->authorize()` before delegating:

```php
// Consistent mapping between controller actions and policy methods:
index()        → authorize('viewAny', Level::class)
create()       → authorize('create', Level::class)
store()        → delegated to LevelRequest::authorize()
edit()         → authorize('update', $level)
update()       → authorize('update', $level) + LevelRequest::authorize()
destroy()      → authorize('delete', $level)
toggleStatus() → authorize('toggleStatus', $level)
```

---

### 2.3 Service Layer

#### `App\Contracts\Services\LevelServiceInterface`

```
app/Contracts/Services/LevelServiceInterface.php
```

| Method signature | Description |
|---|---|
| `createLevel(array): Level` | Create + cache invalidation |
| `updateLevel(Level, array): Level` | Update + cache invalidation |
| `deleteLevel(Level): bool` | Delete (throws `LevelException` if has classes) + cache invalidation |
| `toggleStatus(Level): Level` | Flip `is_active` + cache invalidation |

#### `App\Services\Admin\LevelService`

```
app/Services/Admin/LevelService.php
```

Implements `LevelServiceInterface`. All four public methods call `$this->invalidateClassesCache()` after mutation.

**Cache invalidation:**

```php
private const CACHE_KEY_CLASSES = 'classes_active_with_levels';

private function invalidateClassesCache(): void
{
    Cache::forget(self::CACHE_KEY_CLASSES);
}
```

The cache key `classes_active_with_levels` is consumed by the class selection dropdowns elsewhere in the application (class creation forms). It is **not** managed by `CacheService` (level-specific cache, confined to this module).

---

### 2.4 Repository Layer

#### `App\Contracts\Repositories\LevelRepositoryInterface`

```
app/Contracts/Repositories/LevelRepositoryInterface.php
```

Single method: `getLevelsWithPagination(array): LengthAwarePaginator`.

#### `App\Repositories\Admin\LevelRepository`

```
app/Repositories/Admin/LevelRepository.php
```

Uses `Paginatable` trait. Builds query via private `buildLevelQuery()`:

- `withCount(['classes'])` — always eager-counts associated classes (displayed in list, used to disable Delete button)
- `->ordered()` scope applied before pagination
- Filters: `search` (name, code, description LIKE), `status` ('0'/'1'/''/null)

---

### 2.5 Form Request & Validation

#### `App\Http\Requests\Admin\LevelRequest`

```
app/Http/Requests/Admin/LevelRequest.php
```

Single Form Request for both `store` and `update`. Authorization delegates to the policy based on route context:

```php
public function authorize(): bool
{
    $level = $this->route('level');

    return $level instanceof Level
        ? $this->user()->can('update', $level)
        : $this->user()->can('create', Level::class);
}
```

**Validation rules:**

| Field | Rules |
|---|---|
| `name` | required, string, max:255, unique ignoring current `$level->id` |
| `code` | required, string, max:50, unique ignoring current `$level->id` |
| `description` | nullable, string, max:1000 |
| `order` | required, integer, min:0 |
| `is_active` | boolean |

---

### 2.6 Policy & Permissions

#### `App\Policies\LevelPolicy`

```
app/Policies/LevelPolicy.php
```

| Policy method | Permission required | Used by |
|---|---|---|
| `viewAny()` | `view levels` | `index()` |
| `view()` | `view levels` | (unused by controller) |
| `create()` | `create levels` | `create()` + `LevelRequest::authorize()` |
| `update()` | `update levels` | `edit()`, `update()`, `LevelRequest::authorize()` |
| `delete()` | `delete levels` | `destroy()` |
| `toggleStatus()` | `update levels` | `toggleStatus()` |
| `manage()` | `update levels` OR `delete levels` | (unused — for external checks) |

All permissions are seeded for the `admin` role in `RoleAndPermissionSeeder`.

---

### 2.7 Exception

#### `App\Exceptions\LevelException`

```
app/Exceptions/LevelException.php
```

Single exception class for level business rule violations:

```php
public static function hasClasses(): self
{
    return new self(__('messages.level_cannot_delete_with_classes'));
}
```

Thrown by `LevelService::deleteLevel()`, caught in `LevelController::destroy()` which flashes the message as an error.

---

### 2.8 Cache Strategy

The level module manages one cache key independently of `CacheService`:

| Key | `classes_active_with_levels` |
|---|---|
| Purpose | Active class list with their level (for dropdowns in class/subject forms) |
| Invalidated by | `LevelService` after create / update / delete / toggleStatus |
| TTL | Not fixed (forgotten on mutation, re-populated lazily on next read) |

There is no separate cache for the level list itself (the admin index is always fresh from DB with pagination).

---

## 3. Frontend Architecture

### 3.1 Pages

```
resources/ts/Pages/Admin/Levels/
├── Index.tsx    Paginated list + delete confirmation modal
├── Create.tsx   Creation form page
└── Edit.tsx     Edit form page
```

#### `Index.tsx`

- Receives `levels: PaginationType<Level & { classes_count: number }>` from controller
- Delegates all logic to `useListLevels()` hook
- Renders `<LevelList>` + `<ConfirmationModal>` for deletion

#### `Create.tsx` / `Edit.tsx`

- Share `<LevelForm>` component
- Cancel button navigates to `admin.levels.index` via `router.visit(route('admin.levels.index'))` — stays within the Inertia SPA context

---

### 3.2 Components

#### `LevelForm`

```
resources/ts/Components/features/levels/LevelForm.tsx
```

Reusable form for both create and edit. Detects mode via `level` prop presence (`isEditMode = !!level`).

- Local state: `formData`, `errors`, `isSubmitting`
- On submit: `router.put(...)` (edit) or `router.post(...)` (create) with inline error handling
- Fields: `name`, `code`, `description` (textarea), `order` (number), `is_active` (Toggle)

**Note:** Uses `router` directly (not the Inertia `<Form>` component). Errors are wired manually via `onError` callback.

#### `LevelList`

```
resources/ts/Components/shared/lists/LevelList.tsx
```

Extends `BaseEntityList` with a typed `EntityListConfig`. Columns: name+code, description, order (Badge), classes_count, status (Toggle or Badge), actions (edit/delete buttons).

- Delete button is **disabled** when `level.classes_count > 0` — prevents misleading clicks before the server-side guard fires
- Toggle renders as interactive `<Toggle>` only when `permissions.canUpdate` is true; otherwise a read-only `<Badge>`
- Status filter: all / active / inactive

---

### 3.3 Hook — useListLevels

```
resources/ts/hooks/shared/useListLevels.ts
```

Centralizes all Index page logic:

| Export | Description |
|---|---|
| `canCreateLevels` | `auth.permissions.includes('create levels')` |
| `canUpdateLevels` | `auth.permissions.includes('update levels')` |
| `canDeleteLevels` | `auth.permissions.includes('delete levels')` |
| `handleCreate()` | `router.visit(admin.levels.create)` |
| `handleEdit(level)` | `router.visit(admin.levels.edit, level.id)` |
| `handleToggleStatus(level)` | `router.post(admin.levels.toggle-status, level.id)` |
| `handleDelete(id)` | `router.delete(admin.levels.destroy, id)` + closes modal |
| `deleteModal` | `{ isOpen, data, openModal, closeModal }` |

---

### 3.4 TypeScript Types

```
resources/ts/types/models/shared/level.ts
```

```typescript
interface Level {
    id: number;
    name: string;
    code: string;
    description?: string;
    order: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    classes_count?: number;   // populated by LevelRepository withCount(['classes'])
}
```

`Level` is re-exported through `resources/ts/types/models/shared/index.ts` and consumed by `ClassModel` and `Subject` types.

The `SharedProps` type in `resources/ts/types/index.ts` exposes permission flags:

```typescript
auth: {
    canManageLevels: boolean;
    canCreateLevels: boolean;
    canUpdateLevels: boolean;
    canDeleteLevels: boolean;
}
```

---

## 4. Route Map

All routes under prefix `/admin/levels`, name prefix `admin.levels.`, middleware: `auth`, `role:admin|super_admin`.

```
GET    /admin/levels                     admin.levels.index
GET    /admin/levels/create              admin.levels.create
POST   /admin/levels                     admin.levels.store
GET    /admin/levels/{level}/edit        admin.levels.edit
PUT    /admin/levels/{level}             admin.levels.update
DELETE /admin/levels/{level}             admin.levels.destroy
PATCH  /admin/levels/{level}/toggle-status  admin.levels.toggle-status
```

---

## 5. Interface Bindings

Registered in `App\Providers\AppServiceProvider`:

```php
$this->app->bind(LevelRepositoryInterface::class, LevelRepository::class);
$this->app->bind(LevelServiceInterface::class, LevelService::class);
```

This allows swapping implementations (e.g., caching repository decorator) without touching the controller.

---

## 6. Testing

| File | Scope | Tests |
|---|---|---|
| `tests/Feature/Admin/LevelControllerTest.php` | Full HTTP integration (all routes, all roles, validation, business rules) | 34 tests |
| `tests/Unit/Services/Admin/LevelServiceTest.php` | Service layer: CRUD, cache invalidation, pagination, filters | 12 tests |

**Notable test coverage:**
- Authorization: guest / student / teacher forbidden, admin allowed on all routes
- Store validation: unique name, unique code, required fields
- Update uniqueness: allows same name/code for self (ignore self in Rule::unique)
- Delete guard: cannot delete level that has classes (redirects with error)
- Toggle status: activate / deactivate
- Cache invalidation: asserted via `Cache::spy()` on create, update, delete, toggleStatus

---

## 7. Architectural Decisions

### Interface / Repository / Service triad

This module was designed with full interface abstraction (unlike other modules that inject concrete services directly). The benefit is testability and replaceability. The trade-off is additional indirection — `LevelServiceInterface` and `LevelRepositoryInterface` each declare only the minimal contract, making the bindings easy to swap in tests.

### Single Form Request for store and update

`LevelRequest` handles both actions. The unique constraint ignoring is managed by reading `$this->route('level')?->id` — `null` on store (no route binding), the model ID on update. `authorize()` uses the same route binding to dispatch to the correct policy method.

### Delete guarded at service level, not DB level

No foreign-key `RESTRICT` on `classes.level_id → levels.id`. The guard lives in `LevelService::deleteLevel()`. This keeps the migration simple and makes the error message translatable. The frontend reinforces this by disabling the Delete button when `classes_count > 0`.

### Status toggle as dedicated endpoint

Rather than reusing `update`, the toggle gets its own `PATCH /toggle-status` route. This keeps the `update` action for full form submissions and allows the toggle to fire `preserveScroll: true` without reloading the full form.
