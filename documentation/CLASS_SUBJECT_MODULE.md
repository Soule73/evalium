# ClassSubject Module — Technical Reference

## Overview

The ClassSubject module manages the assignment of teachers to teach a specific subject in a class, scoped to a semester and academic year. It supports **teacher historization**: when a teacher is replaced, the previous assignment is archived (`valid_to` is set) and a new active record is created.

---

## Database Schema

**Table:** `class_subjects`

| Column | Type | Notes |
|---|---|---|
| `id` | bigint PK | |
| `class_id` | FK → `classes` | CASCADE DELETE |
| `subject_id` | FK → `subjects` | CASCADE DELETE |
| `teacher_id` | FK (nullable) → `users` | SET NULL on delete |
| `semester_id` | FK (nullable) → `semesters` | SET NULL on delete |
| `coefficient` | decimal(5,2) | must be > 0 |
| `valid_from` | date | assignment start date |
| `valid_to` | date (nullable) | NULL = active; set on termination/replacement |
| `created_at` / `updated_at` | timestamps | |

**Indexes:**
- `(class_id, subject_id, valid_to)` — composite index for active lookups
- No UNIQUE constraint on `(class_id, subject_id)` — history requires multiple rows

**Business rule:** Only one row with `valid_to IS NULL` per `(class_id, subject_id)` pair is allowed. Enforced at the service layer by `ClassSubjectException::alreadyActive()`.

---

## Architecture

```
HTTP Request
    │
    ▼
Form Request (validation + authorization via Policy)
    │
    ▼
Controller (thin — delegates all logic)
    │
    ├─► ClassSubjectService (via ClassSubjectServiceInterface)
    │       │
    │       └─► ClassSubjectRepository (via ClassSubjectRepositoryInterface)
    │
    └─► Inertia::render() OR RedirectResponse
```

---

## Key Files

### Model

**`app/Models/ClassSubject.php`**

- `scopeActive()` — `whereNull('valid_to')`
- `scopeCurrent()` — active + belongs to current academic year

### Contracts

**`app/Contracts/Services/ClassSubjectServiceInterface.php`**
**`app/Contracts/Repositories/ClassSubjectRepositoryInterface.php`**

### Service

**`app/Services/Core/ClassSubjectService.php`**

Implements `ClassSubjectServiceInterface`. Injected with `ClassSubjectRepositoryInterface`.

| Method | Description |
|---|---|
| `getFormDataForCreate(?int $yearId)` | Returns classes, subjects, teachers, semesters for the create form |
| `assignTeacherToClassSubject(array $data)` | Creates a new assignment; validates no active duplicate and level match |
| `replaceTeacher(ClassSubject, int, ?Carbon)` | Archives current record, creates new one; throws if already terminated |
| `getTeachingHistory(int $classId, int $subjectId)` | Delegates to repository — returns full ordered Collection |
| `updateCoefficient(ClassSubject, float)` | Updates coefficient; throws if ≤ 0 |
| `terminateAssignment(ClassSubject, ?Carbon)` | Sets `valid_to`; idempotent (no guard needed — expected behavior) |
| `deleteClassSubject(ClassSubject)` | Throws `ClassSubjectException::hasAssessments()` if linked assessments exist |

**Validation flow in `assignTeacherToClassSubject()`:**
1. Check for active duplicate `(class_id, subject_id, valid_to IS NULL)` → `alreadyActive()`
2. Check level mismatch between class and subject → `levelMismatch()`

### Repository

**`app/Repositories/Admin/ClassSubjectRepository.php`**

| Method | Description |
|---|---|
| `getClassSubjectsForIndex(?int $yearId, bool $paginate)` | Paginated list with filters for admin index |
| `loadClassSubjectDetails(ClassSubject)` | Eager-loads relationships on existing model |
| `getClassAndSubjectForHistory(int, int)` | Returns class+subject models for the history page header |
| `getTeachersForReplacement(ClassSubject)` | Returns teachers eligible for replacement (role=teacher) |
| `getPaginatedHistory(int, int, int, ?int)` | Paginated history, excludes current active record |
| `getHistory(int, int)` | Full ordered Collection for service delegation |

### Controllers

**`app/Http/Controllers/Admin/ClassSubjectController.php`**

Routes: `admin.class-subjects.*`

| Action | Route | HTTP |
|---|---|---|
| `index` | `/admin/class-subjects` | GET |
| `store` | `/admin/class-subjects` | POST |
| `history` | `/admin/class-subjects/{cs}/history` | GET |
| `replaceTeacher` | `/admin/class-subjects/{cs}/replace-teacher` | POST |
| `updateCoefficient` | `/admin/class-subjects/{cs}/update-coefficient` | PATCH |
| `terminate` | `/admin/class-subjects/{cs}/terminate` | PATCH |
| `destroy` | `/admin/class-subjects/{cs}` | DELETE |

Note: there is **no `show` route** on this controller. Detail view is rendered by `ClassController@subjectShow` → `Admin/Classes/SubjectShow`.

The `store()` action supports a `redirect_to` payload field: if it starts with `/`, the response redirects there (e.g., back to the class show page). Otherwise defaults to `admin.classes.subjects.show`.

**`app/Http/Controllers/Teacher/TeacherClassSubjectController.php`**

Routes: `teacher.class-subjects.*` — teacher's own assignments.

### Form Requests

| Request | Route | Authorization |
|---|---|---|
| `StoreClassSubjectRequest` | store | `can('create', ClassSubject::class)` |
| `ReplaceTeacherRequest` | replace-teacher | `can('update', $classSubject)` |
| `UpdateCoefficientRequest` | update-coefficient | `can('update', $classSubject)` |
| `TerminateAssignmentRequest` | terminate | `can('update', $classSubject)` |

`ReplaceTeacherRequest::prepareForValidation()` injects `old_teacher_id` from the route model so the `different:old_teacher_id` validation rule works correctly.

### Policy

**`app/Policies/ClassSubjectPolicy.php`**

| Method | Condition |
|---|---|
| `viewAny` | `manage class subjects` permission |
| `view` | `manage class subjects` permission |
| `create` | `manage class subjects` permission |
| `update` | `manage class subjects` permission |
| `delete` | `manage class subjects` permission |
| `replaceTeacher` | `manage class subjects` permission |

### Exception

**`app/Exceptions/ClassSubjectException.php`**

| Factory | Message key | Trigger |
|---|---|---|
| `levelMismatch()` | `class_subject_level_mismatch` | Subject level ≠ class level |
| `invalidCoefficient()` | `class_subject_invalid_coefficient` | Coefficient ≤ 0 |
| `hasAssessments()` | `class_subject_has_assessments` | Delete blocked by linked assessments |
| `alreadyActive()` | `class_subject_already_active` | Duplicate active assignment |
| `alreadyTerminated()` | `class_subject_already_terminated` | Replace/update on archived record |

All factories produce a `422 Unprocessable Entity` HTTP response via the base exception handler redirect pattern.

---

## Frontend Pages

| File | Route context |
|---|---|
| `resources/ts/Pages/Admin/Classes/SubjectShow.tsx` | Admin: class-subject detail view |
| `resources/ts/Pages/Admin/Classes/Subjects.tsx` | Admin: list of subjects in a class |
| `resources/ts/Components/shared/lists/ClassSubjectList.tsx` | Shared: teacher + admin list component |
| `resources/ts/Components/shared/lists/ClassSubjectHistoryList.tsx` | Shared: teacher historization timeline |

The teacher views share components with admin via a `routeContext` prop pattern (same as the Class module).

---

## Historization Flow

```
Active record: { valid_to: null, teacher_id: A }

replaceTeacher(newTeacherId: B, effectiveDate: D)
    │
    ├─ Guard: if valid_to !== null → throw alreadyTerminated()
    ├─ UPDATE class_subjects SET valid_to = D-1 WHERE id = current
    └─ INSERT class_subjects { teacher_id: B, valid_from: D, valid_to: null }

Result:
  { valid_to: D-1, teacher_id: A }   ← archived
  { valid_to: null, teacher_id: B }  ← new active
```

---

## Tests

| File | Tests | Assertions |
|---|---|---|
| `tests/Feature/Admin/ClassSubjectControllerTest.php` | 22 | covers auth, replace, coefficient, terminate, delete |
| `tests/Feature/Admin/ClassSubjectStoreTest.php` | 13 | covers auth, creation, validation, duplicate guard |
| `tests/Feature/Teacher/TeacherClassSubjectControllerTest.php` | 4 | covers teacher visibility and access |