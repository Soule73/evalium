# Grade Calculation System

## Overview

The grading system uses a **double-coefficient weighted average** to compute student grades at two levels:

1. **Assessment grade** — a single evaluation normalized to `/20`
2. **Subject grade** — weighted average of assessment grades, by coefficient
3. **Annual average** — weighted average of subject grades, by subject coefficient

All grade computation logic is centralized in `GradeCalculationService`. No other service or controller must re-implement the formulas.

---

## Formulas

### 1. Normalized assessment grade

An assessment can have any point distribution set by the teacher (e.g. Q1: 10 pts, Q2: 5 pts, Q3: 10 pts → max = 25 pts). The raw score is normalized to a grade out of 20:

$$
\text{note\_eval} = \frac{\text{raw\_score}}{\text{max\_points}} \times 20
$$

**Example:**
- Q1: 10 pts, Q2: 5 pts, Q3: 10 pts → `max_points = 25`
- Student scores 18/25 → `note_eval = (18/25) × 20 = 14.4/20`

Only assignments with `graded_at IS NOT NULL` are included.

---

### 2. Subject grade (Note Matière)

Each assessment within a subject carries a `coefficient` set by the teacher. The subject grade is the weighted average of all normalized assessment grades for that subject:

$$
\text{Note\_Matière} = \frac{\sum_{i}(\text{coeff}_{i} \times \text{note\_eval}_{i})}{\sum_{i} \text{coeff}_{i}}
$$

**Example:**

| Assessment | coeff | note_eval |
|---|---|---|
| Examen mi-semestre | 2 | 14.4/20 |
| Devoir maison | 1 | 16.0/20 |

$$
\text{Note\_Matière} = \frac{(2 \times 14.4) + (1 \times 16.0)}{2 + 1} = \frac{28.8 + 16.0}{3} = \frac{44.8}{3} \approx 14.93/20
$$

---

### 3. Annual average (Moyenne Annuelle)

Each subject (class_subject) carries a `coefficient` defined by the class structure. The annual average is the weighted average of all subject grades:

$$
\text{Moyenne\_Annuelle} = \frac{\sum_{j}(\text{coeff\_matière}_{j} \times \text{Note\_Matière}_{j})}{\sum_{j} \text{coeff\_matière}_{j}}
$$

**Example:**

| Subject | coeff | Note_Matière |
|---|---|---|
| Mathématiques | 4 | 14.93/20 |
| Physique | 3 | 12.50/20 |
| Histoire | 2 | 17.00/20 |

$$
\text{Moyenne\_Annuelle} = \frac{(4 \times 14.93) + (3 \times 12.50) + (2 \times 17.00)}{4 + 3 + 2} = \frac{59.72 + 37.50 + 34.00}{9} = \frac{131.22}{9} \approx 14.58/20
$$

---

## Important rules

- A subject grade is `null` if no assignments have been graded yet.
- A subject with `null` grade is **excluded** from the annual average (neither numerator nor denominator).
- An assessment with `max_points = 0` is **excluded** from the subject grade to avoid division by zero.
- An assessment with `coefficient = 0` contributes `0` to the weighted score but also `0` to the denominator — its grade is effectively ignored.

---

## Source of truth: `GradeCalculationService`

**File:** `app/Services/Core/GradeCalculationService.php`

The canonical normalization formula is implemented once in the private method `computeWeightedGrade`:

```php
/**
 * @param array<int, array{score: float, max_points: float, coefficient: float}> $triplets
 */
private function computeWeightedGrade(array $triplets): ?float
```

All public methods in this service delegate to it. **Never re-implement this formula elsewhere.**

### Public API

| Method | Input | Output | Description |
|---|---|---|---|
| `calculateSubjectGrade(User, ClassSubject)` | student + class_subject | `float\|null` (`/20`) | Grade for one subject |
| `calculateAnnualAverage(User, AcademicYear)` | student + year | `float\|null` (`/20`) | Annual average across all subjects |
| `getGradeBreakdown(User, ClassModel)` | student + class | array | Full breakdown (subjects + annual avg) |
| `getGradeBreakdownFromLoaded(User, ClassModel, Collection)` | pre-loaded class subjects | array | Same, from eager-loaded data (zero extra queries) |
| `getStudentOverallStats(User, ?int, ?Enrollment)` | student + optional filters | array | Dashboard stats (avg, counts, subject breakdown) |
| `getStudentAssessmentSummary(User, ?int)` | student + optional year | array | Per-assessment list with raw + normalized grade |
| `calculateClassAverageForSubject(ClassSubject)` | class_subject | `float\|null` | Average across all students for one subject |
| `getEnrollmentAssignments(Enrollment, array, int)` | enrollment + filters | Paginator | Paginated assignment list with virtual entries |

---

## Where each formula is used

### `GradeCalculationService` consumers

| Context | Controller | Method called |
|---|---|---|
| Student enrollment page | `StudentEnrollmentController` | `getGradeBreakdownFromLoaded` |
| Student dashboard | `StudentDashboardService` → `GradeCalculationService` | `getStudentOverallStats` |
| Teacher — view student profile | `TeacherClassStudentController` | `getGradeBreakdown` |
| Admin — view student profile | `ClassStudentController` | `getGradeBreakdown` |

### Performance alternative — `TeacherClassResultsService`

**File:** `app/Services/Teacher/TeacherClassResultsService.php`

The class results page (teacher) computes the same formulas **directly in SQL** to avoid N+1 queries on large classes. The implementation mirrors `computeWeightedGrade` exactly:

- `computeAssessmentStats` — `AVG(raw_score) → normalize /20` per assessment
- `computeStudentStats` — `Σ(coeff × raw/max × 20) / Σ(coeff)` per student

Any change to the canonical formula **must be replicated in both places**.

---

## `AssessmentStatsService` — special case

**File:** `app/Services/Core/AssessmentStatsService.php`

Used exclusively for the **single-assessment show page** (`admin/teacher/classes/{id}/assessments/{id}`).

Returns `average_score` as **raw points** (not `/20`), because the frontend displays it as `X / totalPoints` (e.g. `8.5 / 15`). This is intentional: the denominator varies per assessment and is shown alongside.

Normalization to `/20` is **not applied here**. This is the only legitimate exception to the `/20` rule.

---

## Score computation — `AssessmentAssignment::getScoreAttribute`

**File:** `app/Models/AssessmentAssignment.php`

The `score` accessor on `AssessmentAssignment` returns the sum of all `answers.score` for that assignment:

```php
public function getScoreAttribute(): ?float
{
    if (! $this->graded_at) {
        return null;
    }
    // Uses eager-loaded answers_sum_score when available (avoid N+1)
    if (array_key_exists('answers_sum_score', $this->attributes)) {
        return (float) $this->attributes['answers_sum_score'];
    }
    return (float) $this->answers()->sum('score');
}
```

Always use `->withSum('answers', 'score')` when loading assignments in bulk to avoid N+1 queries.

---

## Display conventions

| Context | Format | Example |
|---|---|---|
| Single assessment stats (show page) | `raw / max_points` | `8.5 / 15` |
| Subject grade | `X / 20` | `14.93 / 20` |
| Annual average | `X / 20` | `14.58 / 20` |
| Student dashboard average | `X / 20` | `14.58 / 20` |
| Class results — per assessment | `X / 20` | `14.4 / 20` |
| Class results — per student | `X / 20` | `14.93 / 20` |

---

## Data flow diagram

```
Teacher creates Assessment
  └─ Sets questions with points (Q1: 10, Q2: 5, Q3: 10) → max_points = 25
  └─ Sets coefficient (e.g. 2)

Student takes Assessment
  └─ Saves answers → answers.score per question
  └─ Submits → assessment_assignments.submitted_at

Teacher grades
  └─ Saves manual scores → answers.score updated
  └─ Sets graded_at → triggers ScoringService.saveManualGrades

Grade calculation (on demand, not stored)
  └─ raw_score = SUM(answers.score)            [from AssessmentAssignment accessor]
  └─ note_eval = (raw_score / max_points) × 20 [GradeCalculationService.computeWeightedGrade]
  └─ Note_Matière = Σ(coeff_i × note_eval_i) / Σ(coeff_i)
  └─ Moyenne_Annuelle = Σ(coeff_j × Note_Matière_j) / Σ(coeff_j)
```

---

## Key constraints

- Grades are **computed on demand**, never stored in the database.
- Only assignments with `graded_at IS NOT NULL` count toward any grade.
- Assessments with `max_points = 0` (no questions) are silently excluded.
- `TeacherClassResultsService` must stay in sync with `GradeCalculationService::computeWeightedGrade` — both implement the same formula, one in PHP, one in SQL.
