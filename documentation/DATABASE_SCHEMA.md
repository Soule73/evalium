# Database Schema Reference

> Last updated: sync with migrations state as of `fee63ed`

## Overview

The Examena database contains **23 application tables** organized around five domain groups:

| Domain | Tables |
|--------|--------|
| **Identity & Auth** | `users`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` |
| **Academic Structure** | `levels`, `academic_years`, `semesters`, `subjects`, `classes` |
| **Enrollment** | `enrollments`, `class_subjects` |
| **Assessment** | `assessments`, `assessment_assignments`, `questions`, `choices`, `answers` |
| **System** | `notifications`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens` |

**Key design decisions:**
- Soft deletes only on `users` and `assessments`
- Scores are stored per-answer (`answers.score`), not per-assignment
- File metadata is embedded in `answers` (no separate attachments table)
- Student access to assessments goes through enrollment (`enrollment → assessment_assignment`)
- Permissions managed by Spatie (`spatie/laravel-permission`) via polymorphic pivots
- Notifications use Laravel's built-in system with UUID primary key

---

## Application Tables

### `users`

Central identity table. All roles (admin, teacher, student) share this table and are differentiated via Spatie roles.

| Column | Type | Nullable | Default | Constraints |
|--------|------|----------|---------|-------------|
| `id` | bigint | No | — | PK, auto-increment |
| `name` | varchar | No | — | |
| `email` | varchar | No | — | UNIQUE |
| `avatar` | varchar | Yes | NULL | |
| `locale` | varchar(2) | No | `'en'` | |
| `email_verified_at` | timestamp | Yes | NULL | |
| `is_active` | boolean | No | `true` | |
| `password` | varchar | No | — | |
| `remember_token` | varchar | Yes | NULL | |
| `created_at` / `updated_at` | timestamp | Yes | NULL | |
| `deleted_at` | timestamp | Yes | NULL | Soft delete |

**Indexes:**
- `PRIMARY` on `id`
- `users_email_unique` on `email`
- `users_email_active_idx` on `(email, is_active)`

---

### `levels`

Educational levels (e.g., Grade 10, Terminale). Referenced by `subjects` and `classes`.

| Column | Type | Nullable | Default | Constraints |
|--------|------|----------|---------|-------------|
| `id` | bigint | No | — | PK |
| `name` | varchar | No | — | UNIQUE |
| `code` | varchar | No | — | UNIQUE |
| `description` | text | Yes | NULL | |
| `order` | integer | No | `0` | |
| `is_active` | boolean | No | `true` | |
| `created_at` / `updated_at` | timestamp | Yes | NULL | |

**Indexes:**
- `levels_name_unique`, `levels_code_unique`

---

### `academic_years`

Represents a school year. Exactly one row has `is_current = true` at any time.

| Column | Type | Nullable | Default | Constraints |
|--------|------|----------|---------|-------------|
| `id` | bigint | No | — | PK |
| `name` | varchar | No | — | UNIQUE |
| `start_date` | date | No | — | |
| `end_date` | date | No | — | CHECK: `end_date > start_date` |
| `is_current` | boolean | No | `false` | |
| `description` | text | Yes | NULL | |
| `created_at` / `updated_at` | timestamp | Yes | NULL | |

**Indexes:**
- `academic_years_name_unique`
- `academic_years_is_current_index` on `is_current`

---

### `semesters`

Semester subdivisions of an academic year. Each year has at most 2 semesters.

| Column | Type | Nullable | Default | Constraints |
|--------|------|----------|---------|-------------|
| `id` | bigint | No | — | PK |
| `academic_year_id` | bigint | No | — | FK → `academic_years` CASCADE |
| `name` | varchar | No | — | |
| `start_date` | date | No | — | |
| `end_date` | date | No | — | CHECK: `end_date > start_date` |
| `order_number` | tinyint | No | — | CHECK: `1` or `2` |
| `created_at` / `updated_at` | timestamp | Yes | NULL | |

**Indexes:**
- `semesters_academic_year_id_index`
- `semesters_academic_year_id_order_number_unique` — enforces max 2 semesters per year

---

### `subjects`

Academic subjects scoped to a level. Teachers are assigned to subjects per class via `class_subjects`.

| Column | Type | Nullable | Default | Constraints |
|--------|------|----------|---------|-------------|
| `id` | bigint | No | — | PK |
| `level_id` | bigint | No | — | FK → `levels` CASCADE |
| `name` | varchar | No | — | |
| `code` | varchar | No | — | UNIQUE |
| `description` | text | Yes | NULL | |
| `created_at` / `updated_at` | timestamp | Yes | NULL | |

**Indexes:**
- `subjects_code_unique`
- `subjects_level_id_index`
- `subjects_level_id_name_unique` on `(level_id, name)`

---

### `classes`

A class groups students for a given academic year and level. A name must be unique within an `(academic_year, level)` pair.

| Column | Type | Nullable | Default | Constraints |
|--------|------|----------|---------|-------------|
| `id` | bigint | No | — | PK |
| `academic_year_id` | bigint | No | — | FK → `academic_years` CASCADE |
| `level_id` | bigint | No | — | FK → `levels` CASCADE |
| `name` | varchar | No | — | |
| `description` | text | Yes | NULL | |
| `max_students` | integer | Yes | NULL | CHECK: `> 0` |
| `created_at` / `updated_at` | timestamp | Yes | NULL | |

**Indexes:**
- `classes_academic_year_id_level_id_name_unique` on `(academic_year_id, level_id, name)`
- `classes_academic_year_id_level_id_index`
- `classes_level_id_foreign`

---

### `enrollments`

Links a student to a class. A student can only be enrolled once per class (`UNIQUE class_id, student_id`).

| Column | Type | Nullable | Default | Constraints |
|--------|------|----------|---------|-------------|
| `id` | bigint | No | — | PK |
| `class_id` | bigint | No | — | FK → `classes` CASCADE |
| `student_id` | bigint | No | — | FK → `users` CASCADE |
| `enrolled_at` | date | No | — | |
| `withdrawn_at` | date | Yes | NULL | |
| `status` | enum | No | `active` | `active`, `withdrawn`, `completed` |
| `created_at` / `updated_at` | timestamp | Yes | NULL | |

**Indexes:**
- `enrollments_class_id_student_id_unique`
- `enrollments_class_id_status_index`
- `enrollments_student_id_index`

---

### `class_subjects`

Pivot assigning a teacher to a subject within a class for a given period. Optional semester scoping. `teacher_id` is nullable (subject can exist without assigned teacher).

| Column | Type | Nullable | Default | Constraints |
|--------|------|----------|---------|-------------|
| `id` | bigint | No | — | PK |
| `class_id` | bigint | No | — | FK → `classes` CASCADE |
| `subject_id` | bigint | No | — | FK → `subjects` CASCADE |
| `teacher_id` | bigint | **Yes** | NULL | FK → `users` SET NULL ON DELETE |
| `semester_id` | bigint | Yes | NULL | FK → `semesters` SET NULL ON DELETE |
| `coefficient` | decimal(5,2) | No | — | CHECK: `> 0` |
| `valid_from` | date | No | — | |
| `valid_to` | date | Yes | NULL | CHECK: `valid_to >= valid_from` |
| `created_at` / `updated_at` | timestamp | Yes | NULL | |

**Indexes:**
- `class_subjects_class_id_subject_id_valid_to_index` on `(class_id, subject_id, valid_to)`
- `class_subjects_teacher_id_valid_to_index` on `(teacher_id, valid_to)`
- `class_subjects_semester_id_index`

---

### `assessments`

An assessment created by a teacher for a class-subject combination. Supports soft deletes.

| Column | Type | Nullable | Default | Constraints |
|--------|------|----------|---------|-------------|
| `id` | bigint | No | — | PK |
| `class_subject_id` | bigint | No | — | FK → `class_subjects` CASCADE |
| `teacher_id` | bigint | No | — | FK → `users` CASCADE |
| `title` | varchar | No | — | |
| `description` | text | Yes | NULL | |
| `type` | enum | No | — | `homework`, `exam`, `practical`, `quiz`, `project` |
| `delivery_mode` | enum | No | `supervised` | `supervised`, `homework` |
| `coefficient` | decimal(5,2) | No | — | CHECK: `> 0` |
| `duration_minutes` | integer | Yes | NULL | CHECK: `> 0` |
| `scheduled_at` | timestamp | Yes | NULL | For supervised sessions |
| `due_date` | timestamp | Yes | NULL | For homework mode |
| `settings` | json | Yes | NULL | Security/config options |
| `is_published` | boolean | No | `false` | |
| `deleted_at` | timestamp | Yes | NULL | Soft delete |
| `created_at` / `updated_at` | timestamp | Yes | NULL | |

**Indexes:**
- `assessments_class_subject_id_type_index`
- `assessments_teacher_id_scheduled_at_index`
- `assessments_delivery_mode_index`

---

### `assessment_assignments`

Tracks each student's participation in an assessment. Created via enrollment, not directly.

| Column | Type | Nullable | Default | Constraints |
|--------|------|----------|---------|-------------|
| `id` | bigint | No | — | PK |
| `assessment_id` | bigint | No | — | FK → `assessments` CASCADE |
| `enrollment_id` | bigint | No | — | FK → `enrollments` CASCADE |
| `started_at` | timestamp | Yes | NULL | Set when student begins |
| `submitted_at` | timestamp | Yes | NULL | Set on submission |
| `graded_at` | timestamp | Yes | NULL | Set after teacher correction |
| `teacher_notes` | text | Yes | NULL | Global feedback from teacher |
| `forced_submission` | boolean | No | `false` | Auto-submitted on timeout/violation |
| `security_violation` | varchar | Yes | NULL | Violation type if detected |
| `created_at` / `updated_at` | timestamp | Yes | NULL | |

**Indexes:**
- `assessment_assignments_assessment_id_enrollment_id_unique`
- `assessment_assignments_enrollment_id_submitted_at_index`
- `assessment_assignments_assessment_id_graded_at_index`

---

### `questions`

Questions belonging to an assessment. Ordered via `order_index`.

| Column | Type | Nullable | Default | Constraints |
|--------|------|----------|---------|-------------|
| `id` | bigint | No | — | PK |
| `assessment_id` | bigint | No | — | FK → `assessments` CASCADE |
| `content` | text | No | — | |
| `type` | enum | No | `text` | `text`, `multiple`, `one_choice`, `boolean`, `file` |
| `points` | integer | No | `1` | |
| `order_index` | integer | No | `1` | |
| `created_at` / `updated_at` | timestamp | Yes | NULL | |

**Indexes:**
- `questions_assessment_id_order_index_index` on `(assessment_id, order_index)`

---

### `choices`

Answer choices for `multiple`, `one_choice`, and `boolean` questions.

| Column | Type | Nullable | Default | Constraints |
|--------|------|----------|---------|-------------|
| `id` | bigint | No | — | PK |
| `question_id` | bigint | No | — | FK → `questions` CASCADE |
| `content` | varchar | No | — | |
| `is_correct` | boolean | No | `false` | |
| `order_index` | integer | No | `1` | |
| `created_at` / `updated_at` | timestamp | Yes | NULL | |

**Indexes:**
- `choices_question_id_foreign`

---

### `answers`

Stores a student's answer to a question within an assignment. Supports all question types including file uploads. Score and feedback per answer enable precise grading.

| Column | Type | Nullable | Default | Constraints |
|--------|------|----------|---------|-------------|
| `id` | bigint | No | — | PK |
| `assessment_assignment_id` | bigint | No | — | FK → `assessment_assignments` CASCADE |
| `question_id` | bigint | No | — | FK → `questions` CASCADE |
| `choice_id` | bigint | Yes | NULL | FK → `choices` CASCADE |
| `answer_text` | text | Yes | NULL | For `text` type questions |
| `file_name` | varchar | Yes | NULL | For `file` type questions |
| `file_path` | varchar | Yes | NULL | |
| `file_size` | unsigned int | Yes | NULL | In bytes |
| `mime_type` | varchar | Yes | NULL | |
| `score` | float | Yes | NULL | Points awarded by teacher |
| `feedback` | text | Yes | NULL | Per-question teacher feedback |
| `created_at` / `updated_at` | timestamp | Yes | NULL | |

**Indexes:**
- `answers_assignment_question_idx` on `(assessment_assignment_id, question_id)`
- `answers_question_id_foreign`
- `answers_choice_id_foreign`

---

### `notifications`

Laravel's built-in notification table. Uses UUID primary key and polymorphic relation (`notifiable`).

| Column | Type | Nullable | Default | Constraints |
|--------|------|----------|---------|-------------|
| `id` | uuid | No | — | PK |
| `type` | varchar | No | — | Notification class FQCN |
| `notifiable_type` | varchar | No | — | Polymorphic morph type |
| `notifiable_id` | bigint | No | — | Polymorphic morph id |
| `data` | text | No | — | JSON payload |
| `read_at` | timestamp | Yes | NULL | |
| `created_at` / `updated_at` | timestamp | Yes | NULL | |

**Indexes:**
- `notifications_notifiable_type_notifiable_id_index` on `(notifiable_type, notifiable_id)`

---

## Spatie Permission Tables

Managed by `spatie/laravel-permission`. Do not modify manually.

### `roles`

| Column | Type |
|--------|------|
| `id` | bigint PK |
| `name` | varchar |
| `guard_name` | varchar |
| `created_at` / `updated_at` | timestamp |

UNIQUE: `(name, guard_name)`

### `permissions`

Same structure as `roles`.

UNIQUE: `(name, guard_name)`

### `model_has_roles`

Polymorphic pivot assigning roles to any model.

| Column | Type |
|--------|------|
| `role_id` | bigint FK → `roles` |
| `model_type` | varchar |
| `model_id` | bigint |

PK: `(role_id, model_id, model_type)`

### `model_has_permissions`

Same structure as `model_has_roles`, for direct permission grants.

### `role_has_permissions`

| Column | Type |
|--------|------|
| `permission_id` | bigint FK → `permissions` |
| `role_id` | bigint FK → `roles` |

PK: `(permission_id, role_id)`

---

## Relationship Map

```
academic_years ──< semesters
academic_years ──< classes >── levels
                    classes ──< enrollments >── users (students)
                    classes ──< class_subjects >── subjects
                                class_subjects >── users (teachers)
                                class_subjects >── semesters
                                class_subjects ──< assessments >── users (teachers)
                                                    assessments ──< questions ──< choices
                                                    assessments ──< assessment_assignments
                     enrollments ──< assessment_assignments ──< answers >── questions
                                                                  answers >── choices
users ──< notifications (polymorphic)
users >── roles (via model_has_roles)
roles >── permissions (via role_has_permissions)
```

---

## System / Infrastructure Tables

| Table | Purpose |
|-------|---------|
| `sessions` | Laravel database session driver |
| `cache` / `cache_locks` | Laravel cache with database driver |
| `jobs` / `job_batches` / `failed_jobs` | Laravel queue |
| `password_reset_tokens` | Password reset flow |
| `migrations` | Migration history |
