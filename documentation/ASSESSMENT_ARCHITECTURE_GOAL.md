# Assessment Architecture: Delivery Modes

> Decision document -- February 2026
> Status: **Approved** -- Ready for implementation

---

## 1. The Problem

The platform supports 5 assessment types: `examen`, `controle`, `devoir`, `tp`, `projet`. Today, they all share the same delivery flow: the student opens the assessment, answers questions, and submits. But in reality, these types represent **fundamentally different experiences**:

- An `examen` is a timed, supervised event. The student sits down, the clock starts, and there is no going back.
- A `projet` is ongoing work. The student drafts, uploads files, comes back days later, and submits when ready.

Treating them the same creates problems:

- **No server-side timer** -- the countdown runs entirely in the browser and resets on page reload.
- **No way to upload files** for project-type work.
- **No distinction between "the student is cheating" and "the student's power went out."**
- **Security measures** (fullscreen lock, tab detection) apply everywhere or nowhere.

---

## 2. The Core Decision: Separate `type` from `delivery_mode`

**Decision**: Introduce a `delivery_mode` field (`supervised` | `homework`) on each assessment, independent from `type`.

**Why not just use `type` to determine behavior?**

Because the mapping is not always 1:1. A `devoir` could be a supervised in-class assignment ("devoir sur table") or a take-home exercise. A `tp` could require real-time supervision or be a week-long lab report. The teacher knows the pedagogical intent -- the system should let them express it.

- `type` describes **what the assessment is** (for grading, transcripts, statistics).
- `delivery_mode` describes **how the student takes it** (the experience, the rules).

The system suggests a default mode based on type (`examen`/`controle` default to `supervised`, others to `homework`), but the teacher can override it.

---

## 3. What Each Mode Means

### Supervised Mode

The student takes the assessment under controlled conditions, similar to a physical exam room.

| Aspect         | Behavior                                                                  |
| -------------- | ------------------------------------------------------------------------- |
| **Start**      | Irreversible. Once started, the clock runs.                               |
| **Timer**      | Personal countdown based on `started_at` + `duration_minutes`.            |
| **Security**   | Fullscreen required, tab switch detection, devtools detection, anti-copy. |
| **Sessions**   | Single session only. No "come back later."                                |
| **Violations** | Any security violation triggers immediate auto-submit.                    |
| **Content**    | Questions only. No file uploads.                                          |
| **Submission** | Automatic when time runs out, or manual by the student.                   |

### Homework Mode

The student works at their own pace before a deadline, similar to a take-home assignment.

| Aspect         | Behavior                                                 |
| -------------- | -------------------------------------------------------- |
| **Start**      | Resumable. The student can leave and come back.          |
| **Timer**      | None. A global `due_date` applies to everyone.           |
| **Security**   | None. The student works in a normal browser environment. |
| **Sessions**   | Multiple sessions. Drafts are saved automatically.       |
| **Violations** | Not applicable.                                          |
| **Content**    | Questions AND/OR file uploads (configurable).            |
| **Submission** | Manual, before the deadline.                             |

---

## 4. Why `started_at` Is Necessary (Even in Strict Supervised Mode)

The initial instinct was: if supervised mode is so strict that any violation ends the exam immediately, why track `started_at` at all? Three reasons:

### 4.1 Server-Side Timer Integrity

Without `started_at`, the timer exists only in the browser. JavaScript can be paused, modified, or bypassed. A student could:

- Edit the countdown variable in browser devtools (before detection kicks in).
- Intercept the timer's network requests.
- Modify the `duration_minutes` value in the page's initial props.

With `started_at` recorded on the server, every `saveAnswers` and `submit` request is validated:

```
if (now > started_at + duration_minutes + grace_period) then reject
```

The browser timer is a **UX convenience**. The server timer is the **source of truth**.

### 4.2 Audit Trail

Teachers and administrators need to answer questions like:

- "When exactly did this student start?"
- "How long did the student actually take?"
- "Did they submit at the last second or finish early?"

These are essential for dispute resolution, grading fairness, and academic integrity reports. Without `started_at`, this information is lost.

### 4.3 Involuntary Disruptions Are Not Cheating

This is the most important reason. Bad things happen during exams:

- The student's internet drops for 30 seconds.
- The browser crashes.
- There is a power outage.
- The student accidentally hits the browser's back button.

All of these look identical to "the student tried to leave the exam" from a pure detection standpoint. Without `started_at`, the system has no way to offer any recourse -- the exam is gone.

With `started_at`, the teacher has options:

- See that the student had 45 minutes remaining when the disruption occurred.
- Grant a controlled exception: reopen the assignment with `remaining_time = original_duration - elapsed`.
- Review the pattern: a student who "crashes" 3 times is suspicious; a student who crashes once during a known network outage is not.

**The policy is still strict by default** -- a violation auto-submits immediately. But `started_at` gives the teacher the power to make a human judgment call after the fact, rather than the system making an irreversible decision with incomplete information.

---

## 5. Database Changes

### 5.1 Table `assessments` -- New Columns

| Column               | Type                             | Purpose                                                             |
| -------------------- | -------------------------------- | ------------------------------------------------------------------- |
| `delivery_mode`      | `ENUM('supervised', 'homework')` | How the student experiences the assessment. Default: `supervised`.  |
| `due_date`           | `TIMESTAMP NULL`                 | Submission deadline for homework mode. Not used in supervised mode. |
| `max_file_size`      | `INTEGER NULL`                   | Maximum file size per upload in KB. NULL = no uploads allowed.      |
| `allowed_extensions` | `VARCHAR NULL`                   | Comma-separated list (e.g., `pdf,docx,zip`). NULL = any type.       |
| `max_files`          | `INTEGER DEFAULT 0`              | Maximum number of file attachments. 0 = file uploads disabled.      |

### 5.2 Table `assessment_assignments` -- New Column

| Column       | Type             | Purpose                                                             |
| ------------ | ---------------- | ------------------------------------------------------------------- |
| `started_at` | `TIMESTAMP NULL` | When the student began the assessment. Written once, never updated. |

### 5.3 New Table `assignment_attachments`

| Column                     | Type        | Purpose                               |
| -------------------------- | ----------- | ------------------------------------- |
| `id`                       | `BIGINT PK` |                                       |
| `assessment_assignment_id` | `FK`        | Links to the student's assignment.    |
| `file_name`                | `VARCHAR`   | Original filename as uploaded.        |
| `file_path`                | `VARCHAR`   | Storage path on disk.                 |
| `file_size`                | `INTEGER`   | File size in bytes.                   |
| `mime_type`                | `VARCHAR`   | MIME type for validation and display. |
| `uploaded_at`              | `TIMESTAMP` | When the file was uploaded.           |

---

## 6. Timer Logic (Supervised Mode)

```
remaining_seconds = (duration_minutes * 60) - seconds_elapsed_since(started_at)
```

**Rules:**

- `started_at` is written **once**, when the student opens the assessment for the first time via `start()`.
- Every subsequent page load (including after a crash/reload, if the teacher allows) receives `remaining_seconds` from the server -- not `duration_minutes`.
- If `remaining_seconds <= 0` at page load, the server auto-submits before rendering the page.
- Every `saveAnswers` request is rejected if `now > started_at + duration_minutes + 30s grace`.
- The 30-second grace period accounts for network latency on the final auto-save, not for extra working time.

---

## 7. Violation Handling (Supervised Mode)

| Event                        | Action                                                                                                           |
| ---------------------------- | ---------------------------------------------------------------------------------------------------------------- |
| Tab switch detected          | Immediate auto-submit                                                                                            |
| Left fullscreen              | Immediate auto-submit                                                                                            |
| DevTools opened              | Immediate auto-submit                                                                                            |
| Page reload / navigation     | If `started_at` exists and time remains: teacher decides. If time expired: auto-submit.                          |
| Network timeout on auto-save | Retry with exponential backoff. If still failing after 3 attempts: queue for submission when connection returns. |

**Key distinction**: Browser-detectable violations (tab, fullscreen, devtools) are treated as intentional. Infrastructure failures (network, crash) leave the assignment in an "interrupted" state that the teacher can review.

---

## 8. Frontend Architecture

### Two Distinct Pages

Instead of one `Take.tsx` page trying to handle both experiences:

- **`Take.tsx`** -- Supervised mode. Timer, fullscreen, security hooks, single session, auto-submit on violation.
- **`Work.tsx`** -- Homework mode. No timer, no security, file upload zone, save draft button, multi-session, manual submit.

The controller decides which page to render based on `assessment.delivery_mode`. The student never chooses -- it is determined by the assessment configuration.

### Mixed Content (Homework Mode)

A homework assessment can have both questions AND file uploads. `Work.tsx` displays:

1. A questions section (same question components as `Take.tsx`).
2. A file upload section (drag-and-drop, file list, delete).
3. A single "Submit" action that validates both sections are complete.

Questions and files are saved independently (auto-save for answers, immediate upload for files), but submission is atomic.

---

## 9. Workflows

### Supervised Flow

```
Student sees assessment
    --> clicks "Start Exam"
    --> Server writes started_at (irreversible)
    --> Server returns remaining_seconds + questions
    --> Student answers questions (auto-save every N seconds)
    --> Timer reaches 0 OR student clicks Submit OR violation detected
    --> Server validates: was time remaining?
    --> Marks as submitted
    --> No going back. Results page (if configured).
```

### Homework Flow

```
Student sees assessment
    --> clicks "Start Working"
    --> Server creates/resumes assignment (no started_at constraint)
    --> Student answers questions, uploads files
    --> Student leaves, comes back later, resumes
    --> Student clicks "Submit" before due_date
    --> Server validates: is due_date not passed? Are required sections filled?
    --> Assignment is submitted. No further edits.
```

### Teacher Exception Flow (Supervised -- After Involuntary Disruption)

```
Student's browser crashes during exam
    --> Assignment stays in "in_progress" state (not submitted)
    --> Teacher sees: "Student X started at 14:00, last save at 14:23, 37 min remaining"
    --> Teacher clicks "Allow Retry"
    --> System reopens assignment with remaining_seconds = 37 * 60
    --> Student continues where they left off
```

This flow is NOT automatic. It requires explicit teacher action, preserving academic integrity.

---

## 10. What This Architecture Does NOT Change

- **Assessment types** (`examen`, `controle`, `devoir`, `tp`, `projet`) remain as they are. They are used for grading categories, report cards, and statistics.
- **Question types** (`multiple`, `one_choice`, `boolean`, `text`, `essay`) remain as they are.
- **Scoring logic** remains unchanged. Supervised and homework assessments are scored the same way.
- **Existing security configuration** (`config/assessment.php`) remains. Supervised mode uses it; homework mode ignores it.
- **Authorization and enrollment logic** remains. Both modes require valid class enrollment.
