# Assessment Module — Technical Reference

## Overview

The Assessment module is the core of Evalium. It covers the full lifecycle of an evaluation: creation by teachers, publication, supervised or homework-based delivery to students, answer-taking with optional anti-cheat enforcement, automatic and manual scoring, teacher review, and result visibility.

Three roles interact with this module via dedicated route prefixes and controllers:

| Role | Route prefix | Primary controller |
|------|-------------|-------------------|
| Teacher | `/teacher/assessments` | `AssessmentController` |
| Teacher (class view) | `/teacher/classes/{class}/assessments` | `TeacherClassAssessmentController` |
| Admin | `/admin/assessments` | `AdminAssessmentController` |
| Student | `/student/assessments` | `StudentAssessmentController` |

---

## Delivery Modes

### Supervised (`supervised`)

Timed, date-anchored exam. Key fields:

- `scheduled_at` — start datetime of the exam window
- `duration_minutes` — how long students have to complete it
- Anti-cheat features (fullscreen enforcement, tab-switch detection, dev-tools detection) — controlled via `config/exam.php` and the `exam.security_enabled` / `exam.dev_mode` flags

### Homework (`homework`)

Flexible deadline-based assignment. Key fields:

- `due_date` — submission deadline
- `allow_late_submission` (boolean accessor on `Assessment` model) — if `true`, students may still submit after `due_date`

---

## Data Model

### `assessments`

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `class_subject_id` | FK | Links to `class_subject` pivot (teacher + subject + class) |
| `title` | string | |
| `type` | enum | `AssessmentType`: exam, tp, devoir, quiz |
| `delivery_mode` | enum | `DeliveryMode`: supervised, homework |
| `scheduled_at` | datetime | Supervised only |
| `duration_minutes` | integer | Supervised only |
| `due_date` | datetime | Homework only |
| `allow_late_submission` | boolean | Cast via `Assessment::$casts`; accessor available |
| `total_points` | decimal | Computed from questions on create/update |
| `is_published` | boolean | Triggers `AssessmentPublishedNotification` to active students |
| `settings` | json | Extra config; accessed via model accessors, not `settings[...]` arrays directly |

### `assessment_assignments`

Tracks each student's session for a given assessment. Created lazily when a student **starts** (not browses) an assessment.

| Column | Type | Notes |
|--------|------|-------|
| `id` | bigint PK | |
| `assessment_id` | FK | |
| `enrollment_id` | FK | Links to `enrollments` — enrollment-based access model |
| `started_at` | datetime | Set when student hits "Start" |
| `submitted_at` | datetime | Set on submit |
| `graded_at` | datetime | Set when teacher saves a manual score |
| `score` | decimal | Final score |
| `answers` | json | Student's submitted answers |

**Status is computed** (no `status` column):

| State | Condition |
|-------|-----------|
| not started | No assignment row |
| in progress | `started_at` not null, `submitted_at` null |
| submitted | `submitted_at` not null, `graded_at` null |
| graded | `graded_at` not null |

---

## Architecture

### Service-Oriented (SRP)

```
AssessmentService            → CRUD (create, update, delete, duplicate, publish)
AssessmentStatsService       → Statistics (per-assessment, per-class, per-student)
StudentAssessmentService     → Student session lifecycle (start, save answers, submit, find assignment)
QuestionManagementService    → Question and choice CRUD
ScoringService               → Score calculation (auto + manual) — Strategy pattern
GradeCalculationService      → Grade/average calculations
```

### Repository Layer

```
TeacherAssessmentRepository   → implements TeacherAssessmentRepositoryInterface
AdminAssessmentRepository     → implements AdminAssessmentRepositoryInterface
TeacherClassRepository        → implements TeacherClassRepositoryInterface
GradingRepository             → raw grading queries
```

Repositories are injected into controllers and are the **only** place allowed to run Eloquent queries for listing/filtering.

### Shared Trait

`HandlesAssessmentViewing` — shared by `AssessmentController` (teacher) and `AdminAssessmentController`. Provides:

- `show()` — display assessment details + stats
- `review()` — display student submission for teacher review
- `grade()` — display grading form
- `saveGrade()` — persist manual corrections via `ScoringService`

Each host controller implements `buildRouteContext(): array` which the trait uses to build role-appropriate redirect/link targets, keeping the shared code route-agnostic.

---

## Key Flows

### 1. Assessment Creation (Teacher)

```
POST /teacher/assessments
  → StoreAssessmentRequest (validates questions via QuestionValidationContext)
  → AssessmentController::store()
  → $this->authorize('create', Assessment::class)   ← Policy check first
  → AssessmentService::createAssessment($validated)
    → Assessment::create(...)
    → QuestionManagementService::createQuestionsForAssessment(...)
  → redirect with flashSuccess
```

### 2. Publication

```
POST /teacher/assessments/{assessment}/publish
  → AssessmentController::publish()
  → AssessmentService::publishAssessment($assessment)
    → $assessment->is_published = true; save()
    → load classSubject.class.enrollments.student
    → filter enrollments where status === EnrollmentStatus::Active
    → Notification::send($activeStudents, AssessmentPublishedNotification)
  → redirect with flashSuccess
```

### 3. Student Browse (show — no side-effect)

```
GET /student/assessments/{assessment}
  → StudentAssessmentController::show()
  → StudentAssessmentService::findAssignment($student, $assessment)
      → looks up Enrollment for this student + class (active only)
      → if found, queries AssessmentAssignment by assessment_id + enrollment_id
      → returns null if none — NO creation
  → $availability = StudentAssessmentService::getAvailability($assessment, $assignment)
  → Inertia::render('Student/Assessments/Show', [...])
```

**No phantom records** — `AssessmentAssignment` rows are **only created** in `StudentAssessmentController::start()`.

### 4. Student Start

```
POST /student/assessments/{assessment}/start
  → StudentAssessmentController::start()
  → StudentAssessmentService::getOrCreateAssignment($student, $assessment)
      → creates AssessmentAssignment if none exists
      → sets started_at = now()
  → Inertia redirect to take page
```

### 5. Auto-Submit (Supervised)

When `duration_minutes` expires, the frontend triggers:

```
POST /student/assessments/{assessment}/submit
  → StudentAssessmentController::submit()
  → StudentAssessmentService::submitAssessment($assignment, $answers)
    → sets submitted_at = now()
    → evaluates auto-gradable questions (MCQ/SCQ) via ScoringService
```

Alternatively, `AssessmentAutoSubmitCommand` (scheduled) sweeps overdue active sessions and force-submits them.

### 6. Teacher Review & Manual Grading

```
GET  /teacher/assessments/{assessment}/students/{student}/review  → HandlesAssessmentViewing::review()
POST /teacher/assessments/{assessment}/students/{student}/grade   → HandlesAssessmentViewing::saveGrade()
  → SaveStudentReviewRequest (validates via ScoreValidationContext)
  → ScoringService::saveManualCorrection($assessment, $student, $validated)
    → updates StudentAnswer scores
    → recalculates total → AssessmentAssignment::score
    → sets graded_at = now()
```

### 7. Statistics

`AssessmentStatsService` always counts from **enrollments** (not assignments) to correctly compute "not started":

```php
// Counts via LEFT JOIN enrollments → assessment_assignments
$stats = $assessmentStatsService->calculateAssessmentStats($assessment);
// Returns: total_assigned, not_started, in_progress, submitted, graded, average_score
```

Results visible to students only after:
- Homework: `submitted_at` is not null
- Supervised: embargo period (1 hour after `scheduled_at + duration_minutes`)

---

## Authorization (AssessmentPolicy)

| Action | Teacher | Admin | Student |
|--------|---------|-------|---------|
| `viewAny` | Own class-subjects only | All | Own (via enrollment) |
| `view` | Own class-subject | All | Enrolled + published |
| `create` | Has class-subject assignment | Yes | No |
| `update` | Own + not published | All | No |
| `delete` | Own + not published | All | No |
| `publish` | Own + has questions | All | No |
| `grade` | Own class-subject | All | No |

All controllers call `$this->authorize()` **before** any service method.

---

## Validation Strategies

### Question Validation (`QuestionValidationContext`)

Applied in `StoreAssessmentRequest` / `UpdateAssessmentRequest`:

| Strategy | Rule |
|----------|------|
| `MultipleChoiceValidationStrategy` | Requires ≥ 2 correct choices |
| `SingleChoiceValidationStrategy` | Requires exactly 1 correct choice |
| `TextQuestionValidationStrategy` | No choices required; max_score mandatory |

### Score Validation (`ScoreValidationContext`)

Applied in `SaveStudentReviewRequest`:

| Strategy | Rule |
|----------|------|
| `QuestionExistsInAssessmentValidationStrategy` | question_id must belong to this assessment |
| `ScoreNotExceedsMaxValidationStrategy` | score ≤ question.max_score |

---

## Security (Supervised Mode)

Configured in `config/exam.php` (key kept as `exam` for backward compatibility):

```php
'security_enabled' => env('EXAM_SECURITY_ENABLED', true),
'dev_mode'         => env('EXAM_DEV_MODE', false),   // disables ALL security locally
'features' => [
    'fullscreen_required'    => true,
    'tab_switch_detection'   => true,
    'dev_tools_detection'    => true,
],
```

`is_correct` is stripped from choices before being sent to the frontend during an active session.

---

## Frontend Pages

| Page | Path | Role |
|------|------|------|
| Assessment list | `Teacher/Assessments/Index` | Teacher |
| Create/Edit form | `Teacher/Assessments/Create` | Teacher |
| Assessment detail | `Teacher/Assessments/Show` | Teacher / Admin |
| Class assessments | `Teacher/Classes/Assessments/Index` | Teacher |
| Results overview | `Teacher/Assessments/Results` | Teacher / Admin |
| Review submission | `Teacher/Assessments/Review` | Teacher / Admin |
| Grade submission | `Teacher/Assessments/Grade` | Teacher / Admin |
| Student list | `Student/Assessments/Index` | Student |
| Student preview | `Student/Assessments/Show` | Student |
| Take assessment | `Student/Assessments/Take` | Student |
| Student results | `Student/Assessments/Results` | Student |

The `buildRouteContext()` method on each controller provides the correct route names for shared Inertia components (diff between teacher/admin flows).

---

## Notifications

`AssessmentPublishedNotification` — sent via `Notification::send()` to all active students in the class when an assessment is published. Implements Laravel's `ShouldQueue` for non-blocking delivery.

---

## Bugs Fixed (this session)

| # | Severity | Description | Fix |
|---|----------|-------------|-----|
| 1 | High | `TeacherAssessmentRepositoryInterface` — `int $selectedYearId` but callers pass `?int` → TypeError | Changed to `?int`, added `->when()` guards in all 3 methods + cascade to `TeacherClassRepository` |
| 2 | High | `StudentAssessmentController::show()` used `getOrCreateAssignment()` → phantom DB rows on browse | Added `findAssignment()` to service; `show()` uses it (nullable, read-only) |
| 3 | High | Status filter applied post-pagination → wrong `total` in paginator metadata | Moved filter pre-pagination using `whereHas`/`whereDoesntHave` SQL |
| 4 | Medium | `AdminAssessmentController::index()` ran 3 Eloquent queries directly (SRP violation) | Added `getFilterData()` to `AdminAssessmentRepository` and its interface |
| 5 | Medium | `TeacherClassAssessmentController::index()` ran `ClassSubject::query()` directly (SRP) | Added `getSubjectFilterDataForClass()` to `TeacherClassRepository` and its interface |
| 6 | Medium | `loadAssessmentDetails()` eagerly loaded all assignments unnecessarily (N+1 waste) | Removed `'assignments.enrollment.student'` from eager load |
| 7 | Medium | `isDueDatePassed()` accessed `$assessment->settings['allow_late_submission']` directly | Replaced with `$assessment->allow_late_submission` model accessor |
| 8 | Low | `publishAssessment()` compared `$e->status->value === 'active'` (fragile string) | Changed to `$e->status === EnrollmentStatus::Active` |
| 9 | Low | `AssessmentPolicy` missing class-level PHPDoc | Added PHPDoc block |

**Tests**: 847/847 passing after fixes + test updates for BUG-02 behavioral change.
