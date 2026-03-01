# Evalium

[![Tests](https://github.com/Soule73/evalium/actions/workflows/tests.yml/badge.svg)](https://github.com/Soule73/evalium/actions/workflows/tests.yml)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel)](https://laravel.com)
[![React](https://img.shields.io/badge/React-19-61DAFB?logo=react)](https://react.dev)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.x-3178C6?logo=typescript)](https://www.typescriptlang.org/)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> **Where every grade tells a story.**

Evalium is an online assessment management platform built with **Laravel 12**, **React 19**, **TypeScript** and **Inertia.js 2**. It provides a complete workflow for creating, delivering, taking, and grading assessments within an academic context.

---

## Features

### Multi-Role System
- **Admin** -- Manage users, classes, subjects, academic years, enrollments, roles & permissions
- **Teacher** -- Create assessments (supervised / homework), assign to classes, grade student submissions
- **Student** -- Take assessments with live timer, auto-save answers, view results & grade history

### Assessment Engine
- Multiple question types: single choice, multiple choice, free text
- Two delivery modes: **supervised** (timed, anti-cheat) and **homework** (deadline-based, file upload)
- Automatic scoring for choice-based questions, manual grading for text questions
- Assessment duplication, per-question scoring with feedback, grade calculation

### Academic Structure
- Academic years with semesters
- Classes scoped by academic year and level
- Subject-class-teacher assignments (pivot)
- Student enrollment per class with grade tracking

### Security (Supervised Mode)
- Fullscreen enforcement
- Tab-switch detection
- DevTools detection
- Forced submission on violation
- Configurable via `config/assessment.php`

### Frontend Architecture
- 13 shared list components following a **variant pattern** (BaseEntityList + domain-specific lists)
- Reusable DataTable with server-side pagination, search, filters
- Breadcrumb system, role-aware sidebar navigation
- Bilingual interface (English / French) via `laravel-react-i18n`

---

## Tech Stack

| Layer     | Technology                                            |
|-----------|-------------------------------------------------------|
| Backend   | Laravel 12, PHP 8.4+, MySQL 8                        |
| Frontend  | React 19, TypeScript 5, Inertia.js 2, Tailwind CSS 4 |
| Auth      | Spatie Permission (hybrid role + permission strategy)  |
| Testing   | PHPUnit 11, Vitest, Playwright                        |
| CI/CD     | GitHub Actions                                        |
| Quality   | Laravel Pint, ESLint, Commitlint                      |

---

## Getting Started

### Prerequisites

- PHP >= 8.4, Composer >= 2
- Node.js >= 20, Yarn
- MySQL >= 8.0

### Installation

```bash
git clone https://github.com/Soule73/evalium.git
cd evalium

composer install
yarn install

cp .env.example .env
php artisan key:generate
```

Create the database, then:

```bash
php artisan migrate --seed
```

### Running

```bash
# All-in-one (server + vite + queue)
composer run dev

# Or manually:
php artisan serve       # Backend on :8000
yarn dev                # Vite dev server
php artisan queue:listen --tries=1
```

### Test Accounts

After seeding:

| Role    | Email                    | Password |
|---------|--------------------------|----------|
| Admin   | admin@evalium.com        | password |
| Teacher | teacher1@evalium.com     | password |
| Student | student1@evalium.com     | password |

---

## Testing

```bash
# Backend
php artisan test                     # All tests
php artisan test --filter=ClassName  # Specific test

# Frontend
yarn test:unit                       # Vitest
yarn test:unit:coverage              # With coverage

# E2E
yarn test:e2e                        # Playwright headless
yarn test:e2e:ui                     # Playwright UI mode
```

---

## Project Structure

```
app/
  Http/Controllers/       Thin controllers (delegate to services)
  Models/                 Eloquent models with relationships
  Policies/               Authorization (Spatie Permission)
  Services/
    Admin/                User, class, enrollment, academic year management
    Core/                 Assessment, scoring, grade calculation
    Student/              Assessment sessions, assignment queries
    Teacher/              Dashboard stats
  Strategies/Validation/  Question & score validation (Strategy pattern)

resources/ts/
  Components/
    features/             Domain-specific components (assessment, classes...)
    layout/               Sidebar, navbar, breadcrumbs, logo
    shared/lists/         13 shared list components (variant pattern)
    ui/                   Design system (@evalium/ui)
  Pages/                  Inertia page components (Admin, Teacher, Student)
  hooks/                  Custom React hooks
  types/                  TypeScript interfaces

documentation/            Architecture diagrams, branding guide
```

---

## Documentation

- [EVALIUM_BRANDING.md](documentation/EVALIUM_BRANDING.md) -- Brand guidelines, logo, color palette
- [CONTRIBUTING.md](CONTRIBUTING.md) -- How to contribute
- [CHANGELOG.md](CHANGELOG.md) -- Version history
- [documentation/](documentation/) -- Architecture decisions, refactoring plans, audit reports

---

## Authors

- **Soule Soumare** -- [@Soule73](https://github.com/Soule73)

## License

[MIT](LICENSE)
