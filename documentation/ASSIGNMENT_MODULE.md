# Assessment Assignment Module — Technical Reference

> **Module 7 of the Evalium backend audit**
> Covers: `assessment_assignments`, `questions`, `choices`, `answers`, scoring strategies, and the materialisation command.

---

## 1. Domain Overview

The assessment assignment module sits at the heart of the student evaluation workflow. It bridges three concerns:

1. **Assessment definition** — questions, choices, points schema
2. **Student participation** — starting, saving answers, submitting
3. **Scoring and grading** — auto-correction for MCQ/Boolean, manual grading for text/file

### Key Invariant

A student never links directly to an assessment. The chain is always:

```
Student → Enrollment → AssessmentAssignment → Assessment
```

This preserves the class and academic year context for every student response.

---

## 2. Data Model

### `assessment_assignments`

| Column | Type | Notes |
|---|---|---|
| `assessment_id` | FK | The parent assessment |
| `enrollment_id` | FK | Preserves class context (never direct student FK) |
| `started_at` | datetime | Set on first `start()`; never overwritten |
| `submitted_at` | datetime | Set on submission or forced termination |
| `graded_at` | datetime | Set when all grading is complete |
| `teacher_notes` | text | Optional global note from the teacher |
| `forced_submission` | boolean | True when auto-submitted (time expiry / violation) |
| `security_violation` | string | Optional violation type description |

**Unique constraint:** `(assessment_id, enrollment_id)` — one row per student per assessment.

**Computed appends (not stored):**
- `status` — derived from `graded_at / submitted_at / started_at`
- `score` — sum of `answers.score`, null when `graded_at` is null
- `auto_score` — sum of `answers.score` after submission; null when not yet submitted; used by the frontend to show partial scores before manual grading completes

### `questions`

| Column | Type | Notes |
|---|---|---|
| `assessment_id` | FK | Parent assessment |
| `content` | text | Question text |
| `type` | enum | `QuestionType` — see below |
| `points` | int | Max points for this question |
| `order_index` | int | Display order; enforced via global `ordered` scope |

**Global scope:** `ordered` — all queries automatically order by `order_index`.

### `choices`

| Column | Type | Notes |
|---|---|---|
| `question_id` | FK | Parent question |
| `content` | string | Option text |
| `is_correct` | boolean | Used by scoring strategies; hidden from students |
| `order_index` | int | Display order |

### `answers`

| Column | Type | Notes |
|---|---|---|
| `assessment_assignment_id` | FK | Parent assignment |
| `question_id` | FK | The question being answered |
| `choice_id` | FK (nullable) | For MCQ / OneChoice / Boolean |
| `answer_text` | text (nullable) | For text questions |
| `file_name / file_path / file_size / mime_type` | — | For file questions |
| `score` | float (nullable) | Auto-set for MCQ; manually set by teacher for text/file |
| `feedback` | text (nullable) | Teacher feedback per answer |

**Multiple-choice questions** produce multiple answer rows (one per selected choice) for a single `question_id` within the same `assessment_assignment_id`.

---

## 3. Question Types

Defined in `app/Enums/QuestionType.php`:

| Type | Value | Auto-correctable | Manual grading |
|---|---|---|---|
| `OneChoice` | `one_choice` | Yes | No |
| `Multiple` | `multiple` | Yes | No |
| `Boolean` | `boolean` | Yes | No |
| `Text` | `text` | No | Yes |
| `File` | `file` | No | Yes |

**Important:** Both `Text` AND `File` require manual grading. Use `$type->requiresManualGrading()` as the single source of truth.

---

## 4. Service Layer

### `ScoringService`

Located at `app/Services/Core/Scoring/ScoringService.php`. Orchestrates all scoring via the Strategy pattern.

**Key public methods:**

| Method | Purpose |
|---|---|
| `calculateAssignmentScore(assignment)` | Total score across all questions |
| `calculateAutoCorrectableScore(assignment)` | MCQ/Boolean only; excludes text/file |
| `calculateScoreForQuestion(question, answers)` | Single question score via matching strategy |
| `isAnswerCorrect(question, answers)` | Boolean correctness check |
| `hasManualCorrectionQuestions(assignment)` | True if any `Text` or `File` questions exist |
| `saveManualGrades(assignment, scores, teacherNotes)` | Batch update of manual grades + recalculate total |

**Performance pattern:** Use `withSum('answers', 'score')` on queries to avoid N+1; the `getScoreAttribute()` and `getAutoScoreAttribute()` detect the pre-loaded value via `$this->attributes['answers_sum_score']`.

### `StudentAssessmentService`

Located at `app/Services/Student/StudentAssessmentService.php`. Full lifecycle of a student session.

**Key methods:**

| Method | Purpose |
|---|---|
| `getOrCreateAssignment(student, assessment)` | Idempotent — resolves enrollment, FirstOrCreate |
| `findAssignment(student, assessment)` | Read-only lookup; returns null if not started |
| `startAssignment(assignment, assessment)` | Sets `started_at` if not already set |
| `saveAnswers(assignment, answers)` | Delete+recreate per question; skips submitted |
| `submitAssessment(assignment, assessment, answers)` | Save → auto-score → set `submitted_at` |
| `autoScoreAssessment(assignment, assessment)` | Writes scores to answer rows; sets `graded_at` if no manually-graded questions |
| `autoSubmitIfExpired(assignment, assessment)` | Force-submits with `forced_submission=true` |
| `isTimeExpired / calculateRemainingSeconds / isDueDatePassed` | Time guard helpers |

### `QuestionCrudService`

Located at `app/Services/Core/QuestionCrudService.php`. Handles question CRUD only.

- `deleteQuestion()` / `deleteBulk()` — also cleans up related `Answer` and `Choice` rows before deletion.
- `createQuestion()` / `updateQuestion()` — raw Eloquent, no side effects.

### `ChoiceManagementService`

Located at `app/Services/Core/ChoiceManagementService.php`. Type-aware choice management.

- `createChoicesForQuestion()` — delegates to type-specific private methods.
- `updateChoicesForQuestion()` — updates existing, creates new, **deletes orphaned** choices not present in the submitted payload.
- `deleteChoicesByIds()` — also removes associated `Answer` rows to maintain referential integrity.

### `AnswerFormatterService`

Located at `app/Services/Core/Answer/AnswerFormatterService.php`. Formats answers for different contexts.

- `formatForFrontend(assignment)` — maps grouped answers to a frontend-friendly structure.
- `formatForGrading(assignment)` — returns Answer objects grouped by `question_id`, suitable for the grading UI.
- `getCompletionStats(assignment)` — total/answered/percentage breakdown.
- `getStudentResultsData(assignment)` — full results payload for the results page.
- `prepareAnswerData(questionType, requestData)` — normalised input for answer insertion by question type (including explicit `file` handling).

### `AssignmentExceptionService`

Located at `app/Services/Teacher/AssignmentExceptionService.php`. Handles teacher-initiated exceptions.

- `reopenForStudent()` — clears `submitted_at`, preserves `started_at`; logs audit trail.
- `canReopen()` — validates preconditions and returns structured reason on failure.

---

## 5. Scoring Strategies

All strategies implement `ScoringStrategyInterface` and extend `AbstractScoringStrategy`.

| Strategy | Question type | Logic |
|---|---|---|
| `OneChoiceScoringStrategy` | `one_choice` | Full points if selected choice is correct; 0 otherwise |
| `MultipleChoiceScoringStrategy` | `multiple` | All-or-nothing: full points only when exactly the correct choices are selected |
| `BooleanScoringStrategy` | `boolean` | Same as `OneChoiceScoringStrategy` |
| `TextQuestionScoringStrategy` | `text` | Returns `answer.score` (set by teacher); 0 if null |
| `FileQuestionScoringStrategy` | `file` | Returns `answer.score` (set by teacher); 0 if null |

**AbstractScoringStrategy** provides shared helpers: `hasValidChoice()`, `getSelectedChoiceIds()`, `getCorrectChoices()`, `isChoiceCorrect()`. The default `calculateScore()` returns `points` if `isCorrect()` — concrete strategies override `calculateScore()` for manual grading types.

---

## 6. Controller Flow

### Student — Assessment Take

```
GET  /student/assessments/{assessment}/start
     → canStudentAccessAssessment() [enrollment check]
     → getAvailabilityStatus() [opens/closes window]
     → getOrCreateAssignment() → startAssignment()
     → redirect to take

GET  /student/assessments/{assessment}/take
     → getOrCreateAssignment() + startAssignment()
     → autoSubmitIfExpired() [time guard]
     → Inertia::render('Student/Assessments/Take|Work')
       props: assignment, assessment, questions, userAnswers, remainingSeconds

POST /student/assessments/{assessment}/answers
     → SaveAnswersRequest (nullable array)
     → isTimeExpired() + isDueDatePassed() guards
     → saveAnswers()

POST /student/assessments/{assessment}/submit
     → submitAssessment() → notify teacher
     → redirect to result or show

POST /student/assessments/{assessment}/file-answer   [homework only]
     → FileAnswerService::saveFileAnswer()

DELETE /student/assessments/{assessment}/file-answer/{answer}
     → FileAnswerService::deleteFileAnswer()

POST /student/assessments/{assessment}/security-violation
     → terminateForViolation() → forced_submission
```

### Teacher — Grading

```
GET  teacher/assessments/{assessment}/grade/{assignment}
     → TeacherClassAssessmentController / AssessmentController

POST teacher/assessments/{assessment}/save-grade/{assignment}
     → ScoringService::saveManualGrades()
     → Notify student via AssessmentGradedNotification
```

---

## 7. `MaterialiseAssessmentAssignments` Command

**Signature:** `assessment:materialise-assignments [--dry-run]`

**Purpose:** After a published assessment ends, creates `AssessmentAssignment` rows for any enrolled students who never opened the assessment. This ensures every student has a gradeable record even if they were absent.

**Scheduling:** Every 30 minutes (defined in `routes/console.php`).

**Logic:**
1. Queries all published assessments.
2. Filters in-PHP to those that have ended (`hasEnded()`).
3. For each assessment, looks up active enrollments in the assessment's class.
4. Creates missing rows (skips existing ones).
5. `--dry-run` reports counts without persisting.

---

## 8. Frontend Types

Key TypeScript interfaces in `resources/ts/types/`:

| Interface | File | Notes |
|---|---|---|
| `AssessmentAssignment` | `models/assessmentAssignment.ts` | Includes `status`, `score`, `auto_score` |
| `Answer` | `models/shared/answer.ts` | `assessment_assignment_id` (not `assignment_id`) |
| `Question` | `models/shared/question.ts` | Includes `type`, `choices` |
| `Choice` | `models/shared/choice.ts` | `is_correct` hidden from students at runtime |

**`auto_score`** on `AssessmentAssignment` is populated by the backend accessor. The frontend uses it as a fallback when `score` is null (i.e., manually-graded questions are still pending). Pattern: `assignment.score ?? assignment.auto_score ?? 0`.

---

## 9. Security

- `is_correct` is stripped from all choice data before rendering the take page via `StudentAssessmentController::hideCorrectAnswers()`.
- `is_correct` is also hidden on the results page unless `canShowCorrectAnswers` is true (requires `show_correct_answers` flag + graded).
- `security_violation` records the type of infraction; `forced_submission` flags auto-terminated sessions.
- Time expiry is double-checked with a configurable grace period (`assessment.timing.grace_period_seconds`).

---

## 10. Tests

| Test class | Coverage |
|---|---|
| `ScoringServiceTest` | All strategies, N+1 guards, `hasManualCorrectionQuestions` (Text + File), `calculateScoreForQuestion` |
| `ChoiceManagementServiceTest` | Create/update/delete, orphaned choice deletion |
| `QuestionCrudServiceTest` | CRUD + cascade delete |
| `QuestionDuplicationServiceTest` | Deep copy with choices |
| `AnswerFormatterServiceTest` | Format for grading, multiple choice grouping |
| `StudentAssessmentServiceTest` | Lifecycle, answer saving variants |
| `MaterialiseAssessmentAssignmentsTest` | Creation, deduplication, dry-run |
| `DeliveryModeModelsTest` | Model accessors including `auto_score`, `score`, `status` |
| `StudentAssessmentTakeTest` | Submit, auto-score, no-grade for text questions |
| `TeacherExceptionHandlingTest` | Reopen flow |
