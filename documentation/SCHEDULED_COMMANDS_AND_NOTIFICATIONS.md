# Scheduled Commands & Notifications

## Overview

Evalium uses three Artisan commands scheduled via `routes/console.php` to automate assessment lifecycle events. All asynchronous work is handled through **queued Notifications** (no dedicated Job classes). The queue driver is `database` (configurable via `QUEUE_CONNECTION` in `.env`).

### Prerequisites

The queue worker must be running for notifications to be dispatched:

```bash
php artisan queue:listen --tries=1
```

Or via the unified dev command:

```bash
composer dev   # runs server + queue + vite concurrently
```

The scheduler must also be active (production):

```bash
php artisan schedule:work   # foreground (dev)
# OR via cron (production):
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## Scheduled Commands

### 1. `notifications:send-reminders`

| Property | Value |
|---|---|
| **Class** | `App\Console\Commands\SendAssessmentReminders` |
| **Frequency** | Every 5 minutes |
| **Options** | `withoutOverlapping`, `runInBackground` |

**Purpose:** Sends starting-soon notifications to enrolled students for assessments scheduled to begin within the next 15 minutes.

**Logic:**
1. Queries published assessments with a `scheduled_at` in the window `(now, now + 15min]` where `reminder_sent_at IS NULL`.
2. For each assessment, collects active enrolled students (excludes withdrawn enrollments).
3. Sends `AssessmentStartingSoonNotification` to each active student.
4. Sets `reminder_sent_at` on the assessment to prevent duplicate reminders.
5. If no active students exist, still sets `reminder_sent_at` to avoid reprocessing.

**Deduplication:** The `reminder_sent_at` timestamp column on the `assessments` table ensures each assessment triggers at most one reminder batch, regardless of how many times the scheduler runs.

**Notification sent:** `AssessmentStartingSoonNotification` (database channel, queued).

---

### 2. `assessment:auto-submit-expired`

| Property | Value |
|---|---|
| **Class** | `App\Console\Commands\AutoSubmitExpiredAssessments` |
| **Frequency** | Every 5 minutes |
| **Options** | `withoutOverlapping`, `runInBackground`, `--dry-run` |

**Purpose:** Auto-submits supervised assessment assignments where the student's time has expired or the global assessment window has closed, but the browser was closed or crashed before a proper submission.

**Logic:**
1. Queries all `AssessmentAssignment` rows that are started (`started_at IS NOT NULL`) but not submitted (`submitted_at IS NULL`), for published supervised assessments only.
2. For each assignment, checks two expiration conditions:
   - **Per-student time expired:** `started_at + duration_minutes < now()`
   - **Global assessment ended:** `assessment.hasEnded()` returns `true`
3. If expired, auto-scores the assignment using `StudentAssessmentService::autoScoreAssessment()`.
4. Sets `submitted_at` to the student's personal deadline (or global end time), plus `forced_submission = true` and `security_violation = 'time_expired'`.

**Dry-run mode:** Use `--dry-run` to preview which assignments would be submitted without persisting changes.

**Notification sent:** `AssessmentGradedNotification` (via `autoScoreAssessment()` when all questions are auto-gradable).

---

### 3. `assessment:materialise-assignments`

| Property | Value |
|---|---|
| **Class** | `App\Console\Commands\MaterialiseAssessmentAssignments` |
| **Frequency** | Every 30 minutes |
| **Options** | `withoutOverlapping`, `runInBackground`, `--dry-run` |

**Purpose:** Creates `AssessmentAssignment` rows for enrolled students who never opened a published, ended assessment. This ensures every student has a gradeable row so teachers can record marks (or zeros) without ghost entries.

**Logic:**
1. Queries all published assessments that have ended (`hasEnded()` returns `true`).
2. For each assessment, finds active enrollments in the assessment's class.
3. Creates an `AssessmentAssignment` row for each enrolled student who doesn't already have one.

**Dry-run mode:** Use `--dry-run` to preview which assignments would be created without persisting.

**Notification sent:** None.

---

## Schedule Summary

```
routes/console.php
```

| Command | Frequency | Overlapping | Background |
|---|---|---|---|
| `notifications:send-reminders` | Every 5 min | No | Yes |
| `assessment:auto-submit-expired` | Every 5 min | No | Yes |
| `assessment:materialise-assignments` | Every 30 min | No | Yes |

All three commands use `withoutOverlapping()` to prevent concurrent execution of the same command and `runInBackground()` to avoid blocking the scheduler process.

---

## Notifications

All assessment notifications implement `ShouldQueue` with the `Queueable` trait, meaning they are dispatched asynchronously via the database queue.

### Assessment Notifications

| Notification | Channel | Recipient | Trigger |
|---|---|---|---|
| `AssessmentPublishedNotification` | database | Students (enrolled, active) | Teacher publishes an assessment (`AssessmentService::publishAssessment()`) |
| `AssessmentStartingSoonNotification` | database | Students (enrolled, active) | Scheduler (`SendAssessmentReminders` command, 15 min before `scheduled_at`) |
| `AssessmentSubmittedNotification` | database | Teacher | Student submits or auto-submit on expired time (`StudentAssessmentService::notifyTeacherOfSubmission()`) |
| `AssessmentGradedNotification` | database | Student | Three distinct code paths (see below) |
| `UserCredentialsNotification` | mail | New user | Admin creates a user or bulk-enrolls students (`UserManagementService`, `EnrollmentController`) |

### `AssessmentGradedNotification` Dispatch Points

This notification is dispatched from three **distinct** code paths (not duplicates):

1. **Auto-score on submission** (`StudentAssessmentService::autoScoreAssessment()`) - When a student submits and all questions are auto-gradable (single/multiple choice), the system auto-grades and immediately notifies.

2. **Auto-grade zero** (`ScoringService::autoGradeZero()`) - Teacher triggers "grade zero" for students who never submitted. This is a deliberate teacher action.

3. **Manual grading** (`ScoringService::saveManualGrades()`) - Teacher manually grades text/open questions and saves scores. Notification sent after final save.

### Notification Data Shapes

#### `AssessmentPublishedNotification`
```json
{
    "type": "assessment_published",
    "assessment_id": 1,
    "assessment_title": "Math Exam",
    "subject": "Mathematics",
    "scheduled_at": "2026-03-01T10:00:00+00:00",
    "delivery_mode": "supervised",
    "url": "/student/assessments/1"
}
```

#### `AssessmentStartingSoonNotification`
```json
{
    "type": "assessment_starting_soon",
    "assessment_id": 1,
    "assessment_title": "Math Exam",
    "subject": "Mathematics",
    "scheduled_at": "2026-03-01T10:00:00+00:00",
    "delivery_mode": "supervised",
    "url": "/student/assessments/1"
}
```

#### `AssessmentSubmittedNotification`
```json
{
    "type": "assessment_submitted",
    "assessment_id": 1,
    "assessment_title": "Math Exam",
    "subject": "Mathematics",
    "assignment_id": 5,
    "student_name": "John Doe",
    "submitted_at": "2026-03-01T11:30:00+00:00",
    "url": "/teacher/assessments/1/review/5"
}
```

#### `AssessmentGradedNotification`
```json
{
    "type": "assessment_graded",
    "assessment_id": 1,
    "assessment_title": "Math Exam",
    "subject": "Mathematics",
    "assignment_id": 5,
    "url": "/student/assessments/1/result"
}
```

#### `UserCredentialsNotification`
Sent via **mail** channel (not database). Contains login credentials (email + temporary password) with role-specific messaging.

---

## Flow Diagrams

### Assessment Lifecycle Notifications

```
Teacher publishes assessment
    |
    v
AssessmentPublishedNotification --> Students (enrolled, active)
    |
    | (scheduler, 15 min before scheduled_at)
    v
AssessmentStartingSoonNotification --> Students (enrolled, active)
    |
    | (student submits OR auto-submit on expired time)
    v
AssessmentSubmittedNotification --> Teacher
    |
    | (auto-score / manual grade / grade zero)
    v
AssessmentGradedNotification --> Student
```

### Auto-Submit Flow

```
Scheduler (every 5 min)
    |
    v
assessment:auto-submit-expired
    |
    +-- For each started, unsubmitted supervised assignment:
    |       |
    |       +-- Time expired? (per-student or global)
    |       |       |
    |       |       Yes --> autoScoreAssessment() --> force submit
    |       |       |           |
    |       |       |           +-- All auto-gradable? --> AssessmentGradedNotification
    |       |       |
    |       |       No --> skip
```

### Reminder Deduplication Flow

```
Scheduler (every 5 min)
    |
    v
notifications:send-reminders
    |
    +-- Query: published, scheduled_at in (now, now+15min], reminder_sent_at IS NULL
    |
    +-- For each assessment:
    |       |
    |       +-- Active students? 
    |       |       |
    |       |       Yes --> Send AssessmentStartingSoonNotification
    |       |       No  --> (skip notification)
    |       |
    |       +-- Set reminder_sent_at = now() (always, even with no students)
```

---

## Testing

All scheduled commands have comprehensive test coverage:

| Test File | Tests | Assertions |
|---|---|---|
| `tests/Feature/Commands/SendAssessmentRemindersTest.php` | 10 | 30 |
| `tests/Feature/Commands/AutoSubmitExpiredAssessmentsTest.php` | 11 | ~35 |
| `tests/Feature/Commands/MaterialiseAssessmentAssignmentsTest.php` | 9 | ~30 |
| `tests/Feature/Notifications/AssessmentNotificationsTest.php` | 12 | ~40 |
| `tests/Feature/Notifications/UserCredentialsNotificationTest.php` | 6 | ~15 |

Run all schedule-related tests:

```bash
php artisan test tests/Feature/Commands/ tests/Feature/Notifications/
```

---

## Configuration

### Queue Driver

Configured in `config/queue.php`, defaults to `database`:

```env
QUEUE_CONNECTION=database
```

Ensure the `jobs`, `failed_jobs`, and `notifications` tables exist (included in default migrations).

### Schedule Monitoring

Use `php artisan schedule:list` to verify registered commands:

```bash
php artisan schedule:list
```

Expected output:

```
  0 */30 * * *  assessment:materialise-assignments  Next Due: ...
  */5 * * * *   assessment:auto-submit-expired       Next Due: ...
  */5 * * * *   notifications:send-reminders         Next Due: ...
```
