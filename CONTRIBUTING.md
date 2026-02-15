# Contributing to Evalium

Thank you for your interest in contributing to Evalium. This guide covers the conventions and workflow to follow.

## Prerequisites

- PHP >= 8.4, Composer >= 2
- Node.js >= 20, Yarn
- MySQL >= 8.0

## Setup

```bash
git clone https://github.com/Soule73/evalium.git
cd evalium
composer install
yarn install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

## Development Workflow

### Branch Naming

Create a branch from `develop`:

```bash
git checkout develop
git pull origin develop
git checkout -b <type>/<short-description>
```

Branch types: `feature/`, `fix/`, `refactor/`, `docs/`, `test/`

### Commit Conventions

This project uses [Conventional Commits](https://www.conventionalcommits.org/) enforced by Commitlint.

```
<type>(<scope>): <Subject in sentence case>
```

**Types:** `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`, `perf`, `ci`, `build`, `revert`

**Examples:**
```
feat(assessments): Add homework delivery mode
fix(scoring): Correct max score validation
refactor(migrations): Consolidate ALTER migrations into parent files
```

**Rules:**
- Header max 100 characters
- Subject in sentence case, no trailing period
- Body and footer separated by blank lines

### Code Style

**Backend (PHP):**
- Run `vendor/bin/pint --dirty` before committing
- Use constructor property promotion
- Explicit return types on all methods
- PHPDoc blocks on classes and public methods (English)
- No inline comments -- code must be self-explanatory

**Frontend (TypeScript/React):**
- Strict TypeScript, no `any`
- Use `@/` path alias for imports
- Follow React hooks rules
- Never hardcode strings -- use `t()` from `useTranslations` hook

### Architecture Rules

**Backend:**
- Controllers are thin -- delegate to Services
- Business logic goes in `app/Services/` (organized by Admin, Core, Student, Teacher)
- Validation in Form Request classes, using Strategy pattern when applicable
- Authorization via Policies (Spatie Permission)
- No `DB::` facade -- use `Model::query()` and Eloquent relationships
- No `env()` outside config files

**Frontend:**
- Pages in `resources/ts/Pages/` receive props from controllers via Inertia
- Reusable components in `resources/ts/Components/`
- List components follow the variant pattern (see `BaseEntityList`)
- Translations in `lang/` (both `en` and `fr`)

## Testing

Every change must include tests.

```bash
# Backend (PHPUnit)
php artisan test --filter=YourTestName

# Frontend (Vitest)
yarn test:unit

# E2E (Playwright)
yarn test:e2e
```

- Feature tests for controllers and services
- Unit tests for isolated logic
- Maintain coverage >= 70%

## Pull Requests

1. Ensure all tests pass locally
2. Run `vendor/bin/pint --dirty`
3. Push your branch and open a PR against `develop`
4. Fill in the PR description (what, why, how)
5. Link related issues if applicable

## Reporting Issues

Open an [issue](https://github.com/Soule73/evalium/issues/new) with:

- Clear description of the problem
- Steps to reproduce
- Expected vs actual behavior
- Environment details (OS, PHP version, Node version)
- Screenshots if applicable
