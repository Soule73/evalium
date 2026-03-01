# Evalium - AI Coding Agent Instructions

## Project Overview
Evalium is an online assessment management platform built with **Laravel 12** (PHP 8.2+) backend, **React 19** + **TypeScript** frontend via **Inertia.js 2**. The system manages assessments, academic years, classes, subjects, enrollments, scoring, and user roles (super_admin, admin, teacher, student) with full academic year temporal context.

## Tech Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend | Laravel | 12 |
| PHP | | ^8.2 |
| Frontend | React | ^19.1 |
| TypeScript | | ^5.9 |
| SPA Bridge | Inertia.js | v2 |
| CSS | Tailwind CSS | v4.1 (via `@tailwindcss/vite`) |
| Build | Vite | ^7.0 |
| Permissions | spatie/laravel-permission | ^6.21 |
| State | Zustand | ^5.0 |
| Validation | Zod | ^4.1 |
| Markdown | EasyMDE + react-markdown | |
| Math | KaTeX | ^0.16 |
| Charts | Recharts | ^3.7 |
| Drag & Drop | @dnd-kit | v6/v10 |
| Package manager | Yarn | 1.22 (workspaces) |
| Unit tests (PHP) | PHPUnit | ^11.5 |
| Unit tests (JS) | Vitest | ^4.0 |
| E2E tests | Playwright | ^1.56 |
| Linting | ESLint ^10 + Prettier | |
| PHP style | Laravel Pint | ^1.24 |
| Commit linting | commitlint + husky | |
| Versioning | standard-version | |

## Core Architecture

### Backend: Service-Oriented Architecture
- **Controllers** (`app/Http/Controllers/{Admin,Teacher,Student}/`) are thin - delegate to Services
- **Services** (`app/Services/{Admin,Teacher,Student,Core}/`) contain business logic (SRP)
- **Repositories** (`app/Repositories/{Admin,Teacher,Student}/`) handle complex queries (rare - prefer Eloquent)
- **Policies** (`app/Policies/`) implement authorization via Spatie Permission
- **Form Requests** (`app/Http/Requests/{Admin,Teacher,Student,Auth}/`) validate input
  - Shared validation rules via `Traits/` (e.g., `AssessmentValidationRules`, `UserValidationRules`)
  - Strategy pattern integration for question and score validation
- **Strategies** - Two strategy systems:
  - `app/Strategies/Validation/` - Question validation (multiple choice, single choice, text)
  - `app/Strategies/Validation/Score/` - Score validation (question exists, score not exceeds max)
  - `app/Strategies/Scoring/` - Auto-scoring (boolean, multiple choice, one choice, text, file)
- **Enums** (`app/Enums/`) - `AssessmentType`, `DeliveryMode`, `QuestionType`, `EnrollmentStatus`
- **Exceptions** (`app/Exceptions/`) - Domain exceptions: `AssessmentException`, `EnrollmentException`, etc.
- **Notifications** (`app/Notifications/`) - `AssessmentGraded`, `AssessmentPublished`, `AssessmentStartingSoon`, `AssessmentSubmitted`, `UserCredentials`
- **Traits** (`app/Traits/`) - `FiltersAcademicYear`, `HasAcademicYearScope`, `HasAcademicYearThroughClass`, `HasJsonSettings`, `PaginatesResources`

### Frontend: React + Inertia.js
- **Pages** (`resources/ts/Pages/`) - Inertia entry points organized by role:
  - `Admin/` - AcademicYears, Classes, ClassSubjects, Enrollments, Levels, Roles, Subjects, Teachers, Users
  - `Teacher/` - Assessments, Classes, ClassSubjects
  - `Student/` - Assessments, Enrollment
  - `Auth/` - Login
  - `Dashboard/` - Role-based dashboard
- **Components** organized in 4 layers:
  - `ui/` - Design system (Button, Input, Modal, Badge, Select, Charts, MarkdownEditor, etc.) - separate yarn workspace `@evalium/ui`
  - `layout/` - AuthenticatedLayout, GuestLayout, Sidebar, Breadcrumb, AcademicYearSelector, LanguageSelector
  - `features/` - Domain components (assessment, classes, enrollments, notifications, roles, subjects, users, etc.)
  - `shared/` - DataTable, EmptyState, FilePreviewModal, DeleteHistoryModal, Toast
- **Hooks** (`resources/ts/hooks/`):
  - `shared/` - `useDataTable`, `useFormatters`, `useNotifications`, `useBreadcrumbs`, `useTranslations`
  - `features/` - assessment, exam, levels, shared
  - `forms/` - `useForm` (custom hook)
- **Stores** (`resources/ts/stores/`) - Zustand: `useAssessmentFormStore`, `useAssessmentTakeStore`
- **Contexts** (`resources/ts/contexts/`) - `AcademicYearWizardContext`, `EnrollmentWizardContext`
- **Schemas** (`resources/ts/schemas/`) - Zod: assessment, user
- **Types** (`resources/ts/types/`) - TypeScript interfaces synced with Laravel models:
  - `models/` - academicYear, assessment, assessmentAssignment, class, classSubject, enrollment, grades, notification, semester, subject
  - `role.ts`, `datatable.ts`, `question-rendering.ts`, `route-context.ts`
- **Path aliases**: `@/` -> `resources/ts/`, `@evalium/ui` -> `resources/ts/Components/ui`

### Middleware Stack (registered in `bootstrap/app.php`)
- `RoleMiddleware` - Custom alias `role` for role-based route access
- `SetLocale` - Sets app locale from session
- `EagerLoadPermissions` - Pre-loads Spatie permissions
- `InjectAcademicYear` - Resolves selected academic year from session
- `HandleInertiaRequests` - Shares `auth`, `flash`, `academic_year`, `locale`, `language`, `notifications`

### Permission System: Hybrid Strategy
- **4 roles**: `super_admin`, `admin`, `teacher`, `student`
- **Role "student"** is STRICT - use `hasRole('student')` middleware for student-specific routes (`/student/*`)
- **Other actions** use **permissions** - check via Policies: `$user->can('create', Assessment::class)`
- **9 Policies**: Assessment, AcademicYear, Class, ClassSubject, Enrollment, Level, Role, Subject, User
- Routes use `->middleware('role:student')` for student-only, `->middleware('role:teacher,admin,super_admin')` for teacher routes
- See `documentation/` for full architecture docs

### Academic Year Context & Enrollment Architecture
- **Academic Year Management** - All entities (assessments, classes, subjects) are scoped by academic year
- **Enrollment-Based Access** - Students access assessments through class enrollment (`enrollments` table)
- **Class-Subject Assignments** - Teachers assigned to subjects within classes via `class_subject` pivot
- **Assessment Creation** - Assessments created for specific class-subject combinations
- Academic year selector in UI header, stored in session, injected via `InjectAcademicYear` middleware

## Critical Patterns

### 1. Strategy Pattern for Validation
Two validation contexts exist:

**Question Validation** (`app/Strategies/Validation/`):
```php
use App\Strategies\Validation\QuestionValidationContext;

$validationContext = new QuestionValidationContext();
$validationContext->validateQuestions($validator, $questions);
```
- Strategies: `MultipleChoiceValidationStrategy`, `SingleChoiceValidationStrategy`, `TextQuestionValidationStrategy`
- Each validates specific question type rules (e.g., multiple choice needs >= 2 correct answers)

**Score Validation** (`app/Strategies/Validation/Score/`):
```php
use App\Strategies\Validation\Score\ScoreValidationContext;

$validationContext = new ScoreValidationContext();
$validationContext->validate($validator, $data, ['question_exists_in_assessment', 'score_not_exceeds_max'], ['assessment' => $assessment]);
```
- Strategies: `QuestionExistsInAssessmentValidationStrategy`, `ScoreNotExceedsMaxValidationStrategy`, `SingleQuestionExistsValidationStrategy`, `StudentAssignmentValidationStrategy`
- See `app/Strategies/Validation/Score/README.md`

**Auto-Scoring** (`app/Strategies/Scoring/`):
- `BooleanScoringStrategy`, `MultipleChoiceScoringStrategy`, `OneChoiceScoringStrategy`, `TextQuestionScoringStrategy`, `FileQuestionScoringStrategy`
- All extend `AbstractScoringStrategy`

### 2. Controller Pattern
```php
class AssessmentController extends Controller
{
    use AuthorizesRequests, HasFlashMessages;

    public function __construct(
        private readonly AssessmentService $assessmentService,
        private readonly AssessmentStatsService $assessmentStatsService
    ) {}

    public function store(StoreAssessmentRequest $request): RedirectResponse
    {
        $this->authorize('create', Assessment::class);
        $assessment = $this->assessmentService->createAssessment($request->validated());
        return redirect()->route('teacher.assessments.show', $assessment)->flashSuccess(__('messages.assessment_created'));
    }
}
```
- Inject services via constructor with `readonly`
- Use Policy authorization (`$this->authorize()`) BEFORE service calls
- Flash messages via `HasFlashMessages` trait (`flashSuccess()`, `flashError()`, etc.)

### 3. Inertia Response Pattern
```php
return Inertia::render('Teacher/Assessments/Index', [
    'assessments' => AssessmentResource::collection($assessments),
    'permissions' => $request->user()->getAllPermissions()->pluck('name'),
]);
```
- Use Laravel Resources for data transformation
- Always pass `permissions` for frontend authorization checks

### 4. Frontend Authorization
```tsx
import { usePage } from '@inertiajs/react';

export default function AssessmentsIndex() {
    const { auth } = usePage().props;
    const can = (permission: string) => auth.permissions?.includes(permission);
    const isStudent = auth.user.roles.includes('student');

    return (
        <>
            {can('create assessments') && <CreateAssessmentButton />}
            {isStudent && <StudentDashboardLink />}
        </>
    );
}
```

## Models

### 13 Models
`AcademicYear`, `Answer`, `Assessment`, `AssessmentAssignment`, `Choice`, `ClassModel`, `ClassSubject`, `Enrollment`, `Level`, `Question`, `Semester`, `Subject`, `User`

### Conventions
- Use `$fillable` for mass assignment
- Casts set in `casts()` method (not `$casts` property)
- Define relationships with return type hints: `belongsTo`, `hasMany`, `belongsToMany`
- Pivot tables: `class_subject` (teacher-subject-class), `enrollments` (student-class)

### Key Enums
| Enum | Values |
|------|--------|
| `AssessmentType` | `homework`, `exam`, `practical`, `quiz`, `project` |
| `DeliveryMode` | `supervised` (timed, security-enforced), `homework` (deadline-based, multi-session) |
| `QuestionType` | `text`, `multiple`, `one_choice`, `boolean`, `file` |
| `EnrollmentStatus` | `active`, `withdrawn`, `completed` |

## Artisan Commands

### Scheduled Commands (`routes/console.php`)
| Command | Schedule | Purpose |
|---------|----------|---------|
| `assessment:materialise-assignments` | Every 30 min | Creates assignment records for enrolled students |
| `assessment:auto-submit-expired` | Every 5 min | Auto-submits expired assessment sessions |
| `notifications:send-reminders` | Every 5 min | Sends assessment start reminders |

### Utility Commands
| Command | Purpose |
|---------|---------|
| `app:setup-production` | Production setup wizard (roles, admin, cache, etc.) |
| `e2e:setup` | Prepare SQLite DB + seed for E2E tests |
| `e2e:teardown` | Delete E2E SQLite database |
| `db:refresh-seed` | Refresh DB with seeders |

## Development Workflows

### Running the App
```bash
composer dev    # Runs server + queue + scheduler + vite concurrently

# OR manually:
php artisan serve                   # Backend (port 8000)
yarn dev                            # Frontend (Vite)
php artisan queue:listen --tries=1  # Queue processing
php artisan schedule:work           # Scheduler
```

### Testing
```bash
# Backend (PHPUnit)
php artisan test                       # All tests
php artisan test --coverage            # With coverage (min 70%)
php artisan test --filter=ServiceTest  # Specific tests

# Frontend (Vitest)
yarn test:unit                         # All unit tests
yarn test:unit:watch                   # Watch mode
yarn test:unit:coverage                # Coverage report

# E2E (Playwright)
yarn test:e2e                          # Headless mode
yarn test:e2e:ui                       # UI mode
yarn test:e2e:debug                    # Debug mode
yarn test:e2e:report                   # View last report
```

### E2E Test Infrastructure
- Workspace: `e2e/` (yarn workspace `@evalium/e2e-tests`)
- Isolated SQLite DB: `database/e2e_testing.sqlite`
- Server port: `8001` (avoids conflict with dev on `8000`)
- Lifecycle: `global-setup.ts` runs `php artisan e2e:setup`, `global-teardown.ts` runs `php artisan e2e:teardown`
- Dedicated seeder: `database/seeders/E2ESeeder.php` with deterministic test data
- Config: `.env.testing` (Laravel) + `e2e/.env` (Playwright credentials) - both gitignored
- Test attribute: `data-e2e` (not `data-testid`)
- See `e2e/README.md` for full documentation

### Database
```bash
php artisan migrate:fresh --seed    # Reset DB with test data
```

### Code Quality
```bash
./vendor/bin/pint --dirty           # Auto-fix PHP style (run before commits)
yarn lint                           # ESLint
yarn format                         # Prettier
```

## Service Layer Guidelines

### Service Organization (SOLID Principles)

**Core Services** (`app/Services/Core/`):
- `AssessmentService` - Assessment CRUD, publish/unpublish, duplicate
- `AssessmentStatsService` - Statistics calculations (assessment, class, student)
- `QuestionCrudService` - Question CRUD operations
- `QuestionDuplicationService` - Question duplication logic
- `ChoiceManagementService` - Choice CRUD for questions
- `ScoringService` (`Core/Scoring/`) - Score calculation with Strategy pattern
- `GradeCalculationService` - Grade and average calculations
- `ClassSubjectService` - Teacher-subject-class assignments
- `CacheService` - Centralized cache key management
- `RoleBasedRedirectService` - Dashboard redirect per role
- `AnswerFormatterService` (`Core/Answer/`) - Answer formatting

**Teacher Services** (`app/Services/Teacher/`):
- `TeacherDashboardService` - Teacher dashboard statistics
- `TeacherClassResultsService` - Class results for teacher
- `AssignmentExceptionService` - Reopen/reassign assignment operations

**Student Services** (`app/Services/Student/`):
- `StudentAssessmentService` - Assessment session lifecycle (start, submit, save)
- `StudentDashboardService` - Student dashboard statistics
- `FileAnswerService` - File upload/download for file-type questions

**Admin Services** (`app/Services/Admin/`):
- `UserManagementService` - User CRUD and role management
- `ClassService` - Class management
- `EnrollmentService` - Student enrollment in classes
- `LevelService` - Educational level management
- `SubjectService` - Subject management
- `AcademicYearService` - Academic year and semester management
- `RoleService` - Role and permission management
- `AdminDashboardService` - Admin statistics

### Service Injection Pattern
```php
class AssessmentController extends Controller
{
    public function __construct(
        private readonly AssessmentService $assessmentService,
        private readonly AssessmentStatsService $assessmentStatsService
    ) {}
}
```

### Don't Duplicate Logic
- Check existing services before creating new methods
- Use `AssessmentStatsService` for any statistics calculation
- Use `ClassSubjectService` for teacher-subject-class relationships

## Important Configuration

### Assessment Configuration (`config/assessment.php`)
```php
'dev_mode' => env('EXAM_DEV_MODE', false),
'timing' => [
    'grace_period_seconds' => env('EXAM_GRACE_PERIOD_SECONDS', 30),
],
'file_uploads' => [
    'max_size_kb' => (int) env('EXAM_FILE_MAX_SIZE_KB', 10240),
    'allowed_extensions' => explode(',', env('EXAM_FILE_ALLOWED_EXTENSIONS', 'pdf,doc,...')),
],
```
- Set `EXAM_DEV_MODE=true` in `.env` to disable security for local testing

### Localization
- Locales: `en`, `fr`
- Backend: `__('messages.key')` -> `lang/{locale}/messages.php`
- Frontend: `useLaravelReactI18n` hook -> `t('messages.key')`
- Translation files: `lang/en/`, `lang/fr/` (PHP) + `lang/en.json`, `lang/fr.json` (JSON)
- Never hardcode strings in views or components - always use translation functions
- All translations loaded via `HandleInertiaRequests` middleware and cached

### Providers
- `AppServiceProvider` - App bindings
- `FlashMessageServiceProvider` - Flash message macros

## Common Pitfalls

1. **Don't bypass Policies** - Always call `$this->authorize()` in controllers before service methods
2. **Don't forget academic year context** - All assessments, classes, subjects are scoped by academic year
3. **Don't skip validation strategies** - Use `QuestionValidationContext`/`ScoreValidationContext` in Form Requests
4. **Don't forget TypeScript types** - Update `resources/ts/types/` when changing Laravel models
5. **Don't ignore test coverage** - Maintain >= 70% coverage (enforced in CI)
6. **Don't duplicate service logic** - Check existing services and follow SRP
7. **Don't add business logic to controllers** - Always delegate to services
8. **Don't bypass enrollment checks** - Students access assessments through class enrollment, not direct assignment
9. **Don't hardcode strings** - Use `trans()` / `__()` backend, `t()` frontend

## Key Documentation
Located in `documentation/`:
- `ASSESSMENT_MODULE.md` - Assessment domain model and architecture
- `ASSIGNMENT_MODULE.md` - Assignment lifecycle
- `ACADEMIC_YEAR_MODULE.md` - Academic year management
- `CLASS_MODULE.md` / `CLASS_SUBJECT_MODULE.md` - Class and subject structure
- `ENROLLMENT_MODULE.md` - Student enrollment system
- `STUDENT_MODULE.md` / `TEACHER_MODULE.md` / `ADMIN_USER_MODULE.md` - Role-specific modules
- `NOTIFICATION_SYSTEM_DESIGN.md` - Notification architecture
- `GRADE_CALCULATION_SYSTEM.md` - Grading logic
- `QUESTION_RENDERING_ARCHITECTURE.md` - Frontend question rendering
- `EVALIUM_BRANDING.md` - Brand guidelines
- `DATABASE_SCHEMA.md` - Full database schema
- `SCHEDULED_COMMANDS_AND_NOTIFICATIONS.md` - Scheduled tasks documentation
- `SETUP_PRODUCTION_COMMAND.md` - Production setup guide

## Quick Reference

### Adding a New Feature
1. **Controller**: Create/extend in `app/Http/Controllers/{Admin,Teacher,Student}/`
2. **Service**: Add business logic in `app/Services/{Admin,Teacher,Student,Core}/`
3. **Form Request**: Validate in `app/Http/Requests/` with Strategy if applicable
4. **Policy**: Define authorization rules in `app/Policies/`
5. **Route**: Add to `routes/web.php` with appropriate middleware
6. **Frontend**: Create Page in `resources/ts/Pages/`, use `@/` alias for imports
7. **Types**: Update `resources/ts/types/` if new data structures
8. **Tests**: Add PHPUnit (backend) + Vitest (frontend) + Playwright (E2E)

### Debugging
- **Laravel Debugbar**: Available in dev mode (`barryvdh/laravel-debugbar`)
- **Laravel Pail**: `php artisan pail` for real-time log monitoring
- **React DevTools**: Use browser extension
- **Inertia DevTools**: Monitor Inertia requests/responses

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.26
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- tightenco/ziggy (ZIGGY) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- @inertiajs/react (INERTIA) - v2
- react (REACT) - v19

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.


=== inertia-laravel/core rules ===

## Inertia Core

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (vite.config.js).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use `search-docs` for accurate guidance on all things Inertia.

<code-snippet lang="php" name="Inertia::render Example">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>


=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 & v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Polling
- Prefetching
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing / animated skeleton.

### Inertia Form General Guidance
- The recommended way to build forms when using Inertia is with the `<Form>` component - a useful example is below. Use `search-docs` with a query of `form component` for guidance.
- Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use `search-docs` with a query of `useForm helper` for guidance.
- `resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` are available on the `<Form>` component. Use `search-docs` with a query of 'form component resetting' for guidance.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== phpunit/core rules ===

## PHPUnit Core

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files, these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).


=== inertia-react/core rules ===

## Inertia + React

- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="react">

import { Link } from '@inertiajs/react'
<Link href="/">Home</Link>

</code-snippet>


=== inertia-react/v2/forms rules ===

## Inertia + React Forms

<code-snippet name="`<Form>` Component Example" lang="react">

import { Form } from '@inertiajs/react'

export default () => (
    <Form action="/users" method="post">
        {({
            errors,
            hasErrors,
            processing,
            wasSuccessful,
            recentlySuccessful,
            clearErrors,
            resetAndClearErrors,
            defaults
        }) => (
        <>
        <input type="text" name="name" />

        {errors.name && <div>{errors.name}</div>}

        <button type="submit" disabled={processing}>
            {processing ? 'Creating...' : 'Create User'}
        </button>

        {wasSuccessful && <div>User created successfully!</div>}
        </>
    )}
    </Form>
)

</code-snippet>
</laravel-boost-guidelines>
