# Frontend Refactoring Plan - Examena

## 📊 Audit Initial (7 novembre 2025)

### État Actuel du Projet

**Structure des fichiers:**
- Total fichiers frontend: **160 fichiers**
- Fichiers TSX (composants): **112 fichiers**
- Fichiers de tests: **7 fichiers** ⚠️ (4.4% seulement)
- Tests E2E Playwright: **2 fichiers** (dans tests/e2e/)

**Arborescence actuelle:**
```
resources/ts/
├── Components/          # 25+ composants mélangés
│   ├── admin/          # Composants admin
│   ├── dashboard/      # Composants dashboard
│   ├── exam/           # Composants exam
│   ├── form/           # Composants forms
│   ├── Badge.tsx       # Composants UI de base
│   ├── Button.tsx
│   ├── Modal.tsx
│   └── ...
├── hooks/              # 8 hooks custom
│   ├── exam/          # Hooks exam
│   └── ...            # Hooks mélangés
├── Layouts/           # Layouts
├── Pages/             # Pages Inertia
│   ├── Admin/
│   ├── Auth/
│   ├── Dashboard/
│   ├── Exam/
│   └── Student/
├── types/             # Types TypeScript
└── utils/             # Utilitaires
```

### Problèmes Identifiés

#### 🔴 Critiques
1. **Couverture de tests insuffisante** (4.4%)
   - Seulement 7 tests pour 112 composants
   - Aucun test pour les hooks
   - Aucun test pour les utils
   - Tests E2E dans mauvais dossier (tests/e2e/ au lieu de resources/ts/e2e/)

2. **Configuration Jest cassée**
   - Erreur: `TS5103: Invalid value for '--ignoreDeprecations'`
   - Tests unitaires ne s'exécutent pas

3. **Structure non organisée**
   - Composants UI, features, et business mélangés
   - Pas de séparation claire des responsabilités
   - Fichiers de grande taille (>300 lignes probables)

#### 🟡 Moyens
4. **Types TypeScript incomplets**
   - Synchronisation types/ avec models Laravel à vérifier
   - Possibilité de types 'any' non contrôlés

5. **Hooks peu structurés**
   - Mélange entre hooks UI et hooks business
   - Pas de hooks pour appels API centralisés

6. **Utils non organisés**
   - Pas de structure par domaine
   - Documentation JSDoc manquante

#### 🟢 Mineurs
7. **Performance non optimisée**
   - Lazy loading à vérifier
   - Code splitting manuel probablement absent
   - Mémoïsation non systématique

8. **Accessibilité à auditer**
   - ARIA labels probablement incomplets
   - Navigation clavier à tester

---

## 📋 Plan de Refactoring Détaillé

### Phase 1: Correction Infrastructure & Configuration ⚡ (Priorité MAX)

**Objectif:** Réparer l'environnement de développement et tests

#### 1.1 Corriger la configuration Jest
- [ ] Fixer l'erreur `--ignoreDeprecations` dans jest.config.ts
- [ ] Vérifier tsconfig.json et tsconfig.test.json
- [ ] Tester que les tests s'exécutent: `npm run test:unit`
- [ ] Commit: `fix(jest): correct ignoreDeprecations config`

#### 1.2 Déplacer tests E2E vers frontend
- [ ] Créer `resources/ts/e2e/` 
- [ ] Déplacer `tests/e2e/*.spec.ts` → `resources/ts/e2e/`
- [ ] Créer structure:
  ```
  resources/ts/e2e/
  ├── auth.spec.ts
  ├── teacher/
  │   ├── exam-creation.spec.ts
  │   ├── exam-correction.spec.ts
  │   └── group-assignment.spec.ts
  ├── student/
  │   ├── exam-taking.spec.ts
  │   └── exam-security.spec.ts
  ├── admin/
  │   └── user-management.spec.ts
  └── fixtures/
      └── test-data.ts
  ```
- [ ] Mettre à jour playwright.config.ts: `testDir: './resources/ts/e2e'`
- [ ] Tester: `npm run test:e2e`
- [ ] Commit: `refactor(e2e): move Playwright tests to resources/ts/e2e`

#### 1.3 Configurer MSW (Mock Service Worker)
- [ ] Installer: `npm install -D msw@latest`
- [ ] Créer `resources/ts/__mocks__/handlers.ts`
- [ ] Créer `resources/ts/__mocks__/server.ts`
- [ ] Configurer dans jest.setup.ts
- [ ] Commit: `feat(test): setup MSW for API mocking`

**Durée estimée:** 1-2 jours

---

### Phase 2: Restructuration Architecture Composants 🏗️

**Objectif:** Organiser les composants selon Atomic Design

#### 2.1 Créer nouvelle structure
```
resources/ts/Components/
├── ui/                      # Atoms - Composants UI de base
│   ├── Button/
│   │   ├── Button.tsx
│   │   ├── Button.test.tsx
│   │   └── index.ts
│   ├── Input/
│   ├── Badge/
│   ├── Modal/
│   ├── Select/
│   ├── Textarea/
│   └── index.ts
├── forms/                   # Molecules - Composants forms composés
│   ├── FormField/
│   ├── SearchBar/
│   ├── DateRangePicker/
│   └── index.ts
├── features/                # Organisms - Composants métier
│   ├── exam/
│   │   ├── ExamCard/
│   │   ├── ExamForm/
│   │   ├── ExamSecurityMonitor/
│   │   └── QuestionEditor/
│   ├── student/
│   │   ├── StudentReview/
│   │   └── AnswerSheet/
│   ├── admin/
│   │   ├── UserTable/
│   │   └── RoleManager/
│   └── dashboard/
│       └── StatCard/
├── layout/                  # Layout components
│   ├── Sidebar/
│   ├── Navigation/
│   ├── Breadcrumb/
│   └── index.ts
└── shared/                  # Composants partagés complexes
    ├── DataTable/
    ├── Pagination/
    └── Toast/
```

#### 2.2 Migration progressive
- [ ] Étape 1: Migrer composants UI (atoms)
  - [ ] Button → ui/Button/
  - [ ] Badge → ui/Badge/
  - [ ] Modal → ui/Modal/
  - [ ] Select → ui/Select/
  - [ ] Textarea → ui/Textarea/
  - Commit: `refactor(components): create ui/ layer with atoms`

- [ ] Étape 2: Migrer composants forms (molecules)
  - [ ] form/* → forms/
  - Commit: `refactor(components): create forms/ layer`

- [ ] Étape 3: Migrer composants features (organisms)
  - [ ] exam/* → features/exam/
  - [ ] admin/* → features/admin/
  - [ ] dashboard/* → features/dashboard/
  - Commit: `refactor(components): create features/ layer`

- [ ] Étape 4: Migrer layout components
  - [ ] Sidebar → layout/Sidebar/
  - [ ] Navigation → layout/Navigation/
  - [ ] Breadcrumb → layout/Breadcrumb/
  - Commit: `refactor(components): create layout/ layer`

- [ ] Étape 5: Nettoyer ancienne structure
  - [ ] Supprimer dossiers vides
  - [ ] Mettre à jour tous les imports
  - [ ] Vérifier compilation TypeScript
  - Commit: `refactor(components): cleanup old structure`

**Durée estimée:** 3-4 jours

---

### Phase 3: Refactoring Hooks 🎣

**Objectif:** Organiser hooks par responsabilité

#### 3.1 Créer nouvelle structure
```
resources/ts/hooks/
├── api/                     # Hooks pour appels API
│   ├── useExamApi.ts
│   ├── useUserApi.ts
│   ├── useGroupApi.ts
│   └── index.ts
├── features/                # Hooks métier par feature
│   ├── exam/
│   │   ├── useExamForm.ts
│   │   ├── useExamSecurity.ts
│   │   ├── useExamTimer.ts
│   │   └── useQuestionManager.ts
│   ├── student/
│   │   ├── useExamSession.ts
│   │   └── useAnswerSubmission.ts
│   └── admin/
│       ├── useUserManagement.ts
│       └── useRoleManagement.ts
├── ui/                      # Hooks pour état UI
│   ├── useModal.ts
│   ├── useToast.ts
│   ├── useConfirmation.ts
│   └── usePagination.ts
├── forms/                   # Hooks pour forms
│   ├── useFormValidation.ts
│   └── useFormPersist.ts
└── index.ts                 # Export barrel
```

#### 3.2 Migration des hooks
- [ ] Analyser hooks existants:
  - [ ] useCreateExam.ts → features/exam/useExamForm.ts
  - [ ] useEditExam.ts → features/exam/useExamForm.ts
  - [ ] useDeleteHistory.ts → ui/useConfirmation.ts
  - [ ] useQuestionsManager.ts → features/exam/useQuestionManager.ts
  - [ ] useForm.ts → forms/useFormValidation.ts
  - [ ] useRoleForm.ts → features/admin/useRoleManagement.ts

- [ ] Créer nouveaux hooks API:
  - [ ] useExamApi (GET, POST, PUT, DELETE exams)
  - [ ] useUserApi (user CRUD)
  - [ ] useGroupApi (group management)

- [ ] Créer hooks UI manquants:
  - [ ] useModal (open, close, data)
  - [ ] useToast (show, hide, queue)
  - [ ] usePagination (page, perPage, total)

- [ ] Ajouter types stricts partout
- [ ] Documenter avec JSDoc
- [ ] Commit par catégorie:
  - `refactor(hooks): create api layer`
  - `refactor(hooks): create features layer`
  - `refactor(hooks): create ui layer`

**Durée estimée:** 2-3 jours

---

### Phase 4: Organisation Utils 🛠️

**Objectif:** Structurer utilitaires par domaine

#### 4.1 Créer nouvelle structure
```
resources/ts/utils/
├── formatting/              # Formatage de données
│   ├── date.ts             # formatDate, parseDate, relativeDuration
│   ├── number.ts           # formatScore, formatPercentage
│   ├── text.ts             # truncate, capitalize, slugify
│   └── index.ts
├── validation/              # Validation
│   ├── exam.ts             # validateExam, validateQuestion
│   ├── form.ts             # validateEmail, validatePassword
│   └── index.ts
├── api/                     # Helpers API
│   ├── client.ts           # Axios instance configuré
│   ├── errorHandler.ts     # handleApiError
│   └── index.ts
├── exam/                    # Logique métier exam
│   ├── scoring.ts          # calculateScore, calculateTotalPoints
│   ├── timer.ts            # getRemainingTime, formatDuration
│   ├── security.ts         # detectViolation, logSecurityEvent
│   └── index.ts
├── storage/                 # LocalStorage/SessionStorage
│   ├── examSession.ts
│   └── preferences.ts
└── index.ts
```

#### 4.2 Migration et création
- [ ] Auditer utils existants
- [ ] Créer utils/formatting/
  - [ ] Extraire formatters de components
  - [ ] Ajouter tests unitaires
- [ ] Créer utils/validation/
  - [ ] Extraire validateurs
  - [ ] Ajouter tests unitaires
- [ ] Créer utils/api/
  - [ ] Configurer axios client
  - [ ] Error handling centralisé
- [ ] Créer utils/exam/
  - [ ] Logique scoring
  - [ ] Timer utilities
  - [ ] Security detection
- [ ] JSDoc pour toutes fonctions
- [ ] Tests unitaires (100% couverture)
- [ ] Commit: `refactor(utils): organize by domain`

**Durée estimée:** 2 jours

---

### Phase 5: Renforcement Types TypeScript 📘

**Objectif:** Typage strict et synchronisation avec backend

#### 5.1 Synchroniser avec models Laravel
- [ ] Créer script de génération types:
  ```bash
  php artisan typescript:generate
  ```
- [ ] Générer types depuis models:
  - [ ] User, Role, Permission
  - [ ] Exam, Question, Choice, Answer
  - [ ] ExamAssignment
  - [ ] Group, Level
- [ ] Commit: `feat(types): sync with Laravel models`

#### 5.2 Créer types API
```typescript
// types/api/exam.ts
export interface GetExamsResponse {
  data: Exam[];
  meta: PaginationMeta;
}

export interface CreateExamRequest {
  title: string;
  description: string;
  questions: QuestionInput[];
}
```
- [ ] types/api/exam.ts
- [ ] types/api/user.ts
- [ ] types/api/group.ts
- [ ] types/api/assignment.ts
- [ ] Commit: `feat(types): add API types`

#### 5.3 Installer Zod pour validation runtime
- [ ] `npm install zod`
- [ ] Créer schemas: `schemas/exam.ts`
- [ ] Valider réponses API
- [ ] Commit: `feat(validation): add Zod runtime validation`

#### 5.4 Éliminer tous les 'any'
- [ ] Chercher: `grep -r "any" resources/ts/`
- [ ] Remplacer par types stricts
- [ ] Activer `noImplicitAny` strict
- [ ] Commit: `refactor(types): eliminate all 'any' types`

**Durée estimée:** 2-3 jours

---

### Phase 6: Extension Tests Jest (Unit) 🧪

**Objectif:** Atteindre 80% de couverture

#### 6.1 Tester composants UI (atoms)
- [ ] ui/Button/Button.test.tsx
- [ ] ui/Badge/Badge.test.tsx
- [ ] ui/Modal/Modal.test.tsx
- [ ] ui/Input/Input.test.tsx
- [ ] ui/Select/Select.test.tsx
- **Couverture cible:** 100%

#### 6.2 Tester composants forms
- [ ] forms/FormField/FormField.test.tsx
- [ ] forms/SearchBar/SearchBar.test.tsx
- **Couverture cible:** 90%

#### 6.3 Tester hooks
- [ ] hooks/api/useExamApi.test.ts
- [ ] hooks/features/exam/useExamForm.test.ts
- [ ] hooks/ui/useModal.test.ts
- [ ] hooks/ui/useToast.test.ts
- **Couverture cible:** 85%

#### 6.4 Tester utils
- [ ] utils/formatting/date.test.ts
- [ ] utils/formatting/number.test.ts
- [ ] utils/validation/exam.test.ts
- [ ] utils/exam/scoring.test.ts
- **Couverture cible:** 100%

#### 6.5 Tester features complexes
- [ ] features/exam/ExamForm/ExamForm.test.tsx
- [ ] features/exam/QuestionEditor/QuestionEditor.test.tsx
- [ ] features/student/AnswerSheet/AnswerSheet.test.tsx
- **Couverture cible:** 70%

#### 6.6 Configuration snapshot tests
- [ ] Activer snapshots pour UI components
- [ ] Commit réguliers: `test(X): add unit tests`

**Durée estimée:** 5-7 jours

---

### Phase 7: Tests E2E Playwright Complets 🎭

**Objectif:** Couvrir workflows critiques

#### 7.1 Structure tests E2E
```
resources/ts/e2e/
├── auth/
│   ├── login.spec.ts
│   ├── logout.spec.ts
│   └── profile.spec.ts
├── teacher/
│   ├── exam-creation.spec.ts
│   ├── exam-edition.spec.ts
│   ├── exam-assignment.spec.ts
│   ├── exam-correction.spec.ts
│   └── exam-deletion.spec.ts
├── student/
│   ├── exam-taking.spec.ts
│   ├── exam-submission.spec.ts
│   ├── exam-results.spec.ts
│   └── exam-security.spec.ts
├── admin/
│   ├── user-management.spec.ts
│   ├── group-management.spec.ts
│   └── role-assignment.spec.ts
├── fixtures/
│   ├── users.ts
│   ├── exams.ts
│   └── groups.ts
└── helpers/
    ├── auth.ts
    └── navigation.ts
```

#### 7.2 Scénarios critiques
- [ ] **Auth:**
  - [ ] Login réussi
  - [ ] Login échec
  - [ ] Logout
  - [ ] Redirection après login

- [ ] **Teacher - Exam Lifecycle:**
  - [ ] Créer exam avec questions
  - [ ] Éditer exam
  - [ ] Assigner à groupe
  - [ ] Voir soumissions
  - [ ] Corriger copies
  - [ ] Supprimer exam

- [ ] **Student - Exam Taking:**
  - [ ] Liste exams disponibles
  - [ ] Démarrer exam
  - [ ] Répondre questions (choice, text)
  - [ ] Timer fonctionnel
  - [ ] Sauvegarde auto-réponses
  - [ ] Soumettre exam
  - [ ] Voir résultats

- [ ] **Student - Security:**
  - [ ] Détection sortie fullscreen
  - [ ] Détection changement tab
  - [ ] Soumission forcée sur violation
  - [ ] Log violations

- [ ] **Admin:**
  - [ ] Créer utilisateur
  - [ ] Assigner rôle
  - [ ] Créer groupe
  - [ ] Assigner étudiants à groupe

#### 7.3 Configuration parallélisation
- [ ] Configurer workers: `workers: 4`
- [ ] Tests indépendants (pas de dépendances)
- [ ] Fixtures isolés
- [ ] Commit: `test(e2e): add complete E2E test suite`

**Durée estimée:** 7-10 jours

---

### Phase 8: Optimisations Performance ⚡

**Objectif:** Améliorer temps de chargement et réactivité

#### 8.1 Lazy loading & Code splitting
- [ ] Pages:
  ```typescript
  const AdminUsersPage = lazy(() => import('@/Pages/Admin/Users/Index'));
  ```
- [ ] Routes chunking automatique
- [ ] Preload routes principales
- [ ] Commit: `perf: add lazy loading for pages`

#### 8.2 Mémoïsation
- [ ] Audit composants lourds avec React DevTools Profiler
- [ ] Ajouter `React.memo` pour composants purs
- [ ] `useMemo` pour calculs coûteux
- [ ] `useCallback` pour fonctions passées en props
- [ ] Commit: `perf: add memoization to heavy components`

#### 8.3 Virtual scrolling
- [ ] Installer `react-virtual`
- [ ] Appliquer sur DataTable
- [ ] Appliquer sur listes longues (>100 items)
- [ ] Commit: `perf: add virtual scrolling for large lists`

#### 8.4 Debounce & Throttle
- [ ] SearchBar: debounce 300ms
- [ ] Scroll events: throttle 100ms
- [ ] Resize events: throttle 200ms
- [ ] Commit: `perf: add debounce/throttle for events`

#### 8.5 Optimistic UI
- [ ] Soumission forms: update UI immédiat
- [ ] Rollback sur erreur
- [ ] Toast confirmation
- [ ] Commit: `perf: add optimistic UI updates`

#### 8.6 Bundle analysis
- [ ] `npm run build`
- [ ] Analyser avec `vite-plugin-visualizer`
- [ ] Identifier gros chunks
- [ ] Split vendor chunks
- [ ] Commit: `perf: optimize bundle splitting`

**Durée estimée:** 3-4 jours

---

### Phase 9: Accessibilité (a11y) ♿

**Objectif:** WCAG 2.1 AA compliance

#### 9.1 ARIA labels
- [ ] Tous les buttons ont aria-label
- [ ] Tous les inputs ont labels associés
- [ ] Modals ont aria-modal, aria-labelledby
- [ ] Toasts ont role="alert"
- [ ] Commit: `a11y: add ARIA labels`

#### 9.2 Navigation clavier
- [ ] Tab order logique
- [ ] Escape ferme modals
- [ ] Enter soumet forms
- [ ] Arrow keys pour listes
- [ ] Focus visible (outline)
- [ ] Commit: `a11y: improve keyboard navigation`

#### 9.3 Screen readers
- [ ] Tester avec NVDA/JAWS
- [ ] Announcements pour actions
- [ ] Skip links
- [ ] Commit: `a11y: screen reader support`

#### 9.4 Contraste couleurs
- [ ] Vérifier tous les textes (WCAG AA: 4.5:1)
- [ ] Buttons disabled visibles
- [ ] Links soulignés ou couleur distincte
- [ ] Commit: `a11y: improve color contrast`

#### 9.5 Tests automatiques
- [ ] Installer `@axe-core/react`
- [ ] Ajouter tests a11y à tous components
- [ ] CI/CD: bloquer si violations
- [ ] Commit: `test(a11y): add axe-core tests`

**Durée estimée:** 3-4 jours

---

### Phase 10: Documentation & Tooling 📚

**Objectif:** Documentation complète et outils dev

#### 10.1 Storybook
- [ ] Installer: `npx storybook@latest init`
- [ ] Stories pour tous UI components
- [ ] Stories pour forms
- [ ] Controls interactifs
- [ ] Dark mode toggle
- [ ] Commit: `docs: add Storybook`

#### 10.2 README Frontend
- [ ] Créer `resources/ts/README.md`:
  - Architecture
  - Structure dossiers
  - Conventions nommage
  - Comment ajouter composant
  - Comment ajouter test
  - Patterns à suivre
- [ ] Commit: `docs: add frontend README`

#### 10.3 Scripts npm optimisés
```json
{
  "scripts": {
    "dev": "vite",
    "build": "tsc && vite build",
    "test": "npm run test:unit && npm run test:e2e",
    "test:unit": "jest",
    "test:unit:watch": "jest --watch",
    "test:unit:coverage": "jest --coverage",
    "test:e2e": "playwright test",
    "test:e2e:ui": "playwright test --ui",
    "test:e2e:debug": "playwright test --debug",
    "test:a11y": "jest --testMatch='**/*.a11y.test.tsx'",
    "lint": "eslint resources/ts --ext .ts,.tsx",
    "lint:fix": "eslint resources/ts --ext .ts,.tsx --fix",
    "type-check": "tsc --noEmit",
    "storybook": "storybook dev -p 6006",
    "build-storybook": "storybook build",
    "analyze": "vite-bundle-visualizer"
  }
}
```
- [ ] Commit: `chore: optimize npm scripts`

#### 10.4 Pre-commit hooks
- [ ] Installer husky: `npm install -D husky`
- [ ] Installer lint-staged
- [ ] `.husky/pre-commit`:
  ```bash
  npx lint-staged
  npm run type-check
  npm run test:unit -- --bail --findRelatedTests
  ```
- [ ] Commit: `chore: add pre-commit hooks`

#### 10.5 GitHub Actions Frontend
- [ ] `.github/workflows/frontend-tests.yml`:
  ```yaml
  name: Frontend Tests
  on: [push, pull_request]
  jobs:
    test:
      runs-on: ubuntu-latest
      steps:
        - uses: actions/checkout@v3
        - uses: actions/setup-node@v3
        - run: npm ci
        - run: npm run lint
        - run: npm run type-check
        - run: npm run test:unit:coverage
        - run: npx playwright install --with-deps
        - run: npm run test:e2e
  ```
- [ ] Commit: `ci: add frontend GitHub Actions`

**Durée estimée:** 3-4 jours

---

## 📈 Métriques de Succès

### Avant Refactoring
- ❌ Couverture tests: **4.4%** (7 tests)
- ❌ Tests E2E: 2 fichiers (mal placés)
- ❌ Tests Jest: cassés
- ❌ Structure: non organisée
- ❌ Types: incomplets
- ❌ Performance: non optimisée
- ❌ Accessibilité: non testée

### Après Refactoring (Objectifs)
- ✅ Couverture tests: **≥80%**
- ✅ Tests E2E: ≥20 scénarios complets
- ✅ Tests Jest: 100% fonctionnels
- ✅ Structure: Atomic Design appliqué
- ✅ Types: 100% stricts (zéro 'any')
- ✅ Performance: Lazy loading + memoization
- ✅ Accessibilité: WCAG AA compliance
- ✅ Documentation: Storybook + README
- ✅ CI/CD: Tests automatisés

---

## 🗓️ Timeline Globale

| Phase | Durée | Sprint |
|-------|-------|--------|
| 1. Infrastructure | 1-2 jours | Sprint 1 |
| 2. Architecture Composants | 3-4 jours | Sprint 1-2 |
| 3. Hooks | 2-3 jours | Sprint 2 |
| 4. Utils | 2 jours | Sprint 2 |
| 5. Types | 2-3 jours | Sprint 3 |
| 6. Tests Jest | 5-7 jours | Sprint 3-4 |
| 7. Tests E2E | 7-10 jours | Sprint 4-5 |
| 8. Performance | 3-4 jours | Sprint 5 |
| 9. Accessibilité | 3-4 jours | Sprint 6 |
| 10. Documentation | 3-4 jours | Sprint 6 |

**Total estimé:** 31-45 jours (6-9 semaines)

---

## 🚀 Ordre d'Exécution Recommandé

1. ⚡ **Phase 1** (URGENT) - Corriger config Jest + déplacer E2E
2. 🏗️ **Phase 2** - Restructurer composants
3. 🎣 **Phase 3** - Refactorer hooks
4. 🛠️ **Phase 4** - Organiser utils
5. 📘 **Phase 5** - Renforcer types
6. 🧪 **Phase 6** - Tests Jest complets
7. 🎭 **Phase 7** - Tests E2E complets
8. ⚡ **Phase 8** - Optimisations performance
9. ♿ **Phase 9** - Accessibilité
10. 📚 **Phase 10** - Documentation

---

## 📝 Notes Importantes

### Conventions à Respecter

**Nommage:**
- Composants: PascalCase (`ExamCard.tsx`)
- Hooks: camelCase avec 'use' (`useExamForm.ts`)
- Utils: camelCase (`formatDate.ts`)
- Types: PascalCase (`ExamFormData`)
- Constantes: UPPER_SNAKE_CASE (`API_BASE_URL`)

**Organisation fichiers:**
```
ComponentName/
├── ComponentName.tsx
├── ComponentName.test.tsx
├── ComponentName.stories.tsx
├── index.ts
└── styles.module.css (si besoin)
```

**Imports:**
- Utiliser `@/` pour paths absolus
- Grouper imports: React → librairies → @/ → ./
- Index barrel files pour exports propres

**Tests:**
- Nommer: `*.test.tsx` (unit), `*.spec.ts` (E2E)
- AAA pattern: Arrange, Act, Assert
- Un describe par composant/fonction
- Tests isolés et indépendants

---

## 🔄 Processus Itératif

Chaque phase suit ce cycle:
1. 📋 **Plan** - Définir tâches précises
2. 💻 **Code** - Implémenter avec tests
3. 🧪 **Test** - Vérifier fonctionnement
4. 📝 **Review** - Relire code
5. ✅ **Commit** - Commit atomique
6. 🔁 **Repeat** - Itérer

**Commits atomiques:**
- Un commit = une modification logique
- Message clair: `type(scope): description`
- Types: feat, fix, refactor, test, docs, perf, style, chore

---

## 📞 Support & Questions

Pour toute question sur ce plan:
1. Vérifier ce document d'abord
2. Consulter copilot-instructions.md
3. Demander clarification si besoin

**Dernière mise à jour:** 7 novembre 2025
