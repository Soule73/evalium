# Teacher Module — Audit & Validation

## Overview

The Teacher module covers all features available to authenticated users with the `teacher` role: class browsing, subject and student management within classes, assessment CRUD, grading workflows, and results visualization. It also includes the admin-side teacher management (creation, listing, deactivation) handled by `Admin\TeacherController`.

---

## Architecture

### Controllers

| Controller | Namespace | Responsibility |
|---|---|---|
| `TeacherDashboardController` | `Teacher` | Dashboard stats and active class-subject list |
| `TeacherClassController` | `Teacher` | Class index/show for teacher's assigned classes |
| `TeacherClassSubjectController` | `Teacher` | Paginated list of active class-subject assignments |
| `TeacherClassStudentController` | `Teacher` | Student list, grade breakdown, and assignment history inside a class |
| `TeacherClassAssessmentController` | `Teacher` | Class-scoped assessment index and show |
| `AssessmentController` | `Teacher` | Assessment CRUD, publish/unpublish, duplicate, reopen |
| `TeacherClassResultsController` | `Teacher` | Aggregated class results with weighted grades |
| `TeacherController` | `Admin` | Admin-side teacher creation, listing, deactivation, deletion |

All teacher controllers are thin — they delegate business logic to dedicated services or repositories.

### Services

| Service | Location | Responsibility |
|---|---|---|
| `TeacherDashboardService` | `Services/Teacher/` | Dashboard stats: class count, subject count, active assessments |
| `TeacherClassResultsService` | `Services/Teacher/` | Aggregated weighted grade computation per class |
| `AssessmentService` | `Services/Core/` | Assessment CRUD + duplicate |
| `AssessmentStatsService` | `Services/Core/` | Per-assessment statistics for grading views |
| `ScoringService` | `Services/Core/` | Manual grading (save corrections) |
| `GradeCalculationService` | `Services/Core/` | Per-student subject grade breakdowns |

### Repositories

| Repository | Interface | Responsibility |
|---|---|---|
| `TeacherClassRepository` | `TeacherClassRepositoryInterface` | Classes, subjects, assessments, and students accessible to a teacher |
| `TeacherAssessmentRepository` | `TeacherAssessmentRepositoryInterface` | Paginated assessment queries with filters |
| `GradingRepository` | _(concrete)_ | Assignment lists for grading, merged with enrolled but not-yet-started students |

### Traits

| Trait | Responsibility |
|---|---|
| `HandlesAssessmentViewing` | Shared `show()`, `review()`, `grade()`, `saveGrade()` between teacher and admin controllers |
| `HasTeacherClassRouteContext` | Shared route context array for class-scoped teacher controllers |
| `FiltersAcademicYear` | Academic year selection from session or active year |

### Policies

- `AssessmentPolicy` — controls `view`, `create`, `update`, `delete`, `publish`, `duplicate` on assessments
- `ClassPolicy` (via `authorize('view', $class)`) — prevents a teacher from accessing a class they are not assigned to

---

## Data Flow

### Assessment Grading

```
Teacher navigates to grade view
  → TeacherClassAssessmentController::show()
      → GradingRepository::getAssignmentsWithEnrolledStudents()
         → LEFT JOIN enrollments + assignments
         → Real assignments: AssessmentAssignment model (with is_virtual=false via accessor)
         → Virtual (not-started): stdClass with is_virtual=true
  → Inertia: Assessments/Show

Teacher grades a student
  → AssessmentController (Teacher)::grade()
      → authorize('update', $assessment)
      → ScoringService::resolveGradingState()
  → Form: HandlesAssessmentViewing::saveGrade()
      → SaveManualGradeRequest::authorize() → can('update', $assessment)
      → ScoringService::saveManualGrades()
      → back()->flashSuccess()
```

### Class Results

```
TeacherClassResultsController::index()
  → TeacherClassResultsService::getClassResults()
      → Raw SQL: assessment stats per class (completed, graded, completion rates)
      → Raw SQL: student stats (weighted average = Σ(coeff × normalized_score) / Σ(coeff))
      → buildOverview(): totals aggregated from above
  → Inertia: Teacher/ClassResults/Index
```

---

## Validation

### Form Requests

| Request | Authorization | Validation |
|---|---|---|
| `StoreAssessmentRequest` | `can('create', Assessment::class)` | Type, title, questions; uses `QuestionValidationContext` |
| `UpdateAssessmentRequest` | `can('update', $assessment)` | Same as store |
| `SaveManualGradeRequest` | `can('update', $assessment)` | Scores array; uses `ScoreValidationContext` strategies |
| `ReopenAssignmentRequest` | Policy-delegated | Reopen reason required |

---

## Performance Notes

### In-Memory Pagination in `TeacherClassRepository::getClassesForTeacher()`

The repository loads all `ClassSubject` records for a teacher via `.get()`, applies search/filter in PHP via `applyClassFilters()`, then paginates the collection with `paginateCollection()`. For teachers with a bounded number of class assignments (typical in scholastic contexts), this is acceptable. For large deployments, the filter should be pushed to SQL.

### Dual DB Queries in `computeStudentStats()`

`TeacherClassResultsService::computeStudentStats()` executes two separate raw SQL queries (total enrolled and graded students) and merges them in PHP. These could be merged into a single query with a `CASE WHEN` aggregate, but the current approach is readable and the result set is bounded by class enrollment size.

---

## Authorization Summary

| Action | Mechanism |
|---|---|
| Access teacher routes | `role:teacher` middleware |
| View a class | `authorize('view', $class)` → ClassPolicy |
| View/grade an assessment | `authorize('view'/'update', $assessment)` → AssessmentPolicy |
| Save grades (endpoint) | `SaveManualGradeRequest::authorize()` → `can('update', $assessment)` |
| Reopen assignment | `AssignmentExceptionService::canReopen()` + `AssessmentPolicy` |

---

## Test Coverage

| Test File | Scenarios |
|---|---|
| `TeacherClassResultsControllerTest` | Access control (teacher/admin/student/guest), overview keys, assessment visibility |
| `TeacherClassSubjectControllerTest` | Access control, teacher sees only own assignments, guest blocked |
| `TeacherGradingAccessGuardTest` | Grade allowed/blocked by supervised status, save grade blocked during active assessment |
| `TeacherExceptionHandlingTest` | Reopen audit, reopen requires reason, unauthorized reopen rejection |
