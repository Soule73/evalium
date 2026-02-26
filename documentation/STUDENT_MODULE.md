# Student Module — Technical Reference

> Last updated: 2025 — post-audit commit  
> Baseline tests: 862 passing

---

## Overview

The Student module covers everything a user with the `student` role can see and do: viewing their class enrollment and subject list, browsing assessments, taking supervised exams or submitting homework, and consulting results and grades.

All routes are protected by `role:student` middleware and prefixed with `/student`.

---

## Routes

| Method | URI | Route name | Controller action |
|--------|-----|-----------|-------------------|
| GET | `/student/assessments` | `student.assessments.index` | `StudentAssessmentController::index()` |
| GET | `/student/assessments/{assessment}` | `student.assessments.show` | `StudentAssessmentController::show()` |
| POST | `/student/assessments/{assessment}/start` | `student.assessments.start` | `StudentAssessmentController::start()` |
| GET | `/student/assessments/{assessment}/take` | `student.assessments.take` | `StudentAssessmentController::take()` |
| POST | `/student/assessments/{assessment}/save-answers` | `student.assessments.save-answers` | `StudentAssessmentController::saveAnswers()` |
| POST | `/student/assessments/{assessment}/submit` | `student.assessments.submit` | `StudentAssessmentController::submit()` |
| POST | `/student/assessments/{assessment}/security-violation` | `student.assessments.security-violation` | `StudentAssessmentController::securityViolation()` |
| GET | `/student/assessments/{assessment}/result` | `student.assessments.result` | `StudentAssessmentController::results()` |
| POST | `/student/assessments/{assessment}/file-answers` | `student.assessments.file-answers.upload` | `StudentAssessmentController::uploadFileAnswer()` |
| DELETE | `/student/assessments/{assessment}/file-answers/{answer}` | `student.assessments.file-answers.delete` | `StudentAssessmentController::deleteFileAnswer()` |
| GET | `/student/enrollment` | `student.enrollment.show` | `StudentEnrollmentController::show()` |
| GET | `/student/enrollment/history` | `student.enrollment.history` | `StudentEnrollmentController::history()` |
| GET | `/student/enrollment/{enrollment}/classmates` | `student.enrollment.classmates` | `StudentEnrollmentController::classmates()` |

---

## Architecture

### Controllers

#### `StudentAssessmentController`

Thin controller (~471 lines). Uses `AuthorizesRequests` + `FiltersAcademicYear` traits.

Constructor injects:
- `StudentAssessmentService $assessmentService`
- `FileAnswerService $fileAnswerService`

**Authorization strategy (dual pattern):**

- `show()` and `results()` → `$this->authorize('view', $assessment)` — delegates to `AssessmentPolicy`
- All other actions (start, take, saveAnswers, submit, securityViolation, uploadFileAnswer, deleteFileAnswer) → `abort_unless($this->assessmentService->canStudentAccessAssessment($student, $assessment), 403)` — delegates to the service

> Note: Both paths run the same SQL (enrollment existance check) — documented as a DRY opportunity but not a blocking issue.

**`hideCorrectAnswers()` / `shouldRevealCorrectAnswers()`:**

```php
// Correct answers are only revealed when:
// 1. The assignment has been graded (graded_at is not null)
// 2. The assessment has show_correct_answers = true
private function shouldRevealCorrectAnswers(Assessment $assessment, AssessmentAssignment $assignment): bool
{
    return $assignment->graded_at !== null && $assessment->show_correct_answers;
}
```

#### `StudentEnrollmentController`

Thin controller (~128 lines). Uses `FiltersAcademicYear` trait.

Constructor injects:
- `EnrollmentServiceInterface $enrollmentService`
- `StudentEnrollmentRepositoryInterface $enrollmentRepository`
- `GradeCalculationService $gradeCalculationService`

**`show()` intentional in-memory pagination:**

`getAllSubjectsWithStats()` loads all subjects in memory first (no pagination at DB level), then builds an accurate overall grade average, and finally applies PHP-level `LengthAwarePaginator`. This is intentional — paginating at DB level would lose subjects not on the current page when computing the overall average.

---

### Services

#### `StudentAssessmentService`

Core business logic for the assessment lifecycle (~544 lines). Uses `Paginatable` trait.

Constructor injects: `ScoringService $scoringService`

Key methods:

| Method | Purpose |
|--------|---------|
| `getStudentAssessmentsForIndex()` | Paginated assessment list filtered by active enrollment + `is_published = true` |
| `getAssessmentsWithAssignments()` | Merges real `AssessmentAssignment` records with virtual placeholders for assessments not yet started |
| `startAssessment()` | Creates assignment, sets `started_at`, enforces "one active assignment per assessment" |
| `saveAnswers()` | Saves answers inside a DB transaction; uses `is_int()` to distinguish choice IDs from text |
| `submitAssessment()` | Sets `submitted_at`, calls `autoScoreAssessment()` if applicable, sends notification |
| `autoScoreAssessment()` | Scores MCQ/single-choice via `ScoringService`, sets `graded_at` if no text questions remain |
| `handleSecurityViolation()` | Saves violation record + auto-submits the assignment |
| `canStudentAccessAssessment()` | Checks active enrollment linked to the assessment's `class_subject_id` |
| `formatUserAnswers()` | Groups and formats stored answers for display in results page |

**`saveAnswers()` transaction + type check (post-audit):**

```php
DB::transaction(function () use ($assignment, $answers) {
    foreach ($answers as $questionId => $value) {
        $assignment->answers()->where('question_id', $questionId)->delete();
        if (is_array($value)) {
            // Multiple choice: array of choice IDs
        } elseif (is_int($value)) {
            // Single choice: integer choice ID (JSON integer, not numeric string)
            $assignment->answers()->create(['choice_id' => $value]);
        } else {
            // Text or file reference
            $assignment->answers()->create(['answer_text' => $value]);
        }
    }
});
```

#### `StudentDashboardService`

Thin delegator. Passes through to `GradeCalculationService::getGradeBreakdownFromLoaded()`.

#### `FileAnswerService`

Handles file upload and deletion for questions of type `file`.

- Reads `assessment.file_uploads.max_size_kb` and `assessment.file_uploads.allowed_extensions` from config
- Stores files under `storage/app/public/file-answers/{assessment_id}/{student_id}/`
- Validated by `UploadFileAnswerRequest`

---

### Repository

#### `StudentEnrollmentRepository`

Implements `StudentEnrollmentRepositoryInterface`. Read-only queries.

| Method | Usage |
|--------|-------|
| `getAllSubjectsWithStats()` | Called by `StudentEnrollmentController::show()` — loads all ClassSubjects with nested assessments + assignments (avoids N+1) |
| `getEnrollmentHistory()` | Called by `StudentEnrollmentController::history()` |
| `getClassmates()` | Called by `StudentEnrollmentController::classmates()` — filters by `EnrollmentStatus::Active` |
| `validateAcademicYearAccess()` | Called by `StudentEnrollmentController::classmates()` — `abort(403)` on year mismatch |
| `getSubjectsWithStatsForEnrollment()` | Defined in interface, implemented, but **never called** — dead code (see Notes) |

---

### Policy

#### `AssessmentPolicy::view()` — student branch

```php
if ($user->hasRole('student')) {
    return $user->enrollments()
        ->where('status', 'active')
        ->whereHas('class.classSubjects', fn ($q) => $q->where('id', $assessment->class_subject_id))
        ->exists();
}
```

Students can only view assessments belonging to a `ClassSubject` of their currently active enrollment.

---

### Form Requests

| Class | `authorize()` | Key rules |
|-------|--------------|-----------|
| `SaveAnswersRequest` | `hasRole('student')` | `answers: nullable array` |
| `SecurityViolationRequest` | `hasRole('student')` | `violation_type: required string`, `answers: nullable array` |
| `UploadFileAnswerRequest` | `hasRole('student')` | `file: required`, max size and extensions from config |

---

## Frontend Pages

All pages live in `resources/ts/Pages/Student/`.

| Page | Component | Purpose |
|------|-----------|---------|
| `Assessments/Index.tsx` | `AssessmentList` | Paginated assessment list with status filter |
| `Assessments/Show.tsx` | — | Assessment details + start button |
| `Assessments/Take.tsx` | — | Exam interface with security features |
| `Assessments/Result.tsx` | — | Results page with score, correct answers toggle |
| `Enrollment/Show.tsx` | — | Current enrollment + subjects + overall grade |
| `Enrollment/History.tsx` | — | Archive of past enrollments |
| `Enrollment/Classmates.tsx` | — | List of classmates in same class |

Security features in `Take.tsx` are enabled/disabled via `config/assessment.php` (`exam.security_enabled`, `exam.dev_mode`).

---

## Data Flow — Assessment Lifecycle

```
Student opens Index
  └─ StudentAssessmentController::index()
       └─ StudentAssessmentService::getStudentAssessmentsForIndex()
            ├─ Find active enrollment (academic year scoped)
            ├─ Load class subjects => get class_subject_ids
            ├─ Assessment::whereIn(class_subject_ids)->where(is_published, true)->paginate()
            └─ getAssessmentsWithAssignments(): merge real assignments or create virtual placeholders

Student clicks Start
  └─ StudentAssessmentController::start()
       ├─ canStudentAccessAssessment() — 403 if not enrolled
       └─ StudentAssessmentService::startAssessment()
            └─ AssessmentAssignment::firstOrCreate(assessment_id, enrollment_id)
                 └─ sets started_at

Student answers + auto-save (Take page)
  └─ StudentAssessmentController::saveAnswers()
       └─ StudentAssessmentService::saveAnswers()
            └─ DB::transaction: delete old answers, insert new answers per question

Student submits
  └─ StudentAssessmentController::submit()
       └─ StudentAssessmentService::submitAssessment()
            ├─ sets submitted_at
            ├─ autoScoreAssessment() if no file/text questions
            │    └─ ScoringService::calculateScoreForQuestion() per question
            │    └─ sets graded_at if fully auto-graded
            └─ AssessmentGradedNotification::send()

Student views results
  └─ StudentAssessmentController::results()
       ├─ authorize('view', $assessment) — AssessmentPolicy
       └─ shouldRevealCorrectAnswers() → makeHidden('is_correct') on choices if not revealed
```

---

## Tests

| File | Coverage |
|------|---------|
| `tests/Feature/Student/StudentAssessmentShowTest.php` | browse, authorization, availability status, index filter (published/unpublished) |
| `tests/Feature/Student/StudentAssessmentTakeTest.php` | start, save answers, submit, auto-grade, security violation, results embargo, numeric text answer |
| `tests/Feature/Student/StudentEnrollmentControllerTest.php` | show, history, classmates, pagination, grade stats |
| `tests/Unit/Services/Student/StudentAssessmentServiceTest.php` | service unit tests for all key methods |

---

## Configuration

```php
// config/assessment.php (stored under 'exam' key for backward compatibility)
'security_enabled' => env('EXAM_SECURITY_ENABLED', true),
'dev_mode' => env('EXAM_DEV_MODE', false), // disables ALL security in dev
'features' => [
    'fullscreen_required' => true,
    'tab_switch_detection' => true,
    'dev_tools_detection' => true,
],
'file_uploads' => [
    'max_size_kb' => env('EXAM_FILE_MAX_SIZE_KB', 5120),
    'allowed_extensions' => ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg'],
],
```

Set `EXAM_DEV_MODE=true` in `.env` to disable security features during local development.
