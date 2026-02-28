# Charts & Dashboards Overhaul - Discussion Document

## Table of Contents

1. [Current State Analysis](#1-current-state-analysis)
2. [Charting Library Comparison](#2-charting-library-comparison)
3. [Dashboard Redesign Plans](#3-dashboard-redesign-plans)
4. [Other Pages Needing Charts](#4-other-pages-needing-charts)
5. [Backend Changes Required](#5-backend-changes-required)
6. [Implementation Plan](#6-implementation-plan)

---

## 1. Current State Analysis

### What Exists Today

| Page | Current Content | Problem |
|------|----------------|---------|
| **Dashboard Admin** | 3 stat cards (total users, students, teachers) | Almost empty - no insights, no trends, no performance data |
| **Dashboard Teacher** | 4 stat cards + 2 lists (active assignments, recent assessments) | No visual analytics, no class performance overview |
| **Dashboard Student** | 4 stat cards + 1 list (assigned assessments) | No grade evolution, no subject breakdown chart |
| **Teacher/Classes/Results** | 4 stat cards + 2 DataTables (assessment stats, student stats) | Data is tabular only - no visual distribution or trends |
| **Assessments/Show** | 4-8 stat cards (questions, points, duration, assignment status) | No completion progress visualization, no score distribution |
| **Student/Assessments/Result** | 7 stat cards (score, percentage, status, date, etc.) | No comparison with class average, no benchmark |
| **Student/Enrollment/Show** | Subject grade list + overall stats | No radar/spider chart for subject performance |
| **Classes/Show** | 4-5 stat cards (level, year, students, subjects) | No enrollment fill visualization, no performance summary |

### Current Stats Components

- `Stat.Item` / `Stat.Group` : Text-only cards with icon, title, value
- `AssessmentStatsTable` : DataTable for assessment completion per class
- `StudentStatsTable` : DataTable for student scores per class
- `AssignmentScoreStats` : Score summary for grading pages

### Charting Libraries Installed : **NONE**

### Key Backend Services Already Available

| Service | What It Can Provide |
|---------|--------------------|
| `AdminDashboardService` | User counts by role, classes count, enrollments, class-subjects (year-scoped) |
| `TeacherDashboardService` | Teacher's classes/subjects/assessments counts, active assignments, recent assessments |
| `StudentDashboardService` | Total/completed/pending assessments, average score (delegates to GradeCalculationService) |
| `AssessmentStatsService` | Per-assessment: total_assigned, graded, submitted, in_progress, not_started, average_score, completion_rate |
| `GradeCalculationService` | Subject grade /20, annual average /20, grade breakdown by subject, class average per subject, student assessment summary with normalized grades |

---

## 2. Charting Library Comparison

### Candidates

| Library | Bundle Size (gzip) | React Support | Chart Types | Customization | Learning Curve | Maintained |
|---------|-------------------|---------------|-------------|---------------|----------------|-----------|
| **Recharts** | ~45 KB | Native (React components) | Bar, Line, Area, Pie, Radar, Scatter, Funnel, Treemap | High (composable) | Low | Active (22k+ stars) |
| **Chart.js + react-chartjs-2** | ~35 KB + 5 KB | Wrapper | Bar, Line, Pie, Doughnut, Radar, Polar, Bubble, Scatter | Medium (config object) | Low-Medium | Very active (65k+ stars) |
| **Nivo** | ~70-150 KB (varies) | Native (React+D3) | Bar, Line, Pie, Radar, Heatmap, Sankey, Sunburst, etc. | Very High (themes) | Medium | Active |
| **ApexCharts** | ~130 KB | Wrapper | All standard + Candlestick, Heatmap, Treemap | High (config) | Low | Active |
| **Tremor** | ~100 KB+ | Native (React+TailwindCSS) | Bar, Line, Area, Donut, built-in dashboard components | Medium (opinionated) | Low | Active (Tailwind-first) |
| **Victory** | ~60 KB | Native (React components) | Standard + custom | High | Medium | Active |

### Recommendation

| Criteria | Weight | Recharts | Chart.js | Nivo | Tremor |
|----------|--------|----------|----------|------|--------|
| React 19 compatibility | CRITICAL | YES | YES | YES | YES |
| Bundle size | HIGH | good | best | poor | poor |
| Composable React API | HIGH | best | poor | good | good |
| TailwindCSS integration | HIGH | good | poor | good | best |
| Chart variety needed | MEDIUM | good | good | best | limited |
| SSR/Inertia compatibility | HIGH | good | good | good | good |
| Community/docs | MEDIUM | good | best | good | good |
| Accessibility (a11y) | MEDIUM | basic | basic | good | good |

### DECISION NEEDED: Choose one

**Option A - Recharts** (RECOMMENDED)
- Most popular React-native charting library
- Composable API (each chart element is a React component)
- Lightweight, good tree-shaking
- Simple learning curve, extensive docs
- Works perfectly with React 19 + TypeScript
- Easy to style with Tailwind via inline styles/classNames

**Option B - Tremor**
- Built specifically for dashboard UIs with TailwindCSS
- Comes with pre-built dashboard card/chart combos
- More opinionated = faster development
- Risk: adds a design system on top of existing components

**Option C - Chart.js + react-chartjs-2**
- Smallest bundle, most mature
- Config-based API (less "React-like", imperative)
- Canvas-based (better perf for large datasets, but less flexible styling)

**Option D - Nivo**
- Most chart variety, best accessibility
- Heavier bundle, more complex API
- Overkill for our needs

---

## 3. Dashboard Redesign Plans

### 3.1 Admin Dashboard (Currently: 3 stat cards)

**Proposed Layout:**

```
+---------------------------------------------------+
| HEADER: Admin Dashboard                           |
+---------------------------------------------------+
| [Stat Cards Row - 5 cards]                        |
| Users | Students | Teachers | Classes | Assessments|
+---------------------------------------------------+
| [LEFT 60%]              | [RIGHT 40%]             |
| Users by Role            | Enrollments vs Capacity |
| (Donut/Pie Chart)        | (Bar Chart)             |
|                          |                         |
+---------------------------------------------------+
| [LEFT 50%]              | [RIGHT 50%]             |
| Classes by Level         | Recent Activity         |
| (Horizontal Bar Chart)   | (Timeline/List)         |
+---------------------------------------------------+
```

**New Stats Needed (backend):**
- [ ] Users by role breakdown (pie data) - exists via `AdminDashboardService`
- [ ] Classes by level (bar data) - NEW query needed
- [ ] Enrollment fill rates per class (bar data) - NEW query needed
- [ ] Total assessments count (all, published, draft) - NEW query needed
- [ ] Recent activity (recent users created, assessments published) - NEW query needed

---

### 3.2 Teacher Dashboard (Currently: 4 stat cards + 2 lists)

**Proposed Layout:**

```
+---------------------------------------------------+
| HEADER: Teacher Dashboard                         |
+---------------------------------------------------+
| [Stat Cards Row - 4 cards]                        |
| Classes | Subjects | Assessments | Avg Score       |
+---------------------------------------------------+
| [LEFT 60%]              | [RIGHT 40%]             |
| Assessment Completion    | Score Distribution       |
| Overview                 | (Bar Chart: histogram)   |
| (Stacked Bar Chart:      |                         |
|  graded/submitted/       |                         |
|  in_progress/not_started)|                         |
+---------------------------------------------------+
| [LEFT 50%]              | [RIGHT 50%]             |
| Class Performance        | Recent Assessments       |
| (Bar Chart: avg by class)| (List - existing)        |
+---------------------------------------------------+
| [FULL WIDTH]                                       |
| Active Assignments (List - existing)               |
+---------------------------------------------------+
```

**New Stats Needed (backend):**
- [ ] Overall average score across all teacher's assessments - NEW
- [ ] Assessment completion overview (aggregate graded/submitted/in_progress/not_started across all assessments) - NEW
- [ ] Score distribution histogram (score ranges: 0-4, 5-8, 9-12, 13-16, 17-20) - NEW
- [ ] Average score per class (bar chart data) - NEW
- [ ] Per-assessment completion status (stacked bar data) - partially exists via `AssessmentStatsService`

---

### 3.3 Student Dashboard (Currently: 4 stat cards + 1 list)

**Proposed Layout:**

```
+---------------------------------------------------+
| HEADER: Welcome back, {name}                      |
+---------------------------------------------------+
| [Stat Cards Row - 4 cards]                        |
| Total | Completed | Pending | Average /20          |
+---------------------------------------------------+
| [LEFT 60%]              | [RIGHT 40%]             |
| Subject Grades Overview  | Assessment Status        |
| (Radar/Spider Chart      | (Donut Chart:            |
|  by subject /20)         |  completed/pending/      |
|                          |  not_started)            |
+---------------------------------------------------+
| [LEFT 60%]              | [RIGHT 40%]             |
| Recent Scores            | Grade Trend              |
| (Bar Chart: last 5-10    | (Line Chart: score       |
|  assessments with score) |  evolution over time)    |
+---------------------------------------------------+
| [FULL WIDTH]                                       |
| Upcoming/Assigned Assessments (List - existing)    |
+---------------------------------------------------+
```

**New Stats Needed (backend):**
- [ ] Subject grades for radar chart - exists via `GradeCalculationService::getGradeBreakdown`
- [ ] Assessment status breakdown (completed/pending/not_started counts) - partially exists
- [ ] Recent scores with dates for line chart - exists via `GradeCalculationService::getStudentAssessmentSummary`
- [ ] Grade trend over time (ordered by submitted_at) - derivable from above

---

## 4. Other Pages Needing Charts

### 4.1 Teacher/Classes/Results (Priority: HIGH)

Currently: 4 stat cards + 2 DataTables

**Add:**
- **Score Distribution Histogram** (bar chart): How many students in each score range (0-4, 5-8, 9-12, 13-16, 17-20)
- **Assessment Completion Chart** (stacked horizontal bar): Per assessment - graded/submitted/in_progress/not_started
- **Class Average Trend** (line chart): Average score per assessment over time (by scheduled_at)

**Backend needed:**
- [ ] Score distribution buckets for a class - NEW
- [ ] Assessment averages over time for a class - NEW

---

### 4.2 Assessments/Show (Priority: MEDIUM)

Currently: stat cards for assignment status

**Add:**
- **Assignment Status Donut** (donut chart): graded/submitted/in_progress/not_started - data already exists via `AssessmentStatsService`
- **Score Distribution** (bar chart): histogram of student scores for this assessment - NEW

**Backend needed:**
- [ ] Score distribution for a single assessment - NEW

---

### 4.3 Student/Enrollment/Show (Priority: MEDIUM)

Currently: subject grade list with text values

**Add:**
- **Subject Performance Radar** (radar chart): Subject grades /20 in spider web format - data exists via `GradeCalculationService::getGradeBreakdown`
- **Subject Comparison Bar** (horizontal bar chart): Grade per subject with class average comparison

**Backend needed:**
- [ ] Class average per subject for comparison - exists via `GradeCalculationService::calculateClassAverageForSubject`

---

### 4.4 Classes/Show - Admin view (Priority: LOW)

Currently: stat cards

**Add:**
- **Enrollment Fill Gauge** (progress bar or gauge): active_students / max_students percentage
- *Keep DataTables for subjects and assessments as-is*

**Backend needed:** None (data already exists in `ClassStatistics`)

---

### 4.5 Admin/ClassSubjects/Show (Priority: LOW)

**Add if relevant:**
- **Student Grades Bar Chart**: Scores per student in this class-subject
- **Assessment Average Comparison**: Average per assessment

---

## 5. Backend Changes Required

### 5.1 New Service: `DashboardChartService` (or extend existing services)

#### For Admin Dashboard

```php
// AdminDashboardService - extend with:
public function getUsersByRoleChart(?int $academicYearId): array
// Returns: [['role' => 'student', 'count' => 120], ['role' => 'teacher', 'count' => 15], ...]

public function getClassesByLevelChart(?int $academicYearId): array
// Returns: [['level' => '1ere Annee', 'count' => 5], ...]

public function getEnrollmentCapacityChart(?int $academicYearId): array
// Returns: [['class' => 'Classe A', 'enrolled' => 25, 'capacity' => 30], ...]

public function getAssessmentCountsByStatus(?int $academicYearId): array
// Returns: ['published' => 45, 'draft' => 12, 'total' => 57]
```

#### For Teacher Dashboard

```php
// TeacherDashboardService - extend with:
public function getOverallAverageScore(int $teacherId, ?int $academicYearId): ?float

public function getAssessmentCompletionOverview(int $teacherId, ?int $academicYearId): array
// Returns: ['graded' => 80, 'submitted' => 15, 'in_progress' => 10, 'not_started' => 45]

public function getScoreDistribution(int $teacherId, ?int $academicYearId): array
// Returns: [['range' => '0-4', 'count' => 5], ['range' => '5-8', 'count' => 12], ...]

public function getClassPerformanceChart(int $teacherId, ?int $academicYearId): array
// Returns: [['class' => 'Classe A', 'average' => 14.5], ...]
```

#### For Student Dashboard

```php
// StudentDashboardService - extend with:
public function getSubjectRadarData(User $student, ?int $academicYearId): array
// Returns: [['subject' => 'Math', 'grade' => 15.5, 'class_average' => 12.3], ...]

public function getRecentScoresChart(User $student, ?int $academicYearId, int $limit = 10): array
// Returns: [['title' => 'Exam 1', 'score' => 16.5, 'max' => 20, 'date' => '2026-01-15'], ...]

public function getGradeTrend(User $student, ?int $academicYearId): array
// Returns: [['month' => '2025-09', 'average' => 14.2], ['month' => '2025-10', 'average' => 15.1], ...]
```

#### For Results Pages

```php
// AssessmentStatsService - extend with:
public function getScoreDistributionForAssessment(int $assessmentId): array
// Returns: [['range' => '0-4', 'count' => 2], ...]

public function getScoreDistributionForClass(int $classId, ?int $academicYearId): array
// Returns: same format, aggregated across all assessments

public function getClassAverageTrend(int $classId, ?int $academicYearId): array
// Returns: [['assessment' => 'Exam 1', 'average' => 13.5, 'date' => '...'], ...]
```

### 5.2 Deferred Props Strategy

For charts that require heavy queries, use **Inertia v2 Deferred Props** to avoid slowing down initial page load:

```php
// In Controller
return Inertia::render('Dashboard/Teacher', [
    'stats' => $this->dashboardService->getDashboardStats($teacherId, $yearId),
    'activeAssignments' => $this->dashboardService->getActiveAssignments(...),
    // Charts loaded after initial render:
    'chartData' => Inertia::defer(fn () => [
        'completionOverview' => $this->dashboardService->getAssessmentCompletionOverview(...),
        'scoreDistribution' => $this->dashboardService->getScoreDistribution(...),
        'classPerformance' => $this->dashboardService->getClassPerformanceChart(...),
    ]),
]);
```

Frontend skeleton loading while charts load:

```tsx
const { chartData } = usePage().props;
// Show skeleton/pulse animation while chartData is undefined (deferred)
```

---

## 6. Implementation Plan

### Phase 1: Foundation - DONE
- [x] DECISION: Choose charting library -> **Recharts 3.7.0**
- [x] Install chosen library (`yarn add recharts`)
- [x] Create reusable chart wrapper components (`ChartCard`, theme config)
- [x] Create chart color palette matching Evalium brand (indigo-600 primary)
- [x] Create 7 chart components: `ChartCard`, `BarChart`, `LineChart`, `DonutChart`, `RadarChart`, `ScoreDistribution`, `CompletionChart`
- [x] Add i18n translations for chart labels (`lang/en/charts.php`, `lang/fr/charts.php`)
- [x] Migrate from deprecated `Cell` to `fill`-per-data-item pattern (Recharts 4 ready)

### Phase 2: Admin Dashboard (2-3 days)
- [ ] Backend: Extend `AdminDashboardService` with chart data methods
- [ ] Frontend: Redesign `Dashboard/Admin.tsx` with charts
- [ ] Add skeleton loading for deferred chart data
- [ ] Tests: Backend service tests + frontend component tests

### Phase 3: Teacher Dashboard (2-3 days)
- [ ] Backend: Extend `TeacherDashboardService` with chart data methods
- [ ] Frontend: Redesign `Dashboard/Teacher.tsx` with charts
- [ ] Tests

### Phase 4: Student Dashboard (2-3 days)
- [ ] Backend: Extend `StudentDashboardService` with chart data methods
- [ ] Frontend: Redesign `Dashboard/Student.tsx` with charts
- [ ] Tests

### Phase 5: Results & Detail Pages (2-3 days)
- [ ] Backend: Extend `AssessmentStatsService` with distribution methods
- [ ] Frontend: Add charts to `Teacher/Classes/Results.tsx`
- [ ] Frontend: Add donut chart to `Assessments/Show.tsx`
- [ ] Frontend: Add radar chart to `Student/Enrollment/Show.tsx`
- [ ] Tests

### Phase 6: Polish & Landing Page (1-2 days)
- [ ] Fine-tune chart animations, responsiveness, dark mode (if applicable)
- [ ] Take screenshots for landing page v2
- [ ] Record demo video

### Total Estimated: 10-16 days

---

## Decisions (ALL DECIDED)

| # | Decision | Chosen | Status |
|---|----------|--------|--------|
| D1 | Charting library | **Recharts** | DECIDED |
| D2 | Chart color scheme | **Indigo primary with complementary palette** | DECIDED |
| D3 | Deferred props for all charts? | **Yes** - skeleton UX for better perceived performance | DECIDED |
| D4 | Score normalization display | **Always /20** (consistent with grade system) | DECIDED |
| D5 | Score range buckets | **0-4 / 5-8 / 9-12 / 13-16 / 17-20** (French grading culture) | DECIDED |
| D6 | Implementation priority | **Dashboards first** (phases 2-4), then results (phase 5) | DECIDED |
| D7 | Reusable chart components | **Generic wrappers** in `Components/ui/charts/` | DECIDED |

---

## Chart Components to Create

```
resources/ts/Components/ui/charts/
  ChartCard.tsx            -- Card wrapper with title, subtitle, loading state
  BarChart.tsx             -- Reusable bar chart (vertical + horizontal)
  LineChart.tsx            -- Reusable line chart (trend, evolution)
  DonutChart.tsx           -- Reusable donut/pie chart
  RadarChart.tsx           -- Reusable radar/spider chart
  ScoreDistribution.tsx    -- Pre-configured histogram for score ranges
  CompletionChart.tsx      -- Pre-configured stacked bar for assessment status
  index.ts                 -- Barrel export
```

---

## Summary of Charts Per Page

| Page | Chart Type | Data Source | Priority |
|------|-----------|-------------|----------|
| **Admin Dashboard** | Donut (users by role) | AdminDashboardService | HIGH |
| **Admin Dashboard** | Bar (classes by level) | AdminDashboardService (NEW) | HIGH |
| **Admin Dashboard** | Bar (enrollment vs capacity) | AdminDashboardService (NEW) | MEDIUM |
| **Teacher Dashboard** | Stacked Bar (completion overview) | TeacherDashboardService (NEW) | HIGH |
| **Teacher Dashboard** | Bar (score distribution) | TeacherDashboardService (NEW) | HIGH |
| **Teacher Dashboard** | Bar (class performance) | TeacherDashboardService (NEW) | MEDIUM |
| **Student Dashboard** | Radar (subject grades) | GradeCalculationService (exists) | HIGH |
| **Student Dashboard** | Donut (assessment status) | StudentDashboardService (exists) | HIGH |
| **Student Dashboard** | Line (grade trend) | StudentDashboardService (NEW) | MEDIUM |
| **Student Dashboard** | Bar (recent scores) | GradeCalculationService (exists) | MEDIUM |
| **Teacher/Classes/Results** | Bar (score distribution) | AssessmentStatsService (NEW) | HIGH |
| **Teacher/Classes/Results** | Stacked Bar (completion) | AssessmentStatsService (exists) | HIGH |
| **Teacher/Classes/Results** | Line (class avg trend) | AssessmentStatsService (NEW) | MEDIUM |
| **Assessments/Show** | Donut (assignment status) | AssessmentStatsService (exists) | MEDIUM |
| **Assessments/Show** | Bar (score distribution) | AssessmentStatsService (NEW) | LOW |
| **Student/Enrollment/Show** | Radar (subject performance) | GradeCalculationService (exists) | MEDIUM |
