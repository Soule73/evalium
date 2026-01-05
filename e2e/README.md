# E2E Tests for Examena

## Quick Start

### First Time Setup

```bash
# Run the automated setup script
npm run setup:e2e

# Or manually:
# 1. Copy .env.example to .env
cp resources/ts/tests/e2e/.env.example resources/ts/tests/e2e/.env

# 2. Install E2E workspace dependencies
cd resources/ts/tests/e2e && yarn install && cd ../../../..

# 3. Setup authentication for all roles
npm run test:e2e:setup
```

### Running Tests

```bash
# Run all tests
npm run test:e2e

# Open Playwright UI for interactive testing
npm run test:e2e:ui

# Run tests for specific roles
npm run test:e2e:admin
npm run test:e2e:teacher
npm run test:e2e:student

# Debug mode
npm run test:e2e:debug

# Generate and view test report
npm run test:e2e:report
```

## Workspace Configuration

This E2E testing suite is configured as a **Yarn workspace** to provide environment isolation. For detailed information about the workspace setup, environment variables, and configuration, see:

📖 **[WORKSPACE.md](WORKSPACE.md)** - Complete workspace and environment setup guide

Key benefits:
- ✅ Isolated dependencies (E2E packages don't affect main app)
- ✅ Environment-based configuration via `.env`
- ✅ Secure credential management
- ✅ Easy team collaboration
- ✅ CI/CD ready

---

## Test Structure

```
resources/ts/tests/e2e/
├── setup/                      # Authentication setup files
│   ├── auth.admin.setup.ts     # Admin authentication
│   ├── auth.teacher.setup.ts   # Teacher authentication
│   └── auth.student.setup.ts   # Student authentication
├── Helpers/                    # Reusable helpers
│   ├── Core.ts                 # Base helper with common utilities
│   ├── AuthHelper.ts           # Authentication utilities
│   ├── NavigationHelper.ts     # Navigation utilities
│   ├── FormHelper.ts           # Form interaction utilities
│   └── index.ts                # Barrel export
├── Pages/                      # Page Object Models
│   └── LoginPage.ts            # Login page POM
├── admin/                      # Admin role tests
│   └── dashboard.spec.ts
├── teacher/                    # Teacher role tests
│   └── dashboard.spec.ts
├── student/                    # Student role tests
│   └── dashboard.spec.ts
└── auth.spec.ts                # Authentication tests (no auth)
```

## Test IDs Configuration

All test IDs use `data-e2e` attribute instead of `data-testid`:

```tsx
<input data-e2e="email-input" />
<button data-e2e="login-submit">Login</button>
```

Access in tests:
```typescript
page.getByTestId('email-input') // Uses data-e2e automatically
```

## Playwright Projects

### 1. Setup Projects
- `setup-admin`: Authenticates admin user
- `setup-teacher`: Authenticates teacher user
- `setup-student`: Authenticates student user

### 2. Test Projects
- `admin`: Tests for admin role (depends on setup-admin)
- `teacher`: Tests for teacher role (depends on setup-teacher)
- `student`: Tests for student role (depends on setup-student)
- `auth`: Authentication tests (no authentication)

### 3. Optional Projects
- `admin-firefox`: Admin tests on Firefox
- `webkit`: Admin tests on Safari

## Authentication

### Session Persistence

Authentication state is saved per role:
- Admin: `playwright/.auth/admin.json`
- Teacher: `playwright/.auth/teacher.json`
- Student: `playwright/.auth/student.json`

### Default Credentials

```typescript
// Admin
email: 'admin@example.com'
password: 'password123'

// Teacher
email: 'teacher@example.com'
password: 'password123'

// Student
email: 'student@example.com'
password: 'password123'
```

## Helpers Usage

### Core Helper

```typescript
import { Core } from '@/tests/e2e/Helpers';

const core = new Core(page);
await core.goto('/dashboard');
await core.clickByTestId('button-id');
await core.expectTestIdVisible('element-id');
```

### AuthHelper

```typescript
import { AuthHelper } from '@/tests/e2e/Helpers';

const auth = new AuthHelper(page);
await auth.loginAsAdmin();
await auth.verifyLoginSuccess();
await auth.logout();
```

### NavigationHelper

```typescript
import { NavigationHelper } from '@/tests/e2e/Helpers';

const nav = new NavigationHelper(page);
await nav.gotoDashboard();
await nav.gotoExams();
await nav.clickSidebarItem('users');
```

### FormHelper

```typescript
import { FormHelper } from '@/tests/e2e/Helpers';

const form = new FormHelper(page);
await form.fillByTestId('name-input', 'John Doe');
await form.checkByTestId('active-checkbox');
await form.submitFormByTestId('submit-button');
```

## Page Object Models

### LoginPage

```typescript
import { LoginPage } from '@/tests/e2e/Pages/LoginPage';

const loginPage = new LoginPage(page);
await loginPage.navigate();
await loginPage.loginWith('email@example.com', 'password', true);
```

## Running Tests

### All tests
```bash
npm run test:e2e
```

### Specific project
```bash
npx playwright test --project=admin
npx playwright test --project=teacher
npx playwright test --project=student
```

### Specific test file
```bash
npx playwright test admin/dashboard.spec.ts
```

### Debug mode
```bash
npx playwright test --debug
```

### UI mode
```bash
npm run test:e2e:ui
```

### View report
```bash
npm run test:e2e:report
```

## Best Practices

1. **Always use data-e2e for test IDs**
   ```tsx
   <button data-e2e="submit-button">Submit</button>
   ```

2. **Use helpers for common operations**
   ```typescript
   const auth = new AuthHelper(page);
   await auth.loginAsAdmin(); // Instead of manual login
   ```

3. **Use Page Object Models**
   ```typescript
   const loginPage = new LoginPage(page);
   await loginPage.loginWith(email, password);
   ```

4. **Organize tests by role**
   - Admin tests in `admin/` folder
   - Teacher tests in `teacher/` folder
   - Student tests in `student/` folder

5. **Use descriptive test names**
   ```typescript
   test('should create new exam with valid data', async ({ page }) => {
       // Test implementation
   });
   ```

6. **Wait for navigation and loading states**
   ```typescript
   await core.waitForNavigation();
   await core.waitForResponse('/api/exams');
   ```

## Adding New Tests

### 1. Create test file in appropriate folder
```typescript
// resources/ts/tests/e2e/admin/users.spec.ts
import { test, expect } from '@playwright/test';

test.describe('Admin - Users', () => {
    test('should list all users', async ({ page }) => {
        // Test implementation
    });
});
```

### 2. Add data-e2e to components
```tsx
<button data-e2e="create-user-button">Create User</button>
```

### 3. Use helpers
```typescript
const nav = new NavigationHelper(page);
await nav.gotoUsers();
await nav.clickByTestId('create-user-button');
```

## Troubleshooting

### Tests fail due to authentication
1. Delete auth files: `rm -rf playwright/.auth/*.json`
2. Run setup: `npx playwright test --project=setup-admin`

### Can't find elements
1. Check data-e2e attribute exists in component
2. Use `page.pause()` to debug
3. Check element is visible: `await element.waitFor({ state: 'visible' })`

### Timeouts
1. Increase timeout in test: `test.setTimeout(60000)`
2. Check network tab for slow requests
3. Use `waitForLoadState('networkidle')`
