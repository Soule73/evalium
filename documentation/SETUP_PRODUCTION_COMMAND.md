# Setup Production Command

## Overview

The `app:setup-production` Artisan command prepares the Evalium platform for its first production deployment. It performs a full database reset, seeds roles and permissions, creates a super-admin account, and initializes an academic year — making the platform immediately usable.

## Usage

```bash
php artisan app:setup-production
```

### Options

| Option          | Description                                                |
|-----------------|------------------------------------------------------------|
| `--force`       | Skip the confirmation prompt (useful for scripted deploys) |
| `--skip-reset`  | Skip `migrate:fresh` (used in automated tests)            |

### Examples

```bash
# Interactive setup (recommended for first deploy)
php artisan app:setup-production

# Non-interactive with forced confirmation
php artisan app:setup-production --force

# In a CI/test context (skips database wipe)
php artisan app:setup-production --force --skip-reset
```

---

## Execution Flow

```
1. Confirmation prompt (skipped with --force)
2. Database reset via migrate:fresh (skipped with --skip-reset)
3. Seed roles & permissions (RoleAndPermissionSeeder)
4. Prompt for super-admin credentials (name, email, password)
5. Create super-admin user with email verified
6. Prompt for academic year details (name, start date, end date)
7. Create academic year marked as current
8. Display summary
```

---

## Interactive Prompts

The command uses `Laravel\Prompts` for a rich CLI experience with built-in validation.

### Super-Admin Account

| Prompt             | Default         | Validation                               |
|--------------------|-----------------|------------------------------------------|
| Admin name         | `Super Admin`   | Required                                 |
| Admin email        | —               | Required, valid email format              |
| Password           | —               | Required, minimum 8 characters           |
| Confirm password   | —               | Required, must match password             |

### Academic Year

| Prompt             | Default              | Validation                               |
|--------------------|----------------------|------------------------------------------|
| Academic year name | Auto-detected *      | Required, format `YYYY/YYYY`             |
| Start date         | `{startYear}-09-01`  | Required, format `YYYY-MM-DD`            |
| End date           | `{startYear+1}-06-30`| Required, format `YYYY-MM-DD`, after start |

\* The default academic year name is calculated from the current month:
- **September or later** → `currentYear/(currentYear+1)` (e.g. `2025/2026`)
- **Before September** → `(currentYear-1)/currentYear` (e.g. `2024/2025`)

---

## What Gets Created

### Roles (4)

| Role          | Permissions                                      |
|---------------|--------------------------------------------------|
| `super_admin` | All permissions (via Spatie wildcard)             |
| `admin`       | User, level, academic year, subject, class, enrollment, class-subject management |
| `teacher`     | Assessment CRUD, view class-subjects              |
| `student`     | View assessments, take assessments                |

### Permissions (30+)

Managed by `RoleAndPermissionSeeder`. Covers: user management, level management, academic year management, subject management, class management, enrollment management, class-subject management, and assessment management.

### Super-Admin User

- Created with the provided credentials
- `email_verified_at` set automatically
- `is_active` set to `true`
- Assigned the `super_admin` role (all permissions)

### Academic Year

- Created with the provided name, start date, and end date
- Marked as `is_current = true`

---

## Testing

Tests are located in `tests/Feature/Commands/SetupProductionTest.php`.

```bash
php artisan test --filter=SetupProductionTest
```

### Test Cases (5 tests, 60 assertions)

| Test                                    | Verifies                                                    |
|-----------------------------------------|-------------------------------------------------------------|
| `test_creates_roles_and_permissions`    | All 4 roles exist, permissions seeded                       |
| `test_creates_super_admin_user`         | User created with correct name, email, role, verified email |
| `test_creates_academic_year_as_current` | Year created with correct dates, marked as current          |
| `test_full_setup_produces_usable_state` | Exactly 1 user, 1 year, 4+ roles, permissions present      |
| `test_admin_has_all_permissions`        | Super-admin has every permission in the system              |

All tests use `--force --skip-reset` and `expectsQuestion()` to simulate prompts.

---

## Production Deployment Checklist

1. Deploy code to production server
2. Configure `.env` (database, mail, queue, etc.)
3. Run `php artisan app:setup-production`
4. Follow the interactive prompts
5. Start the queue worker: `php artisan queue:listen --tries=1`
6. Configure the scheduler cron: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`
7. Log in with the super-admin credentials and begin platform configuration
