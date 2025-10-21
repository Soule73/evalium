# CI/CD Configuration Documentation

## 📋 Overview

This project includes CI/CD pipelines for both **GitHub Actions** and **GitLab CI** to automatically run all test suites.

## 🧪 Test Suites

### 1. **Laravel Tests (PHPUnit)**
- Backend unit and feature tests
- Database tests with MySQL
- Coverage report (minimum 70%)

### 2. **Jest Tests**
- Frontend unit tests (TypeScript/React)
- Component testing
- Coverage report

### 3. **Playwright Tests**
- End-to-end tests
- Browser automation (Chromium)
- Full application flow testing

---

## 🐙 GitHub Actions

### File: `.github/workflows/tests.yml`

### Triggers
- Push to `main` or `develop` branches
- Pull requests to `main` or `develop`
- Manual workflow dispatch

### Jobs

#### 1. `laravel-tests`
- **Runtime**: ~5-10 minutes
- **Database**: MySQL 8.0
- **PHP**: 8.4
- **Coverage**: Xdebug
- **Artifacts**: PHPUnit coverage reports

#### 2. `jest-tests`
- **Runtime**: ~2-5 minutes
- **Node**: 20
- **Artifacts**: Jest coverage reports

#### 3. `playwright-tests`
- **Runtime**: ~10-20 minutes
- **Browser**: Chromium only (faster CI)
- **Database**: MySQL 8.0
- **Artifacts**: 
  - Playwright HTML report
  - Test results with screenshots/videos
  - Traces for debugging

#### 4. `test-summary`
- Aggregates all test results
- Fails if any test suite fails

### Viewing Results

1. **GitHub Actions Tab**: See all workflow runs
2. **Pull Request**: Checks appear automatically
3. **Artifacts**: Download from workflow run page
   - `phpunit-coverage/`
   - `jest-coverage/`
   - `playwright-report/`
   - `playwright-results/`

### Local Testing

```bash
# Run all tests locally like CI does
npm run test:unit          # Jest
php artisan test           # PHPUnit
npm run test:e2e           # Playwright
```

---

## 🦊 GitLab CI

### File: `.gitlab-ci.yml`

### Stages
1. **build**: Install dependencies
2. **test**: Run all test suites
3. **deploy**: Generate pages (optional)

### Jobs

#### Build Stage

**`build:composer`**
- Install PHP dependencies
- Validate composer.json
- Cache vendor/

**`build:npm`**
- Install Node dependencies
- Build frontend assets
- Cache node_modules/

#### Test Stage

**`test:laravel`**
- PHPUnit with MySQL
- Coverage report (Cobertura format)
- JUnit XML for GitLab integration

**`test:jest`**
- Jest with coverage
- Cobertura and JUnit reports

**`test:playwright`**
- E2E tests with Chromium
- Full Laravel app running
- Screenshots/videos on failure

**`test:code-quality`** (optional)
- PHPStan static analysis
- Laravel Pint code style
- Allowed to fail

**`test:security`** (optional)
- Composer audit
- Security vulnerabilities check
- Allowed to fail

**`test:summary`**
- Shows overall test status
- Runs after all tests

#### Deploy Stage (optional)

**`pages`**
- Publishes test reports to GitLab Pages
- Available at: `https://yourusername.gitlab.io/examena/`
- Only runs on `main` branch

### Viewing Results

1. **Pipeline View**: See all jobs status
2. **Test Reports**: Integrated in Merge Requests
3. **Coverage**: Shown in repository badges
4. **Pages**: Browse HTML reports

### GitLab Pages Structure

```
https://yourusername.gitlab.io/examena/
├── index.html           # Main page
├── coverage/            # Combined coverage
└── playwright/          # E2E test report
```

---

## 🔧 Configuration

### Environment Variables

**GitHub Actions** (Set in repository settings)
```yaml
# No secrets needed for basic tests
# Add if needed:
# - APP_KEY (auto-generated)
# - DB credentials (use services)
```

**GitLab CI** (Set in CI/CD settings)
```yaml
# Predefined variables used:
CI_COMMIT_REF_SLUG    # For cache key
CI_PROJECT_DIR        # Working directory

# Add custom variables if needed:
# - DEPLOY_KEY
# - NOTIFICATION_WEBHOOK
```

### Caching

**GitHub Actions**
- Composer: Cached by `shivammathur/setup-php`
- NPM: Cached by `actions/setup-node`

**GitLab CI**
```yaml
cache:
  key: ${CI_COMMIT_REF_SLUG}
  paths:
    - vendor/
    - node_modules/
```

---

## 📊 Coverage Reports

### Minimum Coverage
- **PHPUnit**: 70%
- **Jest**: 80% (recommended)
- **Overall**: Tracked separately per suite

### Viewing Coverage

**GitHub Actions**
1. Download artifacts from workflow run
2. Extract and open `index.html`

**GitLab CI**
1. Go to repository → Analytics → Repository
2. View coverage trends
3. Or visit GitLab Pages

---

## 🚀 Optimization Tips

### Speed Up CI

1. **Cache Dependencies**
   - Already configured for both platforms

2. **Parallel Jobs**
   - Tests run in parallel automatically

3. **Selective Testing**
   ```yaml
   # GitHub Actions
   on:
     paths:
       - 'app/**'
       - 'tests/**'
   
   # GitLab CI
   only:
     changes:
       - app/**
       - tests/**
   ```

4. **Matrix Strategy** (GitHub)
   ```yaml
   strategy:
     matrix:
       php: [8.3, 8.4]
   ```

### Reduce Playwright Time

**Current**: Only Chromium in CI
**Optional**: Add more browsers locally

```yaml
# playwright.config.ts - CI detection
const isCI = !!process.env.CI;

projects: isCI 
  ? [chromiumProject] 
  : [chromiumProject, firefoxProject, webkitProject]
```

---

## 🐛 Troubleshooting

### Common Issues

#### 1. **MySQL Connection Failed**
```bash
# GitHub Actions
services:
  mysql:
    options: >-
      --health-cmd="mysqladmin ping"
      --health-interval=10s
```

#### 2. **Playwright Timeout**
```bash
# Increase timeout in playwright.config.ts
timeout: 60 * 1000,  # 60 seconds
```

#### 3. **Out of Memory**
```bash
# GitLab CI - Increase memory
before_script:
  - export NODE_OPTIONS="--max_old_space_size=4096"
```

#### 4. **Artifact Upload Failed**
```yaml
# Ensure paths exist
artifacts:
  when: always  # Upload even on failure
```

### Debug Mode

**GitHub Actions**
```yaml
# Add to workflow file
- name: Debug
  run: |
    echo "Node version: $(node -v)"
    echo "PHP version: $(php -v)"
    ls -la
```

**GitLab CI**
```yaml
# Set in GitLab UI
CI_DEBUG_TRACE: "true"
```

---

## 📈 Badges

### GitHub

```markdown
![Tests](https://github.com/Soule73/examena/workflows/Tests/badge.svg)
```

### GitLab

```markdown
[![pipeline status](https://gitlab.com/yourusername/examena/badges/main/pipeline.svg)](https://gitlab.com/yourusername/examena/-/commits/main)
[![coverage report](https://gitlab.com/yourusername/examena/badges/main/coverage.svg)](https://gitlab.com/yourusername/examena/-/commits/main)
```

---

## 📝 Maintenance

### Update Dependencies

```bash
# GitHub Actions
# Check for updates: https://github.com/actions

# GitLab CI
# Check for updates: https://docs.gitlab.com/ee/ci/yaml/
```

### Review Artifacts Retention

**GitHub**: 7 days (free tier limit: 500 MB)
**GitLab**: 1 week for reports, 30 days for pages

---

## 🔗 Resources

- [GitHub Actions Docs](https://docs.github.com/actions)
- [GitLab CI Docs](https://docs.gitlab.com/ee/ci/)
- [Playwright CI Guide](https://playwright.dev/docs/ci)
- [PHPUnit Documentation](https://phpunit.de/)
- [Jest Documentation](https://jestjs.io/)

---

## ✅ Checklist

Before pushing:

- [ ] All tests pass locally
- [ ] `.env.example` is up to date
- [ ] No secrets in code
- [ ] Migrations are compatible
- [ ] Seeders work in testing environment
- [ ] Coverage meets minimum requirements
