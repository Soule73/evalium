# Subject Module — Technical Documentation

## Overview

The Subject module in Examena is actually **two coupled modules**:

- **Subject** — manages academic subjects (name, code, description, level). Pure CRUD, scoped under a Level.
- **ClassSubject** — the enriched pivot between a class, a subject, and a teacher, with a temporalization mechanism (historization via `valid_from` / `valid_to`). This is the true operational heart of academic assignments.

Both follow the **Interface / Repository / Service** pattern. The ClassSubject module is categorized as a **Core** service because it is consumed by teachers, admins, and students alike.

---

## Table of Contents

1. [Database Schema](#1-database-schema)
2. [Backend Architecture — Subject](#2-backend-architecture--subject)
   - 2.1 [Model](#21-model)
   - 2.2 [Controller](#22-controller)
   - 2.3 [Service Layer](#23-service-layer)
   - 2.4 [Repository Layer](#24-repository-layer)
   - 2.5 [Form Requests & Validation](#25-form-requests--validation)
   - 2.6 [Policy & Permissions](#26-policy--permissions)
   - 2.7 [Exception](#27-exception)
3. [Backend Architecture — ClassSubject](#3-backend-architecture--classsubject)
   - 3.1 [Model](#31-model)
   - 3.2 [Controllers](#32-controllers)
   - 3.3 [Service Layer](#33-service-layer)
   - 3.4 [Repository Layer](#34-repository-layer)
   - 3.5 [Form Requests & Validation](#35-form-requests--validation)
   - 3.6 [Policy & Permissions](#36-policy--permissions)
   - 3.7 [Exception](#37-exception)
4. [Temporal / Historization Mechanism](#4-temporal--historization-mechanism)
5. [Cache Strategy](#5-cache-strategy)
6. [Frontend Architecture](#6-frontend-architecture)
7. [Route Map](#7-route-map)
8. [Interface Bindings](#8-interface-bindings)
9. [Testing](#9-testing)
10. [Architectural Decisions](#10-architectural-decisions)

---

## 1. Database Schema

### `subjects`

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | bigint | PK | |
| `level_id` | bigint | FK → `levels.id`, cascade delete | Required |
| `name` | varchar(255) | NOT NULL | Unique within level (composite) |
| `code` | varchar(50) | NOT NULL, UNIQUE global | e.g. `MATH-L1` |
| `description` | text | nullable | |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |

**Indexes:**
- `UNIQUE(level_id, name)` — a subject name must be unique within a level
- `UNIQUE(code)` — subject code is globally unique across all levels

### `class_subjects`

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | bigint | PK | |
| `class_id` | bigint | FK → `classes.id`, cascade delete | |
| `subject_id` | bigint | FK → `subjects.id`, cascade delete | |
| `teacher_id` | bigint | FK → `users.id`, set null | nullable |
| `semester_id` | bigint | FK → `semesters.id`, set null | nullable |
| `coefficient` | decimal(4,2) | min 0.01 | |
| `valid_from` | date | | |
| `valid_to` | date | nullable | null = currently active |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |

**Scopes:** `active()` filters `valid_to IS NULL`. `forAcademicYear(?int)` joins through `classes.academic_year_id`.

---

## 2. Backend Architecture — Subject

### 2.1 Model

`app/Models/Subject.php`

```php
$fillable = ['level_id', 'name', 'code', 'description'];
```

**Relations:**
- `level()` → `BelongsTo(Level::class)` — the parent level
- `classSubjects()` → `HasMany(ClassSubject::class)` — all assignments using this subject

**Business method:**
- `canBeDeleted(): bool` — returns `!$this->classSubjects()->exists()`. Used by `SubjectService::deleteSubject()` before deletion.

No `$casts` needed (no boolean or date fields, no JSON).

### 2.2 Controller

`app/Http/Controllers/Admin/SubjectController.php`

Thin controller — 100% delegating to service/repository interfaces:

| Method | Route action | Description |
|---|---|---|
| `index()` | GET | Renders paginated subject list with level filter |
| `create()` | GET | Renders create form with levels dropdown |
| `store()` | POST | Creates subject via `SubjectService::createSubject()` |
| `show()` | GET | Renders detail page with paginated class assignments |
| `edit()` | GET | Renders edit form with levels dropdown |
| `update()` | PUT | Updates subject via `SubjectService::updateSubject()` |
| `destroy()` | DELETE | Deletes via `SubjectService::deleteSubject()` catching `SubjectException` |

All methods call `$this->authorize()` before any business logic.

### 2.3 Service Layer

`app/Services/Admin/SubjectService.php` implements `SubjectServiceInterface`

| Method | Description |
|---|---|
| `createSubject(array $validatedData): Subject` | `Subject::create()` |
| `updateSubject(Subject $subject, array $validatedData): Subject` | Updates + returns `$subject->fresh()` |
| `deleteSubject(Subject $subject): bool` | Guards via `canBeDeleted()`, throws `SubjectException::hasClassSubjects()` if blocked |

No cache management — subjects are queried dynamically (not cached at service level).

### 2.4 Repository Layer

`app/Repositories/Admin/SubjectRepository.php` implements `SubjectRepositoryInterface`

| Method | Description |
|---|---|
| `getSubjectsForIndex(?int $academicYearId, array $filters, int $perPage)` | Paginated listing; `academicYearId` is nullable (shows all if null) |
| `getSubjectDetailsWithPagination(Subject $subject, int $perPage)` | Loads level + paginated classSubjects with teacher/class/semester |
| `getAllLevels()` | Cached via `CacheService::KEY_LEVELS_ALL` (used for dropdowns) |

### 2.5 Form Requests & Validation

**Trait:** `app/Http/Requests/Traits/SubjectValidationRules.php`

Shared between `StoreSubjectRequest` and `UpdateSubjectRequest` to eliminate duplication.

```php
protected function getSubjectValidationRules(?int $subjectId = null): array
{
    $uniqueCodeRule = Rule::unique('subjects', 'code');
    $uniqueNameRule = Rule::unique('subjects', 'name')->where('level_id', $this->input('level_id'));

    if ($subjectId !== null) {
        $uniqueCodeRule->ignore($subjectId);
        $uniqueNameRule->ignore($subjectId);
    }

    return [
        'level_id'    => ['required', 'exists:levels,id'],
        'name'        => ['required', 'string', 'max:255', $uniqueNameRule],
        'code'        => ['required', 'string', 'max:50', $uniqueCodeRule],
        'description' => ['nullable', 'string', 'max:1000'],
    ];
}
```

**Important:** `name` uniqueness is scoped by `level_id` (composite unique). `code` uniqueness is global.

| Request | `authorize()` | Key behavior |
|---|---|---|
| `StoreSubjectRequest` | `can('create', Subject::class)` | calls `getSubjectValidationRules()` |
| `UpdateSubjectRequest` | `can('update', $this->route('subject'))` | calls `getSubjectValidationRules($subject->id)` to ignore self |

### 2.6 Policy & Permissions

`app/Policies/SubjectPolicy.php`

| Method | Permission checked |
|---|---|
| `viewAny` | `view subjects` |
| `view` | `view subjects` |
| `create` | `create subjects` |
| `update` | `update subjects` |
| `delete` | `delete subjects` |

### 2.7 Exception

`app/Exceptions/SubjectException.php`

| Factory method | Thrown when |
|---|---|
| `SubjectException::hasClassSubjects()` | Admin tries to delete a subject still referenced by class assignments |

---

## 3. Backend Architecture — ClassSubject

### 3.1 Model

`app/Models/ClassSubject.php`

```php
$fillable = ['class_id', 'subject_id', 'teacher_id', 'semester_id', 'coefficient', 'valid_from', 'valid_to'];
```

**Relations:**
- `class()` → `BelongsTo(ClassModel::class)`
- `subject()` → `BelongsTo(Subject::class)`
- `teacher()` → `BelongsTo(User::class)`
- `semester()` → `BelongsTo(Semester::class)`
- `assessments()` → `HasMany(Assessment::class)`

**Local scopes:**
- `scopeActive($query)` — `whereNull('valid_to')`
- `scopeForAcademicYear($query, int $id)` — filters through class's `academic_year_id` via join/whereHas

### 3.2 Controllers

**Admin:** `app/Http/Controllers/Admin/ClassSubjectController.php`

| Method | Route action | Description |
|---|---|---|
| `index()` | GET | Paginated listing with academic year + filters |
| `store()` | POST | Creates assignment via `ClassSubjectService::assignTeacherToClassSubject()` |
| `history()` | GET | Teaching history for a class+subject pair |
| `replaceTeacher()` | POST | Closes current assignment, opens new one (transaction) |
| `updateCoefficient()` | POST | Updates coefficient via `UpdateCoefficientRequest` |
| `terminate()` | POST | Sets `valid_to` via `TerminateAssignmentRequest` |
| `destroy()` | DELETE | Hard-deletes if no assessments |

**Teacher:** `app/Http/Controllers/Teacher/TeacherClassSubjectController.php` — read-only view of the teacher's own assignments.

### 3.3 Service Layer

`app/Services/Core/ClassSubjectService.php` implements `ClassSubjectServiceInterface`

| Method | Description |
|---|---|
| `getFormDataForCreate(?int $selectedYearId): array` | Returns classes/subjects/teachers/semesters for the form; all scoped by year (nullable) |
| `assignTeacherToClassSubject(array $data): ClassSubject` | Validates level consistency between class and subject, creates record |
| `replaceTeacher(ClassSubject, int, ?Carbon): ClassSubject` | Transaction: terminates old (`valid_to = now`), creates new |
| `getTeachingHistory(int $classId, int $subjectId): Collection` | All historical assignments for a class-subject pair |
| `updateCoefficient(ClassSubject, float): ClassSubject` | Updates coefficient, returns updated model |
| `terminateAssignment(ClassSubject, ?Carbon): ClassSubject` | Sets `valid_to` |
| `deleteClassSubject(ClassSubject): bool` | Guards via `ClassSubjectException::hasAssessments()` |

**Level consistency check in `assignTeacherToClassSubject()`:**

```php
private function validateAssignment(array $data): void
{
    $class = ClassModel::findOrFail($data['class_id']);
    $subject = Subject::findOrFail($data['subject_id']);

    if ($class->level_id !== $subject->level_id) {
        throw ClassSubjectException::levelMismatch();
    }
}
```

### 3.4 Repository Layer

`app/Repositories/Admin/ClassSubjectRepository.php` implements `ClassSubjectRepositoryInterface`

| Method | Description |
|---|---|
| `getClassSubjectsForIndex(?int $selectedYearId, array $filters, bool $activeOnly, int $perPage)` | Paginated listing; year filter applied via `->when($selectedYearId, ...)` to handle null |
| `loadClassSubjectDetails(ClassSubject): ClassSubject` | Eager-loads class.level, subject, teacher, semester, assessments |
| `getClassAndSubjectForHistory(int $classId, int $subjectId): array` | Returns `['class' => ..., 'subject' => ...]` for history page |
| `getTeachersForReplacement(): Collection` | All users with role `teacher` |
| `getPaginatedHistory(int $classId, int $subjectId, int $perPage, ?int $excludeId): LengthAwarePaginator` | History excluding current active record |

### 3.5 Form Requests & Validation

| Request | `authorize()` | Validates |
|---|---|---|
| `StoreClassSubjectRequest` | `can('create', ClassSubject::class)` | class_id, subject_id, teacher_id (nullable), semester_id (nullable), coefficient, valid_from, valid_to |
| `ReplaceTeacherRequest` | `can('update', $classSubject)` | new_teacher_id, effective_date |
| `UpdateCoefficientRequest` | `can('update', $this->route('class_subject'))` | coefficient (required, numeric, min:0.01) |
| `TerminateAssignmentRequest` | `can('update', $this->route('class_subject'))` | end_date (required, date) |

### 3.6 Policy & Permissions

`app/Policies/ClassSubjectPolicy.php`

| Method | Permission checked |
|---|---|
| `viewAny` | `view class subjects` |
| `view` | `view class subjects` |
| `create` | `create class subjects` |
| `update` | `update class subjects` |
| `delete` | `delete class subjects` |

### 3.7 Exception

`app/Exceptions/ClassSubjectException.php`

| Factory method | Thrown when |
|---|---|
| `ClassSubjectException::hasAssessments()` | Admin tries to delete an assignment that has linked assessments |
| `ClassSubjectException::levelMismatch()` | Class level ≠ subject level during assignment creation |
| `ClassSubjectException::invalidCoefficient()` | Coefficient ≤ 0 (defense-in-depth in service) |

---

## 4. Temporal / Historization Mechanism

ClassSubject supports teacher replacement with full history tracking:

```
BEFORE replace:
  ClassSubject id=1  { class_id=5, subject_id=3, teacher_id=10, valid_from=2024-09-01, valid_to=NULL }

AFTER replaceTeacher(classSubject=1, newTeacherId=12, effectiveDate=2025-01-15):
  ClassSubject id=1  { ..., valid_to=2025-01-14 }   ← closed
  ClassSubject id=2  { class_id=5, subject_id=3, teacher_id=12, valid_from=2025-01-15, valid_to=NULL }  ← new active
```

- `valid_to = NULL` means the assignment is currently active → use `scopeActive()` for current state
- Complete history is accessible via `getTeachingHistory()` or the `/history` page

---

## 5. Cache Strategy

### What is cached

| CacheService key | Populated by | Invalidated by |
|---|---|---|
| `levels:all` | `SubjectRepository::getAllLevels()` | `LevelService` mutations via `CacheService::invalidateLevelsCaches()` |

### What is NOT cached

Subject listings and ClassSubject listings are **not cached** — they are dynamic (academic year filter, pagination, search) and would require per-user or per-year cache variants that add complexity without proportional benefit.

---

## 6. Frontend Architecture

### Pages — Subject

| File | Route | Description |
|---|---|---|
| `Pages/Admin/Subjects/Index.tsx` | `/admin/subjects` | Paginated table with level filter + search |
| `Pages/Admin/Subjects/Create.tsx` | `/admin/subjects/create` | Form with level dropdown |
| `Pages/Admin/Subjects/Edit.tsx` | `/admin/subjects/{subject}/edit` | Pre-filled form |
| `Pages/Admin/Subjects/Show.tsx` | `/admin/subjects/{subject}` | Subject detail + class assignments list |

### Pages — ClassSubject

| File | Route | Description |
|---|---|---|
| `Pages/Admin/ClassSubjects/Index.tsx` | `/admin/class-subjects` | Assignments table with year/class/subject/teacher filters |
| `Pages/Admin/ClassSubjects/History.tsx` | `/admin/class-subjects/history` | History for a class+subject pair |
| `Pages/Teacher/ClassSubjects/Index.tsx` | `/teacher/class-subjects` | Teacher's own assignments |

### TypeScript Types

Defined in `resources/ts/types/`:
- `Subject` — `{ id, level_id, name, code, description, level?, can_delete? }`
- `ClassSubject` — `{ id, class_id, subject_id, teacher_id?, semester_id?, coefficient, valid_from, valid_to?, class?, subject?, teacher?, semester? }`

---

## 7. Route Map

### Subject routes (prefix: `/admin/subjects`, name: `admin.subjects.`)

| Method | URI | Name suffix | Action |
|---|---|---|---|
| GET | `/` | `index` | `SubjectController@index` |
| GET | `/create` | `create` | `SubjectController@create` |
| POST | `/` | `store` | `SubjectController@store` |
| GET | `/{subject}` | `show` | `SubjectController@show` |
| GET | `/{subject}/edit` | `edit` | `SubjectController@edit` |
| PUT | `/{subject}` | `update` | `SubjectController@update` |
| DELETE | `/{subject}` | `destroy` | `SubjectController@destroy` |

### ClassSubject routes (prefix: `/admin/class-subjects`, name: `admin.class-subjects.`)

| Method | URI | Name suffix | Action |
|---|---|---|---|
| GET | `/` | `index` | `ClassSubjectController@index` |
| POST | `/` | `store` | `ClassSubjectController@store` |
| GET | `/history` | `history` | `ClassSubjectController@history` |
| POST | `/{class_subject}/replace-teacher` | `replace-teacher` | `ClassSubjectController@replaceTeacher` |
| POST | `/{class_subject}/update-coefficient` | `update-coefficient` | `ClassSubjectController@updateCoefficient` |
| POST | `/{class_subject}/terminate` | `terminate` | `ClassSubjectController@terminate` |
| DELETE | `/{class_subject}` | `destroy` | `ClassSubjectController@destroy` |

---

## 8. Interface Bindings

Registered in `app/Providers/AppServiceProvider.php`:

```php
$this->app->bind(SubjectServiceInterface::class, SubjectService::class);
$this->app->bind(SubjectRepositoryInterface::class, SubjectRepository::class);
$this->app->bind(ClassSubjectServiceInterface::class, ClassSubjectService::class);
$this->app->bind(ClassSubjectRepositoryInterface::class, ClassSubjectRepository::class);
```

---

## 9. Testing

| Test file | Tests | Coverage area |
|---|---|---|
| `tests/Feature/Admin/SubjectControllerTest.php` | 32 | Full CRUD + authorization + name/code uniqueness rules |
| `tests/Feature/Admin/ClassSubjectControllerTest.php` | 21 | Full CRUD + replace teacher + coefficient + terminate + authorization |
| `tests/Feature/Teacher/TeacherClassSubjectControllerTest.php` | 4 | Teacher read-only view + authorization |

### Key validation test cases

- `test_store_requires_unique_code` — prevents globally duplicate codes
- `test_store_rejects_duplicate_name_within_same_level` — validates composite unique constraint
- `test_store_allows_same_name_in_different_levels` — confirms cross-level is allowed
- `test_update_name_unique_ignores_self` — confirms self-ignore works on update

---

## 10. Architectural Decisions

### Why a trait for subject validation?
`SubjectValidationRules` is shared between `StoreSubjectRequest` and `UpdateSubjectRequest`. The only structural difference is whether `$subjectId` is passed to `ignore()`. The trait eliminates duplication (DRY).

### Why `valid_to = NULL` instead of a boolean `active`?
Using a nullable date allows precise historization: we know exactly when an assignment ended, not just whether it is active or not. This is critical for reporting (who taught which subject and during which period).

### Why is ClassSubjectService in `Core/` and not `Admin/`?
ClassSubject data is consumed by multiple actors: admin creates it, teachers consult it, and student assessment access depends on it. Moving it to `Core/` reflects its cross-domain nature while keeping `Admin/` for purely administrative concerns.

### Why `?int $selectedYearId` instead of requiring a year?
`FiltersAcademicYear::getSelectedAcademicYearId()` returns `?int`. If no academic year is active (fresh installation, no seed), the system should degrade gracefully and show all records without a year filter, rather than throwing a `TypeError`. Both `ClassSubjectRepository::getClassSubjectsForIndex()` and `ClassSubjectService::getFormDataForCreate()` use `->when($selectedYearId, ...)` to handle the null case.

### Why no cache for subject listings?
Subject listings are filtered by academic year, class, and search term — making caching complex (per-key or full flush on every mutation). Since the subject count per institution is relatively small and queries are indexed, the performance gain from caching is minimal.
