# Evalium Packages Architecture

Evalium organizes shared frontend code into two internal **yarn workspace** packages under `resources/ts/packages/`. These packages are resolved via path aliases in Vite, TypeScript and Vitest configurations.

---

## Overview

```
resources/ts/packages/
  ui/         @evalium/ui     Design system components
  utils/      @evalium/utils  Shared utilities
```

Both packages are:
- **Private** (`"private": true`) -- not published to npm
- **Yarn workspaces** -- registered in the root `package.json`
- **Alias-resolved** -- imported as `@evalium/ui` and `@evalium/utils` across the codebase

### Path Aliases

| Alias | Resolves To | Configured In |
|-------|-------------|---------------|
| `@/` | `resources/ts/` | vite.config.ts, tsconfig.json, vitest.config.ts |
| `@evalium/ui` | `resources/ts/packages/ui/` | vite.config.ts, tsconfig.json, vitest.config.ts |
| `@evalium/utils` | `resources/ts/packages/utils/` | vite.config.ts, tsconfig.json, vitest.config.ts |

---

## @evalium/ui

**Location:** `resources/ts/packages/ui/`

The design system package containing all reusable UI primitives. Each component lives in its own directory with implementation, tests, stories and documentation.

### Components

| Component | Description |
|-----------|-------------|
| `ActionGroup` | Grouped action buttons with dropdown |
| `AlertEntry` | Alert/notification display entry |
| `Badge` | Status badges with color variants |
| `Button` | Primary button with size, color and variant props |
| `Checkbox` | Styled checkbox input |
| `ChoiceEditor` | Question choice editor for assessment creation |
| `ConfirmationModal` | Pre-styled confirmation dialog (wraps Modal) |
| `Input` | Text input with label, error and helper text |
| `MarkdownEditor` | EasyMDE-based markdown editor |
| `MarkdownRenderer` | Renders markdown with KaTeX math and Prism syntax highlighting |
| `Modal` | Accessible modal dialog with animations, portal and scroll lock |
| `Section` | Collapsible content section |
| `Select` | Searchable dropdown select |
| `Stat` | Statistics display card |
| `Textarea` | Multi-line text input |
| `TextEntry` | Key-value text display |
| `Timeline` | Step timeline component |
| `Toggle` | Toggle switch input |
| `Tooltip` | Hover tooltip |
| `charts/` | Chart components (BarChart, DonutChart, RadarChart, ScoreDistribution, etc.) |

### Usage

```tsx
import { Button, Modal, Badge, Input } from '@evalium/ui';
import { BarChart, DonutChart } from '@evalium/ui/charts';
import type { ActionItem } from '@evalium/ui';
```

### Storybook

The package includes a Storybook configuration for visual component development:

```bash
cd resources/ts/packages/ui
npx storybook dev -p 6006
```

Stories are located alongside components (`*.stories.tsx`) and documentation files (`*.mdx`).

### Testing

Component tests use Vitest + React Testing Library:

```bash
yarn test:unit --filter=packages/ui
```

Test files follow the pattern `ComponentName.spec.tsx` within each component directory.

---

## @evalium/utils

**Location:** `resources/ts/packages/utils/`

Shared utility functions, helpers, constants and **TypeScript type definitions** used across the application. Organized into five modules.

### Modules

#### `types/`

All TypeScript interfaces and type definitions for the application, synced with Laravel models.

| Sub-module | Key Exports |
|------------|-------------|
| `types/` (root) | `PageProps`, `FlashMessages`, `CreatedUserCredentials` |
| `types/shared/` | `User`, `Role`, `Permission`, `GroupedPermissions`, `Level`, `Question`, `Choice`, `Answer` |
| `types/datatable` | `DataTableState`, `Column`, `PaginationType`, `SortDirection` |
| `types/route-context` | `AssessmentRouteContext` |
| `types/question-rendering` | Question rendering types |
| Model types | `Assessment`, `AcademicYear`, `ClassModel`, `ClassSubject`, `Enrollment`, `Subject`, `Semester`, `Notification`, `Grades`, `AssessmentAssignment` |

```tsx
import type { PageProps, Assessment, User, ClassModel } from '@evalium/utils/types';
import type { DataTableState, Column } from '@evalium/utils/types/datatable';
import type { AssessmentRouteContext } from '@evalium/utils/types/route-context';
```

#### `api/`

HTTP client and error handling utilities.

| Export | Description |
|--------|-------------|
| `api` | Configured Axios instance for API calls |
| `handleApiError` | Standardized API error handler |
| `isOnline` | Network connectivity check |

```tsx
import { api, handleApiError } from '@evalium/utils';
```

#### `formatting/`

Data formatting functions for display.

| Export | Description |
|--------|-------------|
| `formatTime` | Format duration (seconds to HH:MM:SS) |
| `formatDate` | Format date with locale support |
| `formatDateForInput` | Format date for HTML input fields |
| `formatPercentage` | Format number as percentage |
| `formatScore` | Format assessment score display |
| `formatGrade` | Format grade value |
| `formatNumber` | Locale-aware number formatting |
| `formatFileSize` | Human-readable file sizes |
| `capitalize` | Capitalize first letter |
| `truncateText` | Truncate with ellipsis |
| `toLocalDatetimeInput` | Convert to datetime-local input format |

```tsx
import { formatDate, formatScore, formatFileSize } from '@evalium/utils';
```

#### `helpers/`

Application-wide helper functions.

| Export | Description |
|--------|-------------|
| `hasPermission`, `hasAllPermissions`, `hasAnyPermission` | Permission checking utilities |
| `hasRole`, `hasAllRoles`, `hasAnyRole` | Role checking utilities |
| `PERMISSIONS`, `ROLES` | Permission and role constants |
| `navRoutes` | Sidebar navigation route definitions |
| `getAcademicYearStatus` | Determine academic year status |
| `translateKey` | Translation key resolver |
| `setupZiggy` | Ziggy route initialization |
| `buildDataTableUrl` | Build paginated/filtered URL for DataTable |
| `getSelectableItems`, `toggleAllPageSelection`, etc. | DataTable selection utilities |

```tsx
import { hasPermission, PERMISSIONS, navRoutes } from '@evalium/utils';
import { buildDataTableUrl } from '@evalium/utils/helpers/dataTableUtils';
```

#### `assessment/`

Assessment domain utilities, split into sub-modules.

| Sub-module | Key Exports |
|------------|-------------|
| `assessment/` (root) | `resolveAssignmentDisplayStatus`, `calculateTotalPoints`, `buildScoresMap`, `validateScore`, `getDeliveryModeDefaults` |
| `assessment/components/` | `TYPE_COLORS`, `getTypeColor`, `getBooleanDisplay`, `questionIndexLabel` |
| `assessment/take/` | `formatAssessmentTime`, `getTimeColorClass`, `formatAnswersForSubmission`, `shuffleQuestions`, `isFullscreenSupported` |

```tsx
import { calculateTotalPoints, resolveAssignmentDisplayStatus } from '@evalium/utils';
import { formatAssessmentTime, getTimeColorClass } from '@evalium/utils/assessment/take';
import { getTypeColor, questionIndexLabel } from '@evalium/utils/assessment/components';
```

### Testing

Utility tests use Vitest:

```bash
yarn test:unit --filter=packages/utils
```

Test files: `formatters.spec.ts`, `dataTableUtils.spec.ts`, `assignmentStatus.spec.ts`, `questionFactory.spec.ts`, `questionTypes.spec.ts`.

---

## Adding to a Package

### Adding a new UI component

1. Create a directory under `resources/ts/packages/ui/`:
   ```
   resources/ts/packages/ui/MyComponent/
     MyComponent.tsx
     MyComponent.spec.tsx
     MyComponent.stories.tsx
   ```

2. Export from `resources/ts/packages/ui/index.ts`:
   ```tsx
   export { default as MyComponent } from './MyComponent/MyComponent';
   ```

3. Import in application code:
   ```tsx
   import { MyComponent } from '@evalium/ui';
   ```

### Adding a new utility

1. Add the function to the appropriate module under `resources/ts/packages/utils/`:
   - `types/` -- TypeScript interfaces and type definitions
   - `api/` -- HTTP/network utilities
   - `formatting/` -- Display formatting
   - `helpers/` -- General helpers
   - `assessment/` -- Assessment domain logic

2. Export from the module's `index.ts`, which is re-exported by `resources/ts/packages/utils/index.ts`.

3. Import in application code:
   ```tsx
   import { myUtility } from '@evalium/utils';
   ```

---

## Configuration Files

The packages are integrated via these configuration files:

| File | Purpose |
|------|---------|
| `package.json` | `workspaces` array includes both package paths |
| `vite.config.ts` | `resolve.alias` maps `@evalium/ui` and `@evalium/utils` |
| `vitest.config.ts` | Same aliases for test resolution |
| `tsconfig.json` | `paths` mapping for TypeScript resolution |
| `resources/ts/packages/ui/.storybook/main.ts` | Storybook alias for `@evalium/ui` and `@evalium/utils` |
