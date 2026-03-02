# Grade Reports, Application Settings & Data Export

> **Status**: Approved - Ready for implementation
> **Date**: March 2, 2026
> **Feature Branch**: `feat/grade-reports-exports` (to create after `refactor/packages-reorganization`)

---

## Table of Contents

1. [Overview](#1-overview)
2. [Application Settings System](#2-application-settings-system)
3. [Semester-Level Grading](#3-semester-level-grading)
4. [Grade Reports (Bulletins)](#4-grade-reports-bulletins)
5. [Data Export](#5-data-export)
6. [Database Migrations](#6-database-migrations)
7. [Implementation Plan](#7-implementation-plan)
8. [Package Dependencies](#8-package-dependencies)

---

## 1. Overview

This feature introduces three major capabilities to Evalium:

1. **Application Settings** - Configurable school information and bulletin preferences via `spatie/laravel-settings`
2. **Grade Reports (Bulletins)** - Official PDF report cards per student, per semester or annually, with teacher remarks and class ranking
3. **Data Export** - Excel/CSV export for student lists, assessment results, and class results via `Maatwebsite/Laravel-Excel`

### Prerequisites

- Semester-level grade calculation (currently only annual averages exist)
- Class ranking calculation
- `spatie/laravel-settings` package installation
- `barryvdh/laravel-dompdf` package installation
- `Maatwebsite/Laravel-Excel` package installation

### Out of Scope (v2)

- Data import (students, enrollments, grades) - deferred to v2
- Head teacher ("professeur principal") concept
- Trimester-based grading

---

## 2. Application Settings System

### Package

[`spatie/laravel-settings`](https://github.com/spatie/laravel-settings) - Type-safe settings classes with built-in caching and versioned settings migrations.

### Permission

New Spatie Permission: `manage settings` (assigned to `admin` and `super_admin` roles).

### Settings Classes

#### `GeneralSettings`

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `school_name` | `string` | `'Evalium'` | School/institution name displayed on reports |
| `logo_path` | `?string` | `null` | Path to uploaded logo (stored in `storage/app/public/`) |
| `default_locale` | `string` | `'fr'` | Default locale applied to new users |

```php
// app/Settings/GeneralSettings.php
class GeneralSettings extends Settings
{
    public string $school_name;
    public ?string $logo_path;
    public string $default_locale;

    public static function group(): string
    {
        return 'general';
    }
}
```

#### `BulletinSettings`

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `show_ranking` | `bool` | `true` | Display student rank on report card |
| `show_class_average` | `bool` | `true` | Display class average per subject |
| `show_min_max` | `bool` | `false` | Display min/max scores per subject |

```php
// app/Settings/BulletinSettings.php
class BulletinSettings extends Settings
{
    public bool $show_ranking;
    public bool $show_class_average;
    public bool $show_min_max;

    public static function group(): string
    {
        return 'bulletin';
    }
}
```

### Settings UI

- New admin page: `Pages/Admin/Settings/Index.tsx`
- Accessible via sidebar menu item (visible only with `manage settings` permission)
- Grouped sections: General, Bulletin
- Logo upload with preview

### Settings Controller & Routes

```
GET  /admin/settings          SettingsController@index
POST /admin/settings/general  SettingsController@updateGeneral
POST /admin/settings/bulletin SettingsController@updateBulletin
POST /admin/settings/logo     SettingsController@uploadLogo
```

---

## 3. Semester-Level Grading

### Current Gap

`GradeCalculationService` only computes **annual** averages. The `class_subjects.semester_id` FK exists but is unused in grading logic.

### New Methods in `GradeCalculationService`

#### `calculateSemesterGrade(Enrollment $enrollment, ClassSubject $classSubject, Semester $semester): ?float`

Filters assessments by `class_subjects.semester_id` before applying the weighted average formula.

#### `calculateSemesterAverage(Enrollment $enrollment, Semester $semester): ?array`

Computes the weighted average across all subjects for a given semester:

```
Semester_Average = Sum(coeff_subject_j * Subject_Grade_j) / Sum(coeff_subject_j)
```

Returns `['average' => float, 'total_coefficient' => int, 'subjects' => array]`.

#### `getSemesterGradeBreakdown(Enrollment $enrollment, Semester $semester): array`

Returns a per-subject breakdown filtered by semester, structured identically to `getGradeBreakdown()`.

### New Method: `calculateClassRanking`

#### `calculateClassRanking(ClassModel $class, ?Semester $semester = null): array`

Computes ranking for all enrolled students in a class:
- Sorted by average descending
- Tied averages share the same rank; next rank is skipped (e.g., 1, 2, 2, 4)
- Returns `[['enrollment_id' => int, 'student' => User, 'average' => float, 'rank' => int], ...]`

### Mirror in `TeacherClassResultsService`

The SQL-based equivalent in `TeacherClassResultsService` must also support semester filtering via an optional `?Semester $semester` parameter on relevant methods.

---

## 4. Grade Reports (Bulletins)

### 4.1. Report Card Content

#### Header

| Field | Source |
|-------|--------|
| School name | `GeneralSettings::$school_name` |
| Logo | `GeneralSettings::$logo_path` |
| Academic year | `academic_years.name` |
| Period | `semesters.name` or "Annual" |
| Student name | `users.name` |
| Class | `classes.name` |
| Level | `levels.name` |

#### Body - Subject Grades Table

| Column | Source | Conditional |
|--------|--------|-------------|
| Subject | `subjects.name` | Always |
| Coefficient | `class_subjects.coefficient` | Always |
| Grade /20 | `calculateSemesterGrade()` or `calculateSubjectGrade()` | Always |
| Assessment count | Count of graded assignments | Always |
| Teacher remark | `grade_reports.remarks` JSON | Always |
| Class average | Computed per subject | If `BulletinSettings::$show_class_average` |
| Min / Max | Computed per subject | If `BulletinSettings::$show_min_max` |

#### Footer

| Field | Source | Conditional |
|-------|--------|-------------|
| Overall average /20 | `calculateSemesterAverage()` or `calculateAnnualAverage()` | Always |
| Rank | `calculateClassRanking()` | If `BulletinSettings::$show_ranking` |
| Class size | `enrollments` count (active) | Always |
| General remark | `grade_reports.general_remark` | Always |

### 4.2. Remarks System

**Approach**: Mixed (auto-generated + teacher-editable)

Auto-generation rules (configurable in future iterations):

| Grade Range | French | English |
|-------------|--------|---------|
| >= 16 | Tres bien | Excellent |
| >= 14 | Bien | Good |
| >= 12 | Assez bien | Fairly good |
| >= 10 | Passable | Satisfactory |
| < 10 | Insuffisant | Insufficient |

Teachers can override auto-generated remarks per student per subject. General remarks are auto-generated based on overall average, editable by admin.

Remarks are stored in the `grade_reports.remarks` JSON column:

```json
{
  "subjects": [
    {
      "class_subject_id": 1,
      "subject_name": "Mathematics",
      "remark": "Excellent work, keep it up",
      "auto_generated": false
    }
  ]
}
```

### 4.3. Report Lifecycle

```
Status Flow:  draft -> validated -> published

draft:       Grades computed, remarks editable, PDF not yet generated
validated:   Admin validates, snapshot frozen (data JSON), PDF generated
published:   Visible to students (future: notification sent)
```

### 4.4. PDF Generation

**Package**: `barryvdh/laravel-dompdf`

**Template**: `resources/views/pdf/grade-report.blade.php`
- A4 portrait, inline CSS (DomPDF limitation)
- Table-based layout
- School logo + header
- Subject grades table
- Footer with averages and ranking

**Generation modes**:
- **Individual**: Single student PDF download
- **Batch**: All students in a class -> ZIP file (or multi-page PDF)

### 4.5. Service Architecture

```
app/Services/Core/GradeReport/
    GradeReportService.php           -- Orchestrates report creation, validation, PDF generation
    RemarkGeneratorService.php       -- Auto-generates remarks based on grades
```

#### `GradeReportService`

| Method | Description |
|--------|-------------|
| `generateDraft(ClassModel, ?Semester)` | Computes grades for all students, creates draft reports |
| `updateRemarks(GradeReport, array)` | Updates subject remarks for a report |
| `updateGeneralRemark(GradeReport, string)` | Updates general remark |
| `validate(GradeReport, User)` | Freezes snapshot, sets status to validated |
| `validateBatch(ClassModel, ?Semester, User)` | Validates all drafts for a class |
| `generatePdf(GradeReport)` | Generates PDF from validated report |
| `generateBatchPdf(ClassModel, ?Semester)` | Generates ZIP of all PDFs for a class |
| `publish(GradeReport)` | Sets status to published |

### 4.6. Controllers & Routes

```
# Admin routes
GET    /admin/grade-reports                              GradeReportController@index
POST   /admin/grade-reports/generate                     GradeReportController@generate
GET    /admin/grade-reports/{report}                      GradeReportController@show
PUT    /admin/grade-reports/{report}/general-remark       GradeReportController@updateGeneralRemark
POST   /admin/grade-reports/{report}/validate             GradeReportController@validate
POST   /admin/grade-reports/validate-batch                GradeReportController@validateBatch
GET    /admin/grade-reports/{report}/download              GradeReportController@download
GET    /admin/grade-reports/download-batch                 GradeReportController@downloadBatch
POST   /admin/grade-reports/{report}/publish               GradeReportController@publish

# Teacher routes (remarks only)
GET    /teacher/grade-reports                              TeacherGradeReportController@index
GET    /teacher/grade-reports/{report}                      TeacherGradeReportController@show
PUT    /teacher/grade-reports/{report}/remarks              TeacherGradeReportController@updateRemarks

# Student routes (read only, published reports)
GET    /student/grade-reports                              StudentGradeReportController@index
GET    /student/grade-reports/{report}                      StudentGradeReportController@show
GET    /student/grade-reports/{report}/download              StudentGradeReportController@download
```

### 4.7. Frontend Pages

| Page | Role | Description |
|------|------|-------------|
| `Admin/GradeReports/Index.tsx` | Admin | Select class + period, generate drafts, list reports |
| `Admin/GradeReports/Show.tsx` | Admin | View/edit general remark, validate, download |
| `Teacher/GradeReports/Index.tsx` | Teacher | List reports for teacher's class-subjects |
| `Teacher/GradeReports/Show.tsx` | Teacher | View grades, edit subject remarks |
| `Student/GradeReports/Index.tsx` | Student | List published reports |
| `Student/GradeReports/Show.tsx` | Student | View report details + download PDF |

### 4.8. Policy

`GradeReportPolicy`:

| Method | Logic |
|--------|-------|
| `viewAny` | Admin: all. Teacher: own class-subjects. Student: own published reports. |
| `view` | Admin: all. Teacher: report contains their class-subject. Student: own + published. |
| `create` | Admin only |
| `updateRemarks` | Teacher (own class-subject remarks only) |
| `updateGeneralRemark` | Admin only |
| `validate` | Admin only |
| `publish` | Admin only |
| `download` | Same as `view` |

---

## 5. Data Export

### 5.1. Package

[`Maatwebsite/Laravel-Excel`](https://github.com/SpartnerNL/Laravel-Excel) - Eloquent-native export with queue support.

### 5.2. Export Classes

```
app/Exports/
    ClassStudentsExport.php           -- Student list for a class
    AssessmentResultsExport.php       -- Results for a single assessment
    ClassResultsExport.php            -- All subject results for a class
    EnrollmentsExport.php             -- Enrollment list (admin)
```

Each export implements `FromCollection`, `WithHeadings`, `WithMapping`, `ShouldAutoSize`.

Exports with > 100 rows implement `ShouldQueue` for background processing.

### 5.3. Supported Formats

- Excel (`.xlsx`) - default
- CSV (`.csv`) - via query param `?format=csv`

### 5.4. Export Endpoints

```
GET /admin/exports/class-students/{class}            ClassStudentsExport
GET /admin/exports/enrollments                        EnrollmentsExport
GET /admin/exports/class-results/{class}              ClassResultsExport
GET /teacher/exports/assessment-results/{assessment}  AssessmentResultsExport
GET /teacher/exports/class-results/{class}            ClassResultsExport (scoped to teacher)
```

### 5.5. Security

- Exports respect existing Policies (same authorization as viewing data)
- Teachers can only export results for their own class-subjects
- Admins can export everything

### 5.6. Frontend Integration

- Export buttons added to existing pages (DataTable actions, page headers)
- Format selector dropdown (Excel / CSV)
- Loading state during generation
- Download triggered via `window.location.href` to the export endpoint

---

## 6. Database Migrations

### 6.1. Settings Migrations

Handled by `spatie/laravel-settings` migration system:

```php
// database/settings/2026_03_XX_create_general_settings.php
return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('general.school_name', 'Evalium');
        $this->migrator->add('general.logo_path', null);
        $this->migrator->add('general.default_locale', 'fr');
    }
};

// database/settings/2026_03_XX_create_bulletin_settings.php
return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('bulletin.show_ranking', true);
        $this->migrator->add('bulletin.show_class_average', true);
        $this->migrator->add('bulletin.show_min_max', false);
    }
};
```

### 6.2. Grade Reports Table

```php
Schema::create('grade_reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
    $table->foreignId('semester_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
    $table->json('data');
    $table->json('remarks')->nullable();
    $table->text('general_remark')->nullable();
    $table->unsignedSmallInteger('rank')->nullable();
    $table->decimal('average', 5, 2)->nullable();
    $table->enum('status', ['draft', 'validated', 'published'])->default('draft');
    $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('validated_at')->nullable();
    $table->string('file_path')->nullable();
    $table->timestamps();

    $table->unique(['enrollment_id', 'semester_id', 'academic_year_id']);
});
```

### 6.3. New Enum

```php
// app/Enums/GradeReportStatus.php
enum GradeReportStatus: string
{
    case Draft = 'draft';
    case Validated = 'validated';
    case Published = 'published';
}
```

### 6.4. New Model

```php
// app/Models/GradeReport.php
class GradeReport extends Model
{
    protected $fillable = [
        'enrollment_id', 'semester_id', 'academic_year_id',
        'data', 'remarks', 'general_remark', 'rank', 'average',
        'status', 'validated_by', 'validated_at', 'file_path',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'remarks' => 'array',
            'average' => 'decimal:2',
            'status' => GradeReportStatus::class,
            'validated_at' => 'datetime',
        ];
    }

    // Relationships: enrollment, semester, academicYear, validator (User)
}
```

---

## 7. Implementation Plan

### Phase 1: Foundations (~4.5 days)

| # | Task | Effort | Dependencies |
|---|------|--------|--------------|
| 1.1 | Install `spatie/laravel-settings`, create settings classes + migrations | 1d | - |
| 1.2 | Add `manage settings` permission via seeder | 0.5d | 1.1 |
| 1.3 | Add `calculateSemesterGrade()` and `calculateSemesterAverage()` to `GradeCalculationService` | 1d | - |
| 1.4 | Add `calculateClassRanking()` to `GradeCalculationService` | 0.5d | 1.3 |
| 1.5 | Create `grade_reports` migration + `GradeReport` model + `GradeReportStatus` enum | 0.5d | - |
| 1.6 | Unit tests for new calculation methods + ranking | 1d | 1.3, 1.4 |

### Phase 2: Grade Reports PDF (~9.5 days)

| # | Task | Effort | Dependencies |
|---|------|--------|--------------|
| 2.1 | Install `barryvdh/laravel-dompdf` | 0.5d | - |
| 2.2 | Create Blade PDF template (`grade-report.blade.php`) | 2d | 2.1 |
| 2.3 | Implement `GradeReportService` + `RemarkGeneratorService` | 1.5d | Phase 1 |
| 2.4 | Create `GradeReportPolicy` | 0.5d | 2.3 |
| 2.5 | Create controllers + routes (Admin, Teacher, Student) | 0.5d | 2.3, 2.4 |
| 2.6 | Admin UI: grade report generation + validation pages | 2d | 2.5 |
| 2.7 | Teacher UI: remarks editing page | 1.5d | 2.5 |
| 2.8 | Student UI: view + download published reports | 0.5d | 2.5 |
| 2.9 | Settings admin page (General + Bulletin settings) | 1d | 1.1, 1.2 |
| 2.10 | Feature tests (service + controllers) | 1.5d | 2.3-2.8 |

### Phase 3: Data Export (~5 days)

| # | Task | Effort | Dependencies |
|---|------|--------|--------------|
| 3.1 | Install `Maatwebsite/Laravel-Excel` | 0.5d | - |
| 3.2 | Create `ClassStudentsExport` | 0.5d | 3.1 |
| 3.3 | Create `AssessmentResultsExport` | 1d | 3.1 |
| 3.4 | Create `ClassResultsExport` | 1d | 3.1 |
| 3.5 | Export controller + routes | 0.5d | 3.2-3.4 |
| 3.6 | Add export buttons to existing React pages | 1d | 3.5 |
| 3.7 | Feature tests for exports | 1d | 3.2-3.5 |

### Total: ~19 days

### Suggested Branch Strategy

```
feat/grade-reports-exports
  feat/app-settings           (Phase 1.1-1.2, 2.9)
  feat/semester-grading       (Phase 1.3-1.4, 1.6)
  feat/grade-reports          (Phase 1.5, 2.1-2.8, 2.10)
  feat/data-exports           (Phase 3.1-3.7)
```

---

## 8. Package Dependencies

### New Composer Packages

| Package | Version | Purpose |
|---------|---------|---------|
| `spatie/laravel-settings` | `^3.0` | Application settings system |
| `barryvdh/laravel-dompdf` | `^3.0` | PDF generation for grade reports |
| `maatwebsite/excel` | `^3.1` | Excel/CSV data export |

### New NPM Packages

None required. Export/PDF are server-side only.

### Translations

New keys required in `lang/en/messages.php` and `lang/fr/messages.php`:
- Grade report related: `grade_report_generated`, `grade_report_validated`, `grade_report_published`, etc.
- Settings related: `settings_updated`, `logo_uploaded`, etc.
- Export related: `export_started`, `export_ready`, etc.
- Remarks: `remark_excellent`, `remark_good`, `remark_fairly_good`, `remark_satisfactory`, `remark_insufficient`
