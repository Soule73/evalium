# Class Module — Technical Documentation

## Overview

The Class module manages educational groups (classes) that are scoped by academic year and educational level. Each class can contain enrolled students, assigned subjects (with teachers), and associated assessments.

---

## Architecture

### Pattern: Interface / Repository / Service

```
ClassController ──► ClassServiceInterface ──► ClassService
                 └─► ClassRepositoryInterface ──► ClassRepository
```

- **`ClassController`** — thin HTTP layer: authorization, request/response handling
- **`ClassStudentController`** — dedicated controller for student enrollment views within a class
- **`ClassService`** — business logic: create, update, delete, duplicate classes
- **`ClassRepository`** — read queries: paginated lists, statistics, enrollment data, with caching

### Supporting Classes

| Class | Role |
|---|---|
| `ClassModel` | Eloquent model with relationships and `canBeDeleted()` guard |
| `ClassPolicy` | Gate-based authorization (viewAny, view, create, update, delete) |
| `StoreClassRequest` | Validation + composite unique constraint + policy delegation |
| `UpdateClassRequest` | Validation + scoped unique (ignores self) + policy delegation |
| `ClassValidationRules` | Shared validation rules/messages trait (used by both requests) |
| `ClassException` | Domain exception factory for deletion violations |
| `ProvidesAdminClassRouteContext` | Shared trait generating the admin `routeContext` array |

---

## Data Model

### `classes` table

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | PK |
| `academic_year_id` | bigint FK | NOT NULL, cascade delete |
| `level_id` | bigint FK | NOT NULL, cascade delete |
| `name` | varchar(255) | NOT NULL |
| `description` | text | nullable |
| `max_students` | smallint | nullable, CHECK > 0 |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Constraints:**
- `UNIQUE(academic_year_id, level_id, name)` — class names are unique per level per year

### Key Relationships

```php
ClassModel
  ├── belongsTo AcademicYear
  ├── belongsTo Level
  ├── hasMany Enrollment
  ├── hasMany ClassSubject
  ├── hasManyThrough Assessment (via ClassSubject)
  └── belongsToMany User (students, via enrollments)
```

### Computed Attributes

- **`display_name`** (`$appends`) — formatted as `"{Level} - {Name}"`, null-safe

---

## Routes

All routes are under `admin.classes.*` prefix with `auth` + role middleware.

| Route name | Method | URI | Controller method |
|---|---|---|---|
| `admin.classes.index` | GET | `/admin/classes` | `ClassController@index` |
| `admin.classes.create` | GET | `/admin/classes/create` | `ClassController@create` |
| `admin.classes.store` | POST | `/admin/classes` | `ClassController@store` |
| `admin.classes.show` | GET | `/admin/classes/{class}` | `ClassController@show` |
| `admin.classes.edit` | GET | `/admin/classes/{class}/edit` | `ClassController@edit` |
| `admin.classes.update` | PUT | `/admin/classes/{class}` | `ClassController@update` |
| `admin.classes.destroy` | DELETE | `/admin/classes/{class}` | `ClassController@destroy` |
| `admin.classes.subjects` | GET | `/admin/classes/{class}/subjects` | `ClassController@classSubjectsList` |
| `admin.classes.assessments` | GET | `/admin/classes/{class}/assessments` | `ClassController@classAssessments` |
| `admin.classes.assessments.show` | GET | `/admin/classes/{class}/assessments/{assessment}` | `ClassController@assessmentShow` |
| `admin.classes.subjects.show` | GET | `/admin/classes/{class}/subjects/{classSubject}` | `ClassController@subjectShow` |
| `admin.classes.students.index` | GET | `/admin/classes/{class}/students` | `ClassStudentController@index` |
| `admin.classes.students.show` | GET | `/admin/classes/{class}/students/{enrollment}` | `ClassStudentController@show` |
| `admin.classes.students.assignments` | GET | `/admin/classes/{class}/students/{enrollment}/assignments` | `ClassStudentController@assignments` |

---

## Inertia Page Architecture

### Shared pages (admin + teacher via `routeContext`)

| Page component | Used by |
|---|---|
| `Classes/Index` | `ClassController@index` (admin), `TeacherClassController@index` |
| `Classes/Show` | `ClassController@show` (admin), `TeacherClassController@show` |
| `Classes/Assessments` | `ClassController@classAssessments` (admin), `TeacherClassController@assessments` |
| `Classes/Students/Index` | `ClassStudentController@index` (admin), `TeacherClassStudentController@index` |
| `Classes/Students/Show` | `ClassStudentController@show` (admin), `TeacherClassStudentController@show` |
| `Classes/Students/Assignments/Index` | `ClassStudentController@assignments` (admin) |

### Admin-only pages

| Page component | Used by |
|---|---|
| `Admin/Classes/Create` | `ClassController@create` |
| `Admin/Classes/Edit` | `ClassController@edit` |
| `Admin/Classes/Subjects` | `ClassController@classSubjectsList` |
| `Admin/Classes/SubjectShow` | `ClassController@subjectShow` |

### `routeContext` pattern

The `routeContext` array is passed to shared pages to resolve role-specific routes at the frontend level. It is generated via the `ProvidesAdminClassRouteContext` trait to avoid repetition:

```php
// app/Http/Traits/ProvidesAdminClassRouteContext.php
protected function adminClassRouteContext(): array
{
    return [
        'role' => 'admin',
        'indexRoute' => 'admin.classes.index',
        'showRoute' => 'admin.classes.show',
        // ... 11 more route keys
    ];
}
```

---

## Business Rules

### Creation
- A class requires a valid `academic_year_id` and `level_id`
- The combination of `(academic_year_id, level_id, name)` must be unique
- If no active academic year exists when storing, the controller returns an error flash without creating the class

### Update
- All fields (`name`, `level_id`, `description`, `max_students`) are updatable
- `description` and `max_students` can be explicitly cleared (set to `null`)
- The composite unique constraint is re-checked on update (excluding self)

### Deletion
- A class can only be deleted if it has no enrolled students AND no subject assignments
- `ClassModel::canBeDeleted()` provides a safe pre-check (uses `->exists()`)
- `ClassService::deleteClass()` enforces the rules and throws a `ClassException` on violation

### Duplication
- Classes can be duplicated from one academic year to another via `duplicateClassesToNewYear()`
- By default all classes from the source year are duplicated; an explicit `$classIds` list restricts the selection
- Wrapped in a DB transaction — all-or-nothing

---

## Authorization

`ClassPolicy` is bound to `ClassModel` and uses Spatie permission strings:

| Method | Permission required |
|---|---|
| `viewAny` | `view classes` |
| `view` | `view classes` |
| `create` | `create classes` |
| `update` | `update classes` |
| `delete` | `delete classes` |

Form Requests delegate to the policy:
- `StoreClassRequest::authorize()` → `$user->can('create', ClassModel::class)`
- `UpdateClassRequest::authorize()` → `$user->can('update', $route('class'))`

---

## Caching

`ClassRepository` uses `CacheService` to cache level lists (`KEY_LEVELS_ALL`). The cache is invalidated on any class create/update via `invalidateLevelsCache()`.

---

## Exceptions

`ClassException` provides factory methods for domain violation messages:

| Method | Thrown when |
|---|---|
| `hasEnrolledStudents()` | Deleting a class with active enrollments |
| `hasSubjectAssignments()` | Deleting a class with class-subject assignments |

---

## Test Coverage

| Test file | Scope |
|---|---|
| `tests/Feature/Admin/ClassControllerTest.php` | CRUD access control + validation (27 tests) |
| `tests/Feature/Admin/AdminClassStudentTest.php` | Student enrollment views (11 tests) |
| `tests/Unit/Exceptions/CustomExceptionsTest.php` | `ClassException` factory methods |
