# Enrollment Module — Technical Reference

## Overview

The Enrollment module manages the relationship between students and classes. It covers the full lifecycle: admin enrollment (single, bulk, wizard), student transfer, withdrawal, reactivation, deletion, and student-facing views (dashboard, history, classmates).

Two roles access this module:

| Role | Route prefix | Primary controller |
|------|-------------|-------------------|
| Admin | `/admin/enrollments` | `EnrollmentController` |
| Student | `/student/enrollment` | `StudentEnrollmentController` |

---

## Data Model

### `enrollments`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `class_id` | FK → `classes` | Cascade on delete |
| `student_id` | FK → `users` | Cascade on delete |
| `enrolled_at` | date | Enrollment date |
| `withdrawn_at` | date nullable | Populated on withdraw/transfer |
| `status` | enum | `EnrollmentStatus`: active / withdrawn / completed |
| `created_at` / `updated_at` | timestamps | |

**Unique constraint:** `(class_id, student_id)` — one row per student per class. Handled via `updateOrCreate()` in the service to support re-enrollment after withdrawal.

**Indexes:** `(class_id, status)`, `student_id`

### `EnrollmentStatus` (Enum)

| Case | Value | Meaning |
|------|-------|---------|
| `Active` | `active` | Currently enrolled |
| `Withdrawn` | `withdrawn` | Left class (manually or via transfer) |
| `Completed` | `completed` | Academic year ended with enrollment complete |

---

## Architecture

### Service Layer

```
EnrollmentServiceInterface  →  EnrollmentService (app/Services/Admin/)
```

Single Responsibility: business logic for enrollment CRUD operations.

### Repository Layer

```
EnrollmentRepositoryInterface  →  EnrollmentRepository (app/Repositories/Admin/)
StudentEnrollmentRepositoryInterface  →  StudentEnrollmentRepository (app/Repositories/Student/)
```

Separate interfaces exist for admin and student read concerns. Both are bound in `AppServiceProvider`.

### DI Bindings (`AppServiceProvider`)

```php
$this->app->bind(EnrollmentRepositoryInterface::class, EnrollmentRepository::class);
$this->app->bind(StudentEnrollmentRepositoryInterface::class, StudentEnrollmentRepository::class);
$this->app->bind(EnrollmentServiceInterface::class, EnrollmentService::class);
```

---

## Routes

### Admin routes (`/admin/enrollments`)

| Method | URI | Action | Name |
|--------|-----|--------|------|
| GET | `/enrollments` | `index` | `admin.enrollments.index` |
| GET | `/enrollments/create` | `create` | `admin.enrollments.create` |
| POST | `/enrollments` | `store` | `admin.enrollments.store` |
| POST | `/enrollments/create-student` | `createStudent` | `admin.enrollments.create-student` |
| POST | `/enrollments/bulk` | `bulkStore` | `admin.enrollments.bulk-store` |
| GET | `/enrollments/search-students` | `searchStudents` | `admin.enrollments.search-students` |
| GET | `/enrollments/search-classes` | `searchClasses` | `admin.enrollments.search-classes` |
| POST | `/enrollments/{enrollment}/transfer` | `transfer` | `admin.enrollments.transfer` |
| POST | `/enrollments/{enrollment}/withdraw` | `withdraw` | `admin.enrollments.withdraw` |
| POST | `/enrollments/{enrollment}/reactivate` | `reactivate` | `admin.enrollments.reactivate` |
| DELETE | `/enrollments/{enrollment}` | `destroy` | `admin.enrollments.destroy` |

### Student routes (`/student/enrollment`)

| Method | URI | Action | Name |
|--------|-----|--------|------|
| GET | `/enrollment` | `show` | `student.enrollment.show` |
| GET | `/enrollment/history` | `history` | `student.enrollment.history` |
| GET | `/enrollment/classmates` | `classmates` | `student.enrollment.classmates` |

---

## Key Flows

### 1. Single Enrollment (Admin)

```
POST /admin/enrollments
  → StoreEnrollmentRequest (validates student_id unique per class excluding withdrawn)
  → EnrollmentController::store()
  → $this->authorize('create', Enrollment::class)
  → EnrollmentService::enrollStudent($studentId, $classId)
    → validateEnrollment() — checks student role + no active enrollment in same year
    → isClassAtCapacity() — checks max_students vs active count
    → Enrollment::updateOrCreate(['class_id', 'student_id'], [..., status = Active])
  → handleEnrollmentCredentials() — optional credential notification
  → redirect with flashSuccess
```

**Key detail:** `updateOrCreate()` is used instead of `create()` to handle the DB unique constraint on `(class_id, student_id)`. A student who was previously withdrawn from the same class gets their record reactivated.

### 2. Bulk Enrollment (Wizard — Admin)

```
POST /admin/enrollments/bulk
  → BulkStoreEnrollmentRequest
  → EnrollmentController::bulkStore()
  → Loops through student_ids, calls enrollmentService->enrollStudent() per student
  → New student credentials (created in same wizard session) are sent if requested
  → Returns JsonResponse with enrolled/failed breakdown
```

Session keys used during wizard:
- `new_user_credentials` — temporary storage for newly created student credentials
- `pending_enrollment_credentials` — used for single-student credential sending after confirm
- `enrollment_credential_map` — maps `user_id → password` for bulk credential delivery

### 3. Create Student In Wizard Context

```
POST /admin/enrollments/create-student
  → StoreEnrollmentStudentRequest
  → EnrollmentController::createStudent()
  → UserManagementService::store([..., role: 'student', send_credentials: false])
  → Credentials stored in session for later bulk send
  → back() with flashSuccess
```

### 4. Transfer

```
POST /admin/enrollments/{enrollment}/transfer
  → TransferStudentRequest (validates new_class_id != old_class_id)
  → EnrollmentController::transfer()
  → $this->authorize('transfer', $enrollment)
  → EnrollmentService::transferStudent($enrollment, $newClassId)
    → Validates student role + not already in target class (active)
    → Checks target class capacity
    → DB::transaction:
        enrollment->update(status = Withdrawn, withdrawn_at = now())
        Enrollment::updateOrCreate([class_id: newClass, student_id], [..., status = Active])
  → redirect to new enrollment show page
```

### 5. Withdraw / Reactivate

```
POST /admin/enrollments/{enrollment}/withdraw
  → EnrollmentService::withdrawStudent($enrollment)
    → enrollment->update(status = Withdrawn, withdrawn_at = now())

POST /admin/enrollments/{enrollment}/reactivate
  → EnrollmentService::reactivateEnrollment($enrollment)
    → Guards: status must be Withdrawn + class not at capacity
    → enrollment->update(status = Active, withdrawn_at = null)
```

### 6. Delete

```
DELETE /admin/enrollments/{enrollment}
  → EnrollmentService::deleteEnrollment($enrollment)
    → Throws EnrollmentException::hasAssignments() if assessment_assignments exist
    → enrollment->delete()
```

### 7. Student Dashboard (show)

```
GET /student/enrollment
  → StudentEnrollmentController::show()
  → EnrollmentService::getCurrentEnrollment($student, $selectedYearId)
     → Finds active enrollment in selected year (or current year)
     → If none → redirect to dashboard
  → StudentEnrollmentRepository::getAllSubjectsWithStats($enrollment, $student, $filters)
     → Eager loads all class subjects + assessments + assignments in one batch
  → GradeCalculationService::getGradeBreakdownFromLoaded($student, $class, $allSubjects)
  → Manual LengthAwarePaginator applied to subjects
  → Inertia::render('Student/Enrollment/Show', [...])
```

**Two-step pagination:** All subjects are loaded (non-paginated) to allow accurate overall stats calculation (annual average, etc.), then a `LengthAwarePaginator` is applied in the controller on the result set.

### 8. Previous Year Context in Student Search

```
GET /admin/enrollments/search-students?q=...
  → EnrollmentController::searchStudents()
  → enrollmentQueryService->resolvePreviousAcademicYear($selectedYearId)
     → Finds the AcademicYear with end_date < selected year's start_date
  → Excludes students already actively enrolled in selected year
  → Appends previous_class info if previousYear exists
```

---

## Authorization (`EnrollmentPolicy`)

| Action | Permission required |
|--------|-------------------|
| `viewAny` | `view enrollments` |
| `view` | `view enrollments` |
| `create` | `create enrollments` |
| `update` | `update enrollments` |
| `delete` | `delete enrollments` |
| `transfer` | `transfer enrollments` |

---

## Domain Exceptions (`EnrollmentException`)

| Factory method | Trigger |
|---------------|---------|
| `classFull(?int $slots)` | `max_students` reached |
| `invalidStudentRole()` | User doesn't have `student` role |
| `alreadyEnrolled()` | Active enrollment exists in same year |
| `invalidStatus($status)` | Reactivate called on non-withdrawn enrollment |
| `targetClassFull()` | Transfer destination at capacity |
| `hasAssignments()` | Delete blocked by existing assessment assignments |

---

## Form Requests

| Request | Rules |
|---------|-------|
| `StoreEnrollmentRequest` | `student_id` required + unique per class (excluding withdrawn); `class_id` exists |
| `BulkStoreEnrollmentRequest` | `class_id`, `student_ids` array, optional `new_student_ids`, `send_credentials` |
| `StoreEnrollmentStudentRequest` | `name`, `email` unique in users |
| `TransferStudentRequest` | `new_class_id` exists + different from current (via `prepareForValidation`) |

---

## Frontend Pages

| Page | Component | Notes |
|------|-----------|-------|
| Admin list | `Admin/Enrollments/Index` | Filterable by class, status, search |
| Admin wizard | `Admin/Enrollments/Create` | Multi-step: search students/class → summary → result |
| Student dashboard | `Student/Enrollment/Show` | Subjects + grades + overall stats |
| Student history | `Student/Enrollment/History` | Past enrollments across years |
| Student classmates | `Student/Enrollment/Classmates` | Active peers in same class |

### Wizard Context (`EnrollmentWizardContext`)

React context managing wizard state:
- Student selection (existing or newly created)
- Class selection with capacity info
- Confirmation step with credential-sending option
- Result step showing enrolled/failed breakdown