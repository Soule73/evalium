# RESTful API v1

> **Status**: Approved - Ready for implementation
> **Date**: March 2, 2026
> **Feature Branch**: `feat/api-v1` (to create after `feat/grade-reports-exports`)

---

## Table of Contents

1. [Overview](#1-overview)
2. [Authentication](#2-authentication)
3. [Architecture](#3-architecture)
4. [Endpoints](#4-endpoints)
5. [Resources](#5-resources)
6. [Rate Limiting](#6-rate-limiting)
7. [Pagination & Filtering](#7-pagination--filtering)
8. [Error Handling](#8-error-handling)
9. [Documentation](#9-documentation)
10. [Implementation Plan](#10-implementation-plan)
11. [Package Dependencies](#11-package-dependencies)

---

## 1. Overview

RESTful API v1 for Evalium, enabling mobile applications and third-party integrations.

### Scope

| Consumer | Auth | Description |
|----------|------|-------------|
| Evalium mobile app (first-party) | Sanctum token | Full access matching web permissions |
| Admin scripts | Sanctum token | Automation and batch operations |
| Third-party integrations | Deferred to v2 (OAuth) | LMS, external school systems |

### Design Principles

- **Reuse existing logic**: API controllers delegate to the same Services and Policies as web controllers
- **No business logic duplication**: Services, Strategies, and Form Request rules are shared
- **Version-prefixed**: All endpoints under `/api/v1/`
- **JSON only**: `Accept: application/json` required

### Existing Code Reuse

| Component | Reusable | Notes |
|-----------|----------|-------|
| Services (Admin, Teacher, Student, Core) | Yes | API controllers delegate to same services |
| Policies (9 policies) | Yes | `$this->authorize()` works with Sanctum |
| Form Requests (validation rules) | Partially | Validation rules reusable, some Inertia-specific requests need adaptation |
| Strategies (validation, scoring) | Yes | No changes needed |
| Traits (`FiltersAcademicYear`, etc.) | Yes | Work unchanged |

---

## 2. Authentication

### Package

`laravel/sanctum` - Official Laravel package for API token authentication.

### Configuration

```php
// config/auth.php
'guards' => [
    'web' => ['driver' => 'session', 'provider' => 'users'],
    'sanctum' => ['driver' => 'sanctum', 'provider' => 'users'],
],
```

### Token Strategy

- Tokens created via `/api/v1/auth/login`
- No abilities/scopes on tokens - authorization handled entirely by Spatie Permission + Policies
- Token revocation on logout
- Optional token expiration (configurable)

### Auth Endpoints

```
POST /api/v1/auth/login
  Body: { "email": "user@example.com", "password": "secret" }
  Response 200: {
    "data": {
      "token": "1|abc123...",
      "user": { UserResource },
      "expires_at": "2026-04-02T00:00:00Z"
    }
  }

POST /api/v1/auth/logout
  Header: Authorization: Bearer 1|abc123...
  Response: 204 No Content

GET /api/v1/auth/me
  Header: Authorization: Bearer 1|abc123...
  Response 200: {
    "data": {
      "user": { UserResource },
      "roles": ["teacher"],
      "permissions": ["create assessments", "grade assessments", ...]
    }
  }
```

---

## 3. Architecture

### File Structure

```
routes/
    api.php                          -- Entry point, loads version files
    api/
        v1.php                       -- All v1 routes

app/Http/Controllers/
    Api/
        V1/
            AuthController.php
            AcademicYearController.php
            LevelController.php
            SubjectController.php
            NotificationController.php
            Admin/
                UserController.php
                ClassController.php
                EnrollmentController.php
                ClassSubjectController.php
                RoleController.php
                DashboardController.php
            Teacher/
                AssessmentController.php
                QuestionController.php
                AssignmentController.php
                ClassController.php
                DashboardController.php
            Student/
                AssessmentController.php
                EnrollmentController.php
                DashboardController.php

app/Http/Resources/
    V1/
        UserResource.php
        AcademicYearResource.php
        SemesterResource.php
        LevelResource.php
        ClassResource.php
        SubjectResource.php
        ClassSubjectResource.php
        EnrollmentResource.php
        AssessmentResource.php
        QuestionResource.php
        ChoiceResource.php
        AssignmentResource.php
        AnswerResource.php
        NotificationResource.php
```

### Routing Configuration

```php
// bootstrap/app.php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    health: '/up',
    apiPrefix: 'api/v1',
)

// routes/api.php
Route::prefix('v1')->group(base_path('routes/api/v1.php'));

// routes/api/v1.php
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:api-auth');
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    // General resources, admin, teacher, student routes...
});
```

### API Controller Pattern

```php
class AssessmentController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly AssessmentService $assessmentService
    ) {}

    /**
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Assessment::class);
        $assessments = $this->assessmentService->getTeacherAssessments($request->user());
        return AssessmentResource::collection($assessments);
    }

    /**
     * @return AssessmentResource
     */
    public function store(StoreAssessmentRequest $request): AssessmentResource
    {
        $this->authorize('create', Assessment::class);
        $assessment = $this->assessmentService->createAssessment($request->validated());
        return new AssessmentResource($assessment);
    }
}
```

---

## 4. Endpoints

### Conventions

| Convention | Value |
|------------|-------|
| URL naming | `kebab-case` (`/class-subjects`) |
| Resource naming | Plural (`/assessments`, `/users`) |
| Create response | `201 Created` + resource |
| Delete response | `204 No Content` |
| Auth header | `Authorization: Bearer {token}` |

### 4.1. Public Endpoints

```
POST   /api/v1/auth/login                                 Login, returns token
```

### 4.2. General Authenticated Endpoints

```
# Auth
POST   /api/v1/auth/logout                                Revoke current token
GET    /api/v1/auth/me                                     Current user + roles + permissions

# Academic Years
GET    /api/v1/academic-years                              List academic years
GET    /api/v1/academic-years/{id}                         Academic year detail
GET    /api/v1/academic-years/{id}/semesters               Semesters for an academic year

# Levels
GET    /api/v1/levels                                      List levels
GET    /api/v1/levels/{id}                                 Level detail

# Subjects
GET    /api/v1/subjects                                    List subjects
GET    /api/v1/subjects/{id}                               Subject detail
POST   /api/v1/subjects                                    Create (admin)
PUT    /api/v1/subjects/{id}                               Update (admin)
DELETE /api/v1/subjects/{id}                               Delete (admin)

# Notifications
GET    /api/v1/notifications                               User notifications
PUT    /api/v1/notifications/{id}/read                     Mark as read
POST   /api/v1/notifications/read-all                      Mark all as read
```

### 4.3. Admin Endpoints (`/api/v1/admin/`)

```
# Users
GET    /api/v1/admin/users                                 List users
GET    /api/v1/admin/users/{id}                            User detail
POST   /api/v1/admin/users                                 Create user
PUT    /api/v1/admin/users/{id}                            Update user
DELETE /api/v1/admin/users/{id}                            Soft delete user

# Classes
GET    /api/v1/admin/classes                               List classes
GET    /api/v1/admin/classes/{id}                          Class detail
POST   /api/v1/admin/classes                               Create class
PUT    /api/v1/admin/classes/{id}                          Update class
DELETE /api/v1/admin/classes/{id}                          Delete class

# Enrollments
GET    /api/v1/admin/enrollments                           List enrollments
POST   /api/v1/admin/enrollments                           Enroll student
PUT    /api/v1/admin/enrollments/{id}                      Update status
DELETE /api/v1/admin/enrollments/{id}                      Unenroll

# Class Subjects
GET    /api/v1/admin/class-subjects                        List assignments
POST   /api/v1/admin/class-subjects                        Assign teacher-subject-class
PUT    /api/v1/admin/class-subjects/{id}                   Update assignment
DELETE /api/v1/admin/class-subjects/{id}                   Remove assignment

# Roles & Permissions
GET    /api/v1/admin/roles                                 List roles
GET    /api/v1/admin/roles/{id}                            Role detail + permissions

# Dashboard
GET    /api/v1/admin/dashboard                             Admin dashboard stats
```

### 4.4. Teacher Endpoints (`/api/v1/teacher/`)

```
# Assessments
GET    /api/v1/teacher/assessments                         Teacher's assessments
GET    /api/v1/teacher/assessments/{id}                    Assessment detail + questions
POST   /api/v1/teacher/assessments                         Create assessment
PUT    /api/v1/teacher/assessments/{id}                    Update assessment
DELETE /api/v1/teacher/assessments/{id}                    Soft delete assessment
POST   /api/v1/teacher/assessments/{id}/publish            Publish
POST   /api/v1/teacher/assessments/{id}/unpublish          Unpublish
POST   /api/v1/teacher/assessments/{id}/duplicate          Duplicate

# Questions (sub-resource of assessment)
GET    /api/v1/teacher/assessments/{id}/questions           List questions
POST   /api/v1/teacher/assessments/{id}/questions           Create question
PUT    /api/v1/teacher/assessments/{id}/questions/{qId}     Update question
DELETE /api/v1/teacher/assessments/{id}/questions/{qId}     Delete question
PUT    /api/v1/teacher/assessments/{id}/questions/reorder   Reorder questions

# Grading
GET    /api/v1/teacher/assessments/{id}/assignments         List submissions
PUT    /api/v1/teacher/assignments/{id}/grade                Grade a submission

# Classes & Results
GET    /api/v1/teacher/classes                               Teacher's classes
GET    /api/v1/teacher/classes/{id}/results                  Class results
GET    /api/v1/teacher/classes/{id}/students                 Class students

# Dashboard
GET    /api/v1/teacher/dashboard                             Teacher dashboard stats
```

### 4.5. Student Endpoints (`/api/v1/student/`)

```
# Assessments
GET    /api/v1/student/assessments                           Student's assessments
GET    /api/v1/student/assessments/{id}                      Assessment detail
POST   /api/v1/student/assessments/{id}/start                Start session
POST   /api/v1/student/assessments/{id}/save                 Save answers
POST   /api/v1/student/assessments/{id}/submit               Submit assessment
GET    /api/v1/student/assessments/{id}/result               View results

# Enrollment
GET    /api/v1/student/enrollment                            Current enrollment
GET    /api/v1/student/enrollment/history                    Enrollment history
GET    /api/v1/student/enrollment/classmates                 Classmates

# Dashboard
GET    /api/v1/student/dashboard                             Student dashboard stats
```

### 4.6. Endpoint Summary

| Group | Count | Priority |
|-------|-------|----------|
| Auth | 3 | P0 |
| Academic Years + Levels | 5 | P0 |
| Subjects | 5 | P1 |
| Admin | 18 | P1 |
| Teacher | 16 | P0 |
| Student | 10 | P0 |
| Notifications | 3 | P1 |
| **Total** | **~60** | |

---

## 5. Resources

### Resource Classes

| Resource | Exposed Fields | Notes |
|----------|---------------|-------|
| `UserResource` | `id, name, email, roles, created_at` | Excludes `password`, `remember_token` |
| `AcademicYearResource` | `id, name, start_date, end_date, is_current, semesters_count` | |
| `SemesterResource` | `id, name, order_number, academic_year_id` | |
| `LevelResource` | `id, name, description` | |
| `ClassResource` | `id, name, level, academic_year, students_count` | Nested level and academic_year |
| `SubjectResource` | `id, name, description, level` | |
| `ClassSubjectResource` | `id, class, subject, teacher, semester, coefficient` | Nested relations |
| `EnrollmentResource` | `id, student, class, status, enrolled_at` | |
| `AssessmentResource` | `id, title, description, type, delivery_mode, coefficient, duration, questions_count, max_points, is_published, published_at` | |
| `QuestionResource` | `id, content, type, points, order, choices` | Includes choices |
| `ChoiceResource` | `id, content, is_correct` | `is_correct` visible only for teachers |
| `AssignmentResource` | `id, assessment, student, status, score, started_at, submitted_at, graded_at` | |
| `AnswerResource` | `id, question_id, content, choice_id, score, file_url` | |
| `NotificationResource` | `id, type, data, read_at, created_at` | |

Resources are **API-only** (`app/Http/Resources/V1/`), separate from Inertia data serialization.

---

## 6. Rate Limiting

### Limits

| Scope | Limit | Key |
|-------|-------|-----|
| Login (`api-auth`) | 5 / minute | IP |
| Authenticated endpoints (`api`) | 60 / minute | User ID |
| Heavy endpoints (exports, stats) | 10 / minute | User ID |

### Configuration

```php
// AppServiceProvider::boot()
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('api-auth', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

RateLimiter::for('api-heavy', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
});
```

### Response Headers

Automatically included by Laravel's `throttle` middleware:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 57
Retry-After: 30  (on 429 responses)
```

---

## 7. Pagination & Filtering

### Pagination

Offset-based (page) pagination:

```
GET /api/v1/admin/users?page=2&per_page=25
```

Response format (native Laravel paginator):

```json
{
  "data": [...],
  "meta": {
    "current_page": 2,
    "per_page": 25,
    "total": 48,
    "last_page": 2
  },
  "links": {
    "first": "/api/v1/admin/users?page=1",
    "last": "/api/v1/admin/users?page=2",
    "prev": "/api/v1/admin/users?page=1",
    "next": null
  }
}
```

Maximum `per_page`: 100. Default: 15.

### Filtering & Sorting

Package: [`spatie/laravel-query-builder`](https://github.com/spatie/laravel-query-builder)

| Parameter | Format | Example |
|-----------|--------|---------|
| Filter | `?filter[field]=value` | `?filter[status]=active` |
| Sort | `?sort=field` (asc) / `?sort=-field` (desc) | `?sort=-created_at` |
| Include relations | `?include=relation1,relation2` | `?include=questions,teacher` |
| Search | `?search=term` | `?search=math` |

```php
// Example in controller
$users = QueryBuilder::for(User::class)
    ->allowedFilters(['name', 'email', 'role'])
    ->allowedSorts(['name', 'email', 'created_at'])
    ->allowedIncludes(['roles'])
    ->paginate($request->input('per_page', 15));
```

---

## 8. Error Handling

### Validation Errors (422)

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "name": ["The name must be at least 3 characters."]
  }
}
```

### General Errors

```json
{
  "message": "Assessment not found.",
  "error": "not_found",
  "status": 404
}
```

### HTTP Status Codes

| Code | Usage |
|------|-------|
| `200` | Success (GET, PUT, PATCH) |
| `201` | Created (POST) |
| `204` | No content (DELETE, logout) |
| `400` | Bad request |
| `401` | Unauthenticated |
| `403` | Unauthorized (permissions) |
| `404` | Not found |
| `422` | Validation error |
| `429` | Rate limited |
| `500` | Server error |

Laravel automatically renders exceptions as JSON when requests include `Accept: application/json`, which the `api` middleware stack enforces.

---

## 9. Documentation

### Package

[`dedoc/scramble`](https://github.com/dedoc/scramble) - Auto-generates OpenAPI 3.1 specification from Laravel code (Form Requests, Resources, routes).

### Features

- Zero-maintenance documentation: generated from actual code
- Swagger UI at `/docs/api`
- OpenAPI JSON export at `/docs/api.json` (importable into Postman/Insomnia)
- Automatically documents: parameters, request bodies, response schemas, auth requirements

### Access Control

Documentation endpoint restricted to authenticated admins in production:

```php
// config/scramble.php
'middleware' => ['web', 'auth', 'role:admin,super_admin'],
```

---

## 10. Implementation Plan

### Phase 1: Infrastructure (~3.5 days)

| # | Task | Effort | Dependencies |
|---|------|--------|--------------|
| 1.1 | Install `laravel/sanctum`, configure guard, run migration | 0.5d | - |
| 1.2 | Create `routes/api.php` + `routes/api/v1.php` with middleware stack | 0.5d | 1.1 |
| 1.3 | Configure rate limiters (`api`, `api-auth`, `api-heavy`) | 0.5d | 1.2 |
| 1.4 | Create base API controller with response helpers | 0.5d | - |
| 1.5 | Implement `AuthController` (login, logout, me) + tests | 1d | 1.1, 1.2 |
| 1.6 | Install `dedoc/scramble`, configure documentation route | 0.5d | 1.2 |

### Phase 2: Resources + Core Endpoints (~9.5 days)

| # | Task | Effort | Dependencies |
|---|------|--------|--------------|
| 2.1 | Create all 14 Resource classes | 2d | - |
| 2.2 | Install `spatie/laravel-query-builder` | 0.5d | - |
| 2.3 | Student endpoints (10): assessments, enrollment, dashboard | 2d | 2.1, 2.2 |
| 2.4 | Teacher endpoints (16): assessments, questions, grading, classes, dashboard | 3d | 2.1, 2.2 |
| 2.5 | Feature tests for Phase 2 endpoints | 2d | 2.3, 2.4 |

### Phase 3: Admin Endpoints + Finalization (~7.5 days)

| # | Task | Effort | Dependencies |
|---|------|--------|--------------|
| 3.1 | Admin endpoints (18): users, classes, enrollments, class-subjects, roles, dashboard | 3d | 2.1 |
| 3.2 | General endpoints: academic-years, levels, subjects, notifications | 1.5d | 2.1 |
| 3.3 | Feature tests for Phase 3 endpoints | 2d | 3.1, 3.2 |
| 3.4 | Final documentation review + Postman collection export | 1d | All |

### Total: ~20.5 days

---

## 11. Package Dependencies

### New Composer Packages

| Package | Version | Purpose |
|---------|---------|---------|
| `laravel/sanctum` | `^4.0` | API token authentication |
| `spatie/laravel-query-builder` | `^6.0` | Filtering, sorting, includes |
| `dedoc/scramble` | `^0.12` | Auto-generated OpenAPI documentation |

### New NPM Packages

None required. API is backend-only.

### Translations

New keys required in `lang/en/messages.php` and `lang/fr/messages.php`:
- Auth: `api_login_success`, `api_token_revoked`, `api_unauthenticated`
- Errors: `api_rate_limited`, `api_forbidden`, `api_not_found`
