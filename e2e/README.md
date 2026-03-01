# E2E Tests - Evalium

## Overview

End-to-end tests using **Playwright ^1.56** running against a dedicated SQLite database.
Everything is automated: running `yarn test:e2e` from the project root handles database setup,
frontend build, Laravel server startup, test execution, and full cleanup.

### Lifecycle

```
globalSetup (automatic)
  1. Load .env.testing from project root
  2. Create fresh SQLite DB (database/e2e_testing.sqlite)
  3. Run migrations + E2ESeeder
  4. Build frontend (yarn build)
  5. Start php artisan serve on port 8001
  6. Create playwright/.auth/ directory

Tests run (Playwright)

globalTeardown (automatic)
  1. Stop Laravel server (PID-based)
  2. Delete SQLite DB (php artisan e2e:teardown)
  3. Remove public/build/
```

---

## Quick Start

```bash
# Install E2E dependencies (first time only)
cd e2e && yarn install && cd ..

# Run all tests
yarn test:e2e

# Run by role
yarn test:e2e:admin
yarn test:e2e:teacher
yarn test:e2e:student

# Interactive UI mode (requires manual setup first)
php artisan e2e:setup
yarn test:e2e:ui

# Debug mode
yarn test:e2e:debug

# View HTML report
yarn test:e2e:report
```

---

## Configuration

### Files

| File | Purpose |
|------|---------|
| `.env.testing` (project root) | Laravel env for E2E server (APP_URL, DB_CONNECTION, etc.) |
| `e2e/.env` | Playwright test credentials (loaded by `e2e/Helpers/utils.ts`) |
| `e2e/playwright.config.ts` | Playwright configuration (projects, timeouts, reporters) |
| `config/database.php` | `e2e_testing` connection (SQLite) |

### .env.testing (project root)

```dotenv
APP_ENV=testing
APP_URL=http://localhost:8001
DB_CONNECTION=e2e_testing
QUEUE_CONNECTION=sync
CACHE_STORE=array
DEBUGBAR_ENABLED=false
ASSESSMENT_SECURITY_ENABLED=false
ASSESSMENT_DEV_MODE=true
```

### e2e/.env

```dotenv
BASE_URL=http://localhost:8001
ADMIN_EMAIL=admin@evalium.test
ADMIN_PASSWORD=password
TEACHER_EMAIL=teacher@evalium.test
TEACHER_PASSWORD=password
STUDENT_EMAIL=student@evalium.test
STUDENT_PASSWORD=password
```

> Credentials MUST match `database/seeders/E2ESeeder.php`. If you change one, update the other.

### Playwright Settings

| Setting | Value |
|---------|-------|
| Base URL | `http://localhost:8001` |
| Test ID attribute | `data-e2e` |
| Test timeout | 30s |
| Expect timeout | 10s |
| Navigation timeout | 15s |
| Action timeout | 10s |
| Trace | Always on |
| Screenshot | Always on |
| Video | Always on |

---

## Artisan Commands

### `php artisan e2e:setup`

Creates the SQLite database, runs `migrate:fresh` on the `e2e_testing` connection,
and seeds with `E2ESeeder`.

```bash
php artisan e2e:setup
```

### `php artisan e2e:teardown`

Deletes the SQLite file at `database/e2e_testing.sqlite`.

```bash
php artisan e2e:teardown
```

---

## Test Data (E2ESeeder)

All data is created by `database/seeders/E2ESeeder.php`. The dataset is minimal
but covers the full entity chain needed for testing.

### Users

| Role | Name | Email | Password |
|------|------|-------|----------|
| `super_admin` | Admin E2E | `admin@evalium.test` | `password` |
| `teacher` | Marie Dupont | `teacher@evalium.test` | `password` |
| `student` | Alice Martin | `student@evalium.test` | `password` |
| `student` | Bob Durand | `bob.durand@evalium.test` | `password` |
| `student` | Clara Bernard | `clara.bernard@evalium.test` | `password` |

All users have `email_verified_at` set and `is_active = true`.

### Roles & Permissions

Seeded by `RoleAndPermissionSeeder`. 4 roles, 30+ permissions:

| Role | Permissions |
|------|-------------|
| `super_admin` | All (wildcard) |
| `admin` | User, level, academic year, subject, class, enrollment, class-subject management |
| `teacher` | Assessment CRUD, view class-subjects |
| `student` | View assessments, take assessments |

### Academic Structure

| Entity | Data |
|--------|------|
| **Academic Year** | `2025/2026` (Sep 1 - Jun 30, `is_current = true`) |
| **Semesters** | Semestre 1 (Sep-Jan), Semestre 2 (Feb-Jun) |
| **Level** | `L1` (Licence 1, active) |
| **Subjects** | Mathematics (`MATH_L1`), Physics (`PHYS_L1`) |
| **Class** | `L1 - Group A` (max 30 students) |

### Enrollments

All 3 students are enrolled in `L1 - Group A` with `active` status.

### Class-Subject Assignments

Both subjects (Math + Physics) are assigned to the teacher `Marie Dupont`
in `L1 - Group A` for Semestre 1, coefficient 3.0.

### Assessments (4 total)

| Title | Type | Mode | Duration | Published | Scheduled |
|-------|------|------|----------|-----------|-----------|
| Exam - Mathematics | `exam` | `supervised` | 60 min | Yes | +7 days |
| Quiz - Mathematics | `quiz` | `supervised` | 20 min | No | +14 days |
| Exam - Physics | `exam` | `supervised` | 60 min | Yes | +7 days |
| Quiz - Physics | `quiz` | `supervised` | 20 min | No | +14 days |

---

## Project Structure

```
e2e/
  .env                          # Test credentials
  .env.example                  # Credential template
  playwright.config.ts          # Playwright configuration
  global-setup.ts               # Automated setup (DB + build + server)
  global-teardown.ts            # Automated cleanup
  package.json                  # Workspace package

  setup/                        # Authentication setup (run before tests)
    auth.admin.setup.ts         # Login as admin, save storageState
    auth.teacher.setup.ts       # Login as teacher, save storageState
    auth.student.setup.ts       # Login as student, save storageState

  Helpers/                      # Reusable test utilities
    Core.ts                     # Base helper (goto, getByTestId, waitFor...)
    AuthHelper.ts               # Login/logout helpers
    FormHelper.ts               # Form interaction helpers
    fixtures.ts                 # Playwright test fixtures with DI
    utils.ts                    # Credentials & config from .env
    index.ts                    # Barrel export

  Pages/                        # Page Object Models
    LoginPage.ts                # Login page POM
    index.ts                    # Barrel export

  admin/                        # Admin role test specs (to create)
  teacher/                      # Teacher role test specs (to create)
  student/                      # Student role test specs (to create)
```

## Playwright Projects

| Project | Match Pattern | Auth | Dependencies |
|---------|--------------|------|--------------|
| `setup-admin` | `auth.admin.setup.ts` | None | - |
| `setup-teacher` | `auth.teacher.setup.ts` | None | - |
| `setup-student` | `auth.student.setup.ts` | None | - |
| `admin` | `admin/**/*.spec.ts` | `admin.json` | `setup-admin` |
| `teacher` | `teacher/**/*.spec.ts` | `teacher.json` | `setup-teacher` |
| `student` | `student/**/*.spec.ts` | `student.json` | `setup-student` |

---

## Writing New Tests

### 1. Create the spec file

Place it in the correct role folder:

```typescript
// e2e/admin/users.spec.ts
import { test, expect } from '@playwright/test';

test.describe('Admin - Users', () => {
    test('should list all users', async ({ page }) => {
        await page.goto('/admin/users');
        await expect(page.getByTestId('datatable-body')).toBeVisible();
    });
});
```

### 2. Add `data-e2e` attributes to components

```tsx
<button data-e2e="create-user-button">Create</button>
<div data-e2e="user-list">...</div>
```

Tests use `page.getByTestId('create-user-button')` which maps to `data-e2e`.

### 3. Use fixtures for DI (optional)

```typescript
import { test, expect } from '../Helpers/fixtures';

test('should login as admin', async ({ loginPage, adminCredentials }) => {
    await loginPage.navigate();
    await loginPage.loginWith(
        adminCredentials.email,
        adminCredentials.password,
        true
    );
    await expect(page).toHaveURL(/dashboard/);
});
```

### 4. Use Page Object Models

```typescript
import { LoginPage } from '../Pages';

const loginPage = new LoginPage(page);
await loginPage.navigate();
await loginPage.loginWith('admin@evalium.test', 'password');
```

---

## Troubleshooting

### Auth setup fails (stays on /login)

- Verify `e2e/.env` credentials match `E2ESeeder.php`
- Run `php artisan e2e:setup` manually and check for errors
- Check that `data-e2e="email"` and `data-e2e="password"` exist on login inputs

### Port conflict

- Default E2E port is `8001` (avoids conflict with dev server on `8000`)
- Change in `.env.testing` (`APP_URL`) and `e2e/.env` (`BASE_URL`)

### Server stays running after tests

- Check for `e2e/.laravel-server.pid`
- Windows: `taskkill /F /IM php.exe`
- Linux/Mac: `kill -9 $(cat e2e/.laravel-server.pid)`

### UI mode requires manual setup

`globalSetup` does not run in UI mode. Set up manually first:

```bash
php artisan e2e:setup
php artisan serve --port=8001 &
yarn test:e2e:ui
```

### Vite manifest error

If tests fail with `Unable to locate file in Vite manifest`:

```bash
yarn build    # or npm run build
```

