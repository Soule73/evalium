# Academic Year Module — Technical Documentation

## Overview

The Academic Year module manages the lifecycle of school years (e.g. `2025-2026`) and their subdivisions (semesters). Every entity in the platform — classes, subjects, assessments — is scoped to an academic year. The module exposes:

- A full CRUD admin interface with a multi-step wizard for year creation
- A session-based year switcher available to all authenticated users
- Inertia-shared data so every page receives the selected year and recent years without extra API calls

---

## Table of Contents

1. [Database Schema](#1-database-schema)
2. [Backend Architecture](#2-backend-architecture)
   - 2.1 [Models](#21-models)
   - 2.2 [Controllers](#22-controllers)
   - 2.3 [Service Layer](#23-service-layer)
   - 2.4 [Form Requests & Validation](#24-form-requests--validation)
   - 2.5 [Policy & Permissions](#25-policy--permissions)
   - 2.6 [Middleware](#26-middleware)
   - 2.7 [Cache Strategy](#27-cache-strategy)
3. [Frontend Architecture](#3-frontend-architecture)
   - 3.1 [Pages](#31-pages)
   - 3.2 [Wizard Components](#32-wizard-components)
   - 3.3 [Shared Components](#33-shared-components)
   - 3.4 [Context & State](#34-context--state)
   - 3.5 [TypeScript Types](#35-typescript-types)
4. [Data Flow per Request](#4-data-flow-per-request)
5. [Route Map](#5-route-map)
6. [Testing](#6-testing)
7. [Architectural Decisions](#7-architectural-decisions)

---

## 1. Database Schema

### `academic_years`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `name` | varchar(255) | Unique. E.g. `2025-2026` |
| `start_date` | date | |
| `end_date` | date | Must be after `start_date` |
| `is_current` | boolean | Only one row can be `true` at a time (enforced in service) |
| `created_at` / `updated_at` | timestamp | |

### `semesters`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `academic_year_id` | bigint FK → `academic_years` | Cascade delete |
| `name` | varchar(255) | E.g. `Semester 1` |
| `start_date` | date | Must be ≥ parent year `start_date` |
| `end_date` | date | Must be ≤ parent year `end_date` |
| `order_number` | integer | Display order |

**Constraints enforced in service (not DB):**
- Semesters within the same year must not overlap
- Each semester's end must be after its start

---

## 2. Backend Architecture

### 2.1 Models

#### `App\Models\AcademicYear`

```
app/Models/AcademicYear.php
```

- `$fillable`: `name`, `start_date`, `end_date`, `is_current`
- `casts()`: `start_date` → `date`, `end_date` → `date`, `is_current` → `boolean`
- Relationships: `semesters()` (HasMany), `classes()` (HasMany → `ClassModel`)
- Scope: `scopeCurrent($query)` — filters `WHERE is_current = true`

#### `App\Models\Semester`

```
app/Models/Semester.php
```

- `$fillable`: `academic_year_id`, `name`, `start_date`, `end_date`, `order_number`
- `casts()`: `start_date` → `date`, `end_date` → `date`, `order_number` → `integer`
- Traits: `HasAcademicYearScope` (automatic scoping via the related year)
- Relationships: `academicYear()` (BelongsTo), `classSubjects()` (HasMany)

---

### 2.2 Controllers

Two separate controllers with distinct responsibilities:

#### `App\Http\Controllers\Admin\AcademicYearController`

```
app/Http/Controllers/Admin/AcademicYearController.php
```

Handles the full admin lifecycle. All routes are behind `admin.academic-years.*` and protected by `role:admin|super_admin` middleware.

| Method | HTTP | Route | Action |
|---|---|---|---|
| `archives()` | GET | `/admin/academic-years` | Paginated list with filters |
| `create()` | GET | `/admin/academic-years/create` | Wizard entry page |
| `store()` | POST | `/admin/academic-years` | Create standard year |
| `wizardStore()` | POST | `/admin/academic-years/wizard-store` | Create via wizard (with class duplication) |
| `edit()` | GET | `/admin/academic-years/{id}/edit` | Edit form |
| `update()` | PUT | `/admin/academic-years/{id}` | Update year + sync semesters |
| `destroy()` | DELETE | `/admin/academic-years/{id}` | Delete (blocked if `is_current` or has classes) |
| `setCurrent()` | POST | `/admin/academic-years/{id}/set-current` | Mark as current |
| `archive()` | POST | `/admin/academic-years/{id}/archive` | Archive without deleting |

#### `App\Http\Controllers\AcademicYearController`

```
app/Http/Controllers/AcademicYearController.php
```

Single endpoint — session year switch available to all authenticated roles.

| Method | HTTP | Route | Action |
|---|---|---|---|
| `setCurrent()` | POST | `/api/academic-years/{id}/set-current` | Write year ID to session |

---

### 2.3 Service Layer

#### `App\Services\Admin\AcademicYearService`

```
app/Services/Admin/AcademicYearService.php
```

Constructor-injected dependency: `CacheService`.

All mutation methods run inside a `DB::transaction()` and call `$this->cacheService->invalidateAcademicYearsCaches()` after commit.

| Method | Description |
|---|---|
| `createNewYear(array $data)` | Creates year + semesters. Deactivates current if `is_current` is requested. |
| `setCurrentYear(AcademicYear $year)` | Deactivates all others, activates the target. |
| `archiveYear(AcademicYear $year)` | Sets `is_current = false` without deleting. |
| `updateYear(AcademicYear $year, array $data)` | Updates fields + calls `syncSemesters()`. |
| `deleteYear(AcademicYear $year)` | Returns `bool`. Blocked by controller policy if year is current or has classes. |
| `createWizardYear(array $data)` | Creates year + duplicates classes from `$data['class_ids']`. |
| `syncSemesters(AcademicYear, array)` | Upserts (id present → update, no id → create) and removes semesters not in the payload. |
| `deactivateCurrentYear()` | `UPDATE SET is_current = false WHERE is_current = true`. Internal. |
| `getAcademicYearsForArchives(array $filters)` | Returns paginated results with `semesters` and `classes_count` eager-loaded. |

---

### 2.4 Form Requests & Validation

#### `StoreAcademicYearRequest`

```
app/Http/Requests/Admin/StoreAcademicYearRequest.php
```

- `authorize()`: `$this->user()->can('create', AcademicYear::class)`
- Rules: `name` (required, unique), + semester rules from `ValidatesAcademicYearSemesters`

#### `UpdateAcademicYearRequest`

```
app/Http/Requests/Admin/UpdateAcademicYearRequest.php
```

- `authorize()`: `$this->user()->can('update', $this->route('academic_year'))`
- Rules: `name` (unique ignoring self), `semesters.*.id` (nullable exists), + semester rules

#### `StoreAcademicYearWizardRequest`

```
app/Http/Requests/Admin/StoreAcademicYearWizardRequest.php
```

- `authorize()`: `$this->user()->can('create', AcademicYear::class)`
- Extra rules: `class_ids` (nullable array of existing class IDs)

#### `App\Http\Requests\Traits\ValidatesAcademicYearSemesters`

```
app/Http/Requests/Traits/ValidatesAcademicYearSemesters.php
```

Provides reusable rules for semester sub-objects:
- `name` required string
- `start_date` / `end_date` required dates, end > start
- `start_date` ≥ year `start_date`, `end_date` ≤ year `end_date`
- No overlaps between semesters of the same request

---

### 2.5 Policy & Permissions

#### `App\Policies\AcademicYearPolicy`

```
app/Policies/AcademicYearPolicy.php
```

| Policy method | Permission checked |
|---|---|
| `viewAny()` | `view academic years` |
| `view()` | `view academic years` |
| `create()` | `create academic years` |
| `update()` | `update academic years` |
| `delete()` | `delete academic years` |
| `archive()` | `archive academic years` |

All six permissions are seeded for the `admin` role in `RoleAndPermissionSeeder`.

**Authorization flow:**
1. `FormRequest::authorize()` — resolves before controller code (early 403)
2. `Controller::$this->authorize()` — secondary guard (defence in depth, not redundant on wizard endpoint which skips Form Request authorize)

---

### 2.6 Middleware

Two middleware collaborate on every request, executed sequentially by Laravel's middleware pipeline.

#### `App\Http\Middleware\InjectAcademicYear`

```
app/Http/Middleware/InjectAcademicYear.php
```

**Runs first.** Resolves the academic year the user is working in via 3-level priority chain:

```
1. ?academic_year_id=X  (query param) → write to session
2. session academic_year_id
3. AcademicYear WHERE is_current = true  → write to session
```

Issues **one** `AcademicYear::find()` (or one `WHERE is_current` fallback). Stores the resolved model in:
- `$request->input('selected_academic_year_id')` — the integer ID
- `$request->attributes->set('selected_academic_year', $model)` — the full Eloquent model

#### `App\Http\Middleware\HandleInertiaRequests`

```
app/Http/Middleware/HandleInertiaRequests.php
```

**Runs after.** Shares data via `share()`:

```php
'academic_year' => [
    'selected' => $this->getSelectedAcademicYear($request),  // uses $request->attributes (no extra query)
    'recent'   => $this->getRecentAcademicYears($user),      // from cache (1h TTL)
]
```

`getSelectedAcademicYear()` reads the model injected by `InjectAcademicYear` from `$request->attributes`. If the attribute is missing (e.g. route without `InjectAcademicYear`), falls back to session then DB.

`getRecentAcademicYears()` uses two cache keys depending on role:

| Role | Cache key | Content |
|---|---|---|
| `admin` / `super_admin` | `academic_years:recent:admin` | Current + next (future) + up to 3 previous |
| Others | `academic_years:recent` | 3 most recent by `start_date DESC` |

---

### 2.7 Cache Strategy

Managed by `App\Services\Core\CacheService`.

| Constant | Key | TTL |
|---|---|---|
| `KEY_ACADEMIC_YEARS_RECENT` | `academic_years:recent` | 3600s (1h) |
| — | `academic_years:recent:admin` | 3600s (1h) |
| `KEY_ACADEMIC_YEAR_CURRENT` | `academic_year:current` | 3600s (1h) |

`CacheService::invalidateAcademicYearsCaches()` forgets all three keys. Called by `AcademicYearService` after every mutation (`createNewYear`, `setCurrentYear`, `archiveYear`, `updateYear`, `deleteYear`).

---

## 3. Frontend Architecture

### 3.1 Pages

All pages live under `resources/ts/Pages/Admin/AcademicYears/`.

| File | Route | Description |
|---|---|---|
| `Archives.tsx` | `/admin/academic-years` | Paginated list. Actions: details, set-current, switch session, delete. Inline modals for confirmation. |
| `Create.tsx` | `/admin/academic-years/create` | Hosts the 3-step wizard + result screen via `AcademicYearWizardProvider`. |
| `Edit.tsx` | `/admin/academic-years/{id}/edit` | Simple form with semester management. Submits via `router.put`. |

---

### 3.2 Wizard Components

The creation wizard is split into 4 phases rendered inside `Create.tsx`:

```
resources/ts/Components/features/academic-years/
├── AcademicYearFormStep.tsx      Step 1 – Year dates & name
├── ClassDuplicationStep.tsx      Step 2 – Select classes to duplicate
├── AcademicYearConfirmStep.tsx   Step 3 – Summary + POST to wizard-store
└── AcademicYearResultStep.tsx    Result – Links to archives/classes
```

#### Step 1 — `AcademicYearFormStep`

Pre-fills the form from the current academic year. Key helpers:

- `buildInitialFormData(currentYear)` — shifts dates by +1 year using `addOneYear()` + `toDateInputValue()` (strips UTC timezone offset before `getFullYear()`)
- `buildNextYearName(startDate, endDate)` — infers the year name from date strings, timezone-safe via `toDateInputValue()`
- `buildDefaultSemesters(start, end)` — creates 2 default semester objects

#### Step 2 — `ClassDuplicationStep`

DataTable with row selection. Reads `class_ids` from wizard context.

#### Step 3 — `AcademicYearConfirmStep`

Calls `axios.post(route('admin.academic-years.wizard-store'), ...)` directly (bypasses Inertia form helper) to get a JSON response before advancing to the result screen.

#### Result — `AcademicYearResultStep`

Receives the created year from wizard context `state.result`. Provides navigation links.

---

### 3.3 Shared Components

```
resources/ts/Components/features/academic-years/
├── AcademicYearForm.tsx          Reusable form (used by Step 1 and Edit page)
├── AcademicYearSelector.tsx      Navbar dropdown for session year switch
└── index.ts                      Barrel exports incl. toDateInputValue(), buildDefaultSemesters()
```

`toDateInputValue(isoString)`: Takes an ISO-8601 UTC string and returns a `YYYY-MM-DD` string using local date components, preventing off-by-one errors when the user's timezone is behind UTC.

---

### 3.4 Context & State

#### `AcademicYearWizardContext`

```
resources/ts/contexts/AcademicYearWizardContext.tsx
```

Manages wizard-local state via `useReducer`-style pattern. Shared through `AcademicYearWizardProvider`.

| State field | Type | Description |
|---|---|---|
| `step` | `1 \| 2 \| 3 \| 'result'` | Active wizard step |
| `formData` | `AcademicYearFormData` | Year + semesters from step 1 |
| `selectedClassIds` | `number[]` | Class IDs picked in step 2 |
| `result` | `AcademicYear \| null` | Year created after step 3 POST |

Actions: `goToStep`, `setFormData`, `setSelectedClassIds`, `setResult`, `reset`.

---

### 3.5 TypeScript Types

```
resources/ts/types/models/academicYear.ts
```

```typescript
interface AcademicYear {
    id: number;
    name: string;
    start_date: string;   // ISO-8601
    end_date: string;     // ISO-8601
    is_current: boolean;
    created_at: string;
    updated_at: string;
    semesters?: Semester[];
    classes?: ClassModel[];
    semesters_count?: number;
    classes_count?: number;
}

interface AcademicYearFormData {
    name: string;
    start_date: string;   // YYYY-MM-DD (date input value)
    end_date: string;     // YYYY-MM-DD
    is_current?: boolean;
    semesters: SemesterFormData[];
}
```

The Inertia shared prop `academic_year` is typed in `resources/ts/types/index.ts` under `SharedProps`:

```typescript
academic_year: {
    selected: AcademicYear | null;
    recent: AcademicYear[];
}
```

---

## 4. Data Flow per Request

```
Browser request
    │
    ▼
InjectAcademicYear middleware
    │  1. Reads ?academic_year_id, session, or DB (is_current)
    │  2. Issues ONE AcademicYear::find() (or WHERE is_current fallback)
    │  3. Stores model in $request->attributes['selected_academic_year']
    │  4. Merges id into $request->input['selected_academic_year_id']
    ▼
HandleInertiaRequests middleware
    │  1. Reads model from $request->attributes (zero extra DB query)
    │  2. Reads recent years from Cache (TTL 1h) — 0 or 1 DB query
    │  3. Shares { selected, recent } as Inertia prop 'academic_year'
    ▼
Controller
    │  Reads $request->input('selected_academic_year_id') when needed
    │  Delegates all mutations to AcademicYearService
    ▼
AcademicYearService
    │  Runs mutation in DB::transaction()
    │  Calls CacheService::invalidateAcademicYearsCaches() after commit
    ▼
Inertia response
    │  { academic_year: { selected, recent }, ... }
    ▼
React page
    │  const { academic_year } = usePage().props;
    │  AcademicYearSelector reads academic_year.recent
```

---

## 5. Route Map

### Admin routes (middleware: `auth`, `role:admin|super_admin`, `InjectAcademicYear`)

```
GET    /admin/academic-years                  admin.academic-years.archives
GET    /admin/academic-years/create           admin.academic-years.create
POST   /admin/academic-years                  admin.academic-years.store
POST   /admin/academic-years/wizard-store     admin.academic-years.wizard-store
GET    /admin/academic-years/{id}/edit        admin.academic-years.edit
PUT    /admin/academic-years/{id}             admin.academic-years.update
DELETE /admin/academic-years/{id}             admin.academic-years.destroy
POST   /admin/academic-years/{id}/set-current admin.academic-years.set-current
POST   /admin/academic-years/{id}/archive     admin.academic-years.archive
```

### API route (middleware: `auth`, `InjectAcademicYear`)

```
POST   /api/academic-years/{id}/set-current   api.academic-years.set-current
```

Session-only: writes the year ID to session, no DB mutation on `AcademicYear`.

---

## 6. Testing

### Backend

| File | Scope | Count |
|---|---|---|
| `tests/Feature/Admin/AcademicYearControllerTest.php` | Full HTTP integration (CRUD, wizard, permissions, cache invalidation) | 60 tests |
| `tests/Feature/AcademicYearSwitchControllerTest.php` | Session-switch endpoint | — |
| `tests/Unit/Traits/AcademicYearScopeTraitsTest.php` | `HasAcademicYearScope` trait | — |

Notable test groups in `AcademicYearControllerTest`:
- Authorization (guest/student/teacher/admin for each route)
- Validation (semesters: overlap, bounds, end after start)
- Business rules (can't delete current year, can't delete year with classes)
- Cache invalidation (after store, set-current, archive, destroy)
- Wizard (class duplication, future year uniqueness)

### Frontend

No dedicated Jest unit tests for wizard components. Covered indirectly by E2E Playwright tests under `e2e/admin/`.

---

## 7. Architectural Decisions

### Single-row `is_current` invariant

Only one academic year can be `is_current = true` at a time. This invariant is enforced exclusively in `AcademicYearService::deactivateCurrentYear()`, not at the DB level (no unique partial index). The service wraps all changes in `DB::transaction()` to prevent race conditions.

### Two-controller split

Session switching (`/api/academic-years/{id}/set-current`) is intentionally separated from admin CRUD to allow any authenticated user to switch their working context without requiring admin privileges. The admin `set-current` endpoint (`admin.academic-years.set-current`) also modifies the DB (`is_current` column).

### Wizard uses raw `axios` in Step 3

`AcademicYearConfirmStep` posts directly with `axios` instead of `router.post()` to receive a JSON response and drive the wizard's `result` state without a full Inertia page reload. All subsequent navigation uses Inertia's `<Link>`.

### Cache key split (admin vs non-admin)

Admins need to see future years (created for the next academic year before it becomes current) which are hidden from teachers and students. Two separate cache entries avoid polluting or leaking the admin view to non-admin users.

### Middleware model-passing via `$request->attributes`

`InjectAcademicYear` resolves the `AcademicYear` model once and stores it in `$request->attributes->set('selected_academic_year', $model)`. `HandleInertiaRequests` consumes it via `$request->attributes->get('selected_academic_year')`. This eliminates the redundant DB round-trip that would occur if each middleware read the session independently.
