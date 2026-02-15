# E2E Tests for Evalium

## Vue d'ensemble

Ce système configure automatiquement l'environnement E2E :
1. **globalSetup** : Crée la DB de test, exécute les seeders, lance le serveur Laravel
2. **Tests** : S'exécutent avec la base de données et le serveur prêts
3. **globalTeardown** : Arrête le serveur, nettoie la base de données

## Quick Start

### Configuration initiale

```bash
# 1. Installer les dépendances E2E
cd e2e && yarn install && cd ..

# 2. Créer la variable d'environnement (optionnel)
echo "DB_E2E_DATABASE=evalium_e2e_test" >> .env

# 3. Lancer tous les tests (setup automatique inclus)
yarn test:e2e
```

Le `globalSetup` s'exécute automatiquement et :
- Crée la base de données `evalium_e2e_test`
- Exécute les migrations et seeders
- Lance le serveur Laravel sur le port 8000
- Prépare les fichiers d'authentification

### Exécuter les tests

```bash
# Tous les tests (avec setup/teardown automatique)
yarn test:e2e

# Mode UI interactif (⚠️ nécessite setup manuel)
yarn test:e2e:ui
# Avant le mode UI, lancez : php artisan e2e:setup

# Tests par rôle
yarn test:e2e:admin
yarn test:e2e:teacher
yarn test:e2e:student

# Debug
yarn test:e2e:debug

# Voir le rapport
yarn test:e2e:report
```

## Configuration

### 1. Base de données E2E

Une connexion dédiée `e2e_testing` utilise une base séparée (`evalium_e2e_test`) configurée dans [config/database.php](../config/database.php).

**Ajoutez dans `.env`** (optionnel, valeur par défaut fournie) :
```dotenv
DB_E2E_DATABASE=evalium_e2e_test
```

### 2. Commandes Artisan

**`php artisan e2e:setup`**
- Supprime et recrée la base `evalium_e2e_test`
- Exécute `migrate:fresh` sur la connexion `e2e_testing`
- Exécute les seeders (DatabaseSeeder par défaut)

**`php artisan e2e:teardown`**
- Supprime complètement la base `evalium_e2e_test`

### 3. Scripts Playwright

**global-setup.ts**
- Exécuté UNE FOIS avant tous les tests
- Lance `php artisan e2e:setup`
- Démarre le serveur Laravel (port 8000)
- Crée le dossier `playwright/.auth`

**global-teardown.ts**
- Exécuté UNE FOIS après tous les tests
- Arrête le serveur Laravel
- Lance `php artisan e2e:teardown`

---

## Personnalisation

### Utiliser des seeders spécifiques

Modifiez [E2ESetupCommand.php](../app/Console/Commands/E2ESetupCommand.php) :

```php
Artisan::call('db:seed', [
    '--database' => 'e2e_testing',
    '--class' => 'E2ETestSeeder', // Votre seeder personnalisé
    '--force' => true,
]);
```

### Créer un seeder E2E dédié

```bash
php artisan make:seeder E2ETestSeeder
```

```php
class E2ETestSeeder extends Seeder
{
    public function run(): void
    {
        // Utilisateurs de test
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ])->assignRole('admin');

        User::factory()->create([
            'email' => 'teacher@example.com',
            'password' => Hash::make('password123'),
        ])->assignRole('teacher');

        User::factory()->create([
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
        ])->assignRole('student');

        // Groupes, examens, etc.
        Group::factory(5)->create();
        Exam::factory(10)->create();
    }
}
```

### Modifier le port du serveur

Le port par défaut pour les tests E2E est **8001** (évite les conflits avec le serveur de développement sur 8000).

Pour utiliser un port différent, définissez la variable `E2E_PORT` :

```bash
# Dans votre terminal
E2E_PORT=9000 yarn test:e2e

# Ou créez un fichier .env.e2e
echo "E2E_PORT=9000" >> .env.e2e
```

---

## Test Structure

```
e2e/
├── global-setup.ts             # Setup global (DB + serveur)
├── global-teardown.ts          # Nettoyage global
├── playwright.config.ts        # Configuration Playwright
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
└── student/                    # Student role tests
    └── dashboard.spec.ts
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

---

## Troubleshooting

### Le serveur ne démarre pas
- Vérifiez que le port 8000 n'est pas déjà utilisé : `netstat -ano | findstr :8000`
- Augmentez le délai d'attente dans `global-setup.ts` : `setTimeout(resolve, 5000)`

### La base de données n'est pas créée
- Vérifiez les credentials MySQL dans `.env`
- Assurez-vous que l'utilisateur a les droits `CREATE DATABASE`
- Testez manuellement : `php artisan e2e:setup`

### Les tests échouent en mode headless mais pas en UI
- Le globalSetup ne s'exécute pas en mode UI
- Lancez `php artisan e2e:setup` manuellement avant `yarn test:e2e:ui`

### Le serveur reste actif après les tests
- Vérifiez le fichier `.laravel-server.pid` dans `e2e/`
- Tuez manuellement : `taskkill /F /IM php.exe` (Windows)

---

## CI/CD Integration

Dans votre workflow GitHub Actions :

```yaml
- name: Setup MySQL
  run: |
    sudo systemctl start mysql
    mysql -e "CREATE USER 'test'@'localhost' IDENTIFIED BY 'test';"
    mysql -e "GRANT ALL PRIVILEGES ON *.* TO 'test'@'localhost';"

- name: Run E2E Tests
  run: yarn test:e2e
  env:
    DB_USERNAME: test
    DB_PASSWORD: test
    DB_E2E_DATABASE: evalium_e2e_test_ci

- name: Upload Playwright Report
  if: always()
  uses: actions/upload-artifact@v4
  with:
    name: playwright-report
    path: playwright-report/
```

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
yarn test:e2e
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
yarn run test:e2e:ui
```

### View report
```bash
yarn run test:e2e:report
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
