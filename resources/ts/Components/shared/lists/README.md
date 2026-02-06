# Entity List Components - Infrastructure

This directory contains the infrastructure for creating consistent, role-based entity list components across the application.

## Overview

The **BaseEntityList** component provides a generic, reusable foundation for displaying paginated lists with support for:

- Role-based variations (admin, teacher, student)
- Conditional columns and actions based on roles
- Permission-based action visibility
- Standardized filtering and searching
- Consistent DataTable integration

## Architecture

### Core Components

#### 1. BaseEntityList<T>

Generic component that renders paginated lists with role-aware configurations.

**Props:**

- `data: PaginationType<T>` - Paginated data from backend
- `config: EntityListConfig<T>` - Configuration defining columns, actions, filters
- `variant?: 'admin' | 'teacher' | 'student'` - Role variant (default: 'admin')
- `showSearch?: boolean` - Enable search functionality (default: true)
- `searchPlaceholder?: string` - Custom search placeholder
- `emptyMessage?: string` - Custom empty state message

#### 2. EntityListConfig<T>

Configuration interface for defining how an entity should be displayed.

**Properties:**

- `entity: string` - Entity name (e.g., 'class', 'subject')
- `columns: ColumnConfig<T>[]` - Column definitions with role-based conditionals
- `actions?: ActionConfig<T>[]` - Available actions with permissions
- `filters?: FilterConfig[]` - Available filters
- `permissions?: PermissionConfig` - Permission mappings

### Backend Support

#### Traits

**PaginatesResources** (`app/Traits/PaginatesResources.php`)

- Standardizes pagination across all services
- Applies common filters (search, sort)
- Uses configuration from `config/app.php`

**HandlesIndexRequests** (`app/Http/Traits/HandlesIndexRequests.php`)

- Extracts and validates pagination parameters from requests
- Standardizes filter extraction
- Enforces max per page limits

#### API Resources

**Context-Aware Resources** (e.g., `app/Http/Resources/ClassResource.php`)

- Return different fields based on context (admin, teacher, student)
- Use `withContext(string $context)` method to set context
- Consistent data structures across roles

## Usage Examples

### Creating a Specific Entity List

```tsx
// resources/ts/Components/shared/lists/ClassList.tsx
import { BaseEntityList } from "./BaseEntityList";
import { ClassModel } from "@/types";
import type { EntityListConfig } from "./types/listConfig";

interface ClassListProps {
    data: PaginationType<ClassModel>;
    variant?: "admin" | "teacher" | "student";
}

export function ClassList({ data, variant = "admin" }: ClassListProps) {
    const config: EntityListConfig<ClassModel> = {
        entity: "class",

        columns: [
            {
                key: "name",
                labelKey: "common.name",
                render: (classItem) => (
                    <div>
                        <div className="font-medium">
                            {classItem.display_name}
                        </div>
                        <div className="text-sm text-gray-500">
                            {classItem.level?.name}
                        </div>
                    </div>
                ),
            },
            {
                key: "students",
                labelKey: "common.students",
                render: (classItem, currentVariant) => {
                    // Different rendering based on variant
                    if (currentVariant === "admin") {
                        return `${classItem.active_enrollments_count} / ${classItem.max_students}`;
                    }
                    return classItem.active_enrollments_count;
                },
            },
            {
                key: "capacity",
                labelKey: "admin_pages.classes.capacity",
                render: (classItem) => classItem.max_students,
                conditional: (v) => v === "admin", // Only show for admin
            },
        ],

        actions: [
            {
                labelKey: "common.view",
                onClick: (item) =>
                    router.visit(route(`${variant}.classes.show`, item.id)),
                permission: "view classes",
            },
            {
                labelKey: "common.edit",
                onClick: (item) =>
                    router.visit(route("admin.classes.edit", item.id)),
                permission: "update classes",
                color: "primary",
                conditional: (item, v) => v === "admin", // Only for admin
            },
        ],
    };

    return <BaseEntityList data={data} config={config} variant={variant} />;
}
```

### Using in Pages

```tsx
// resources/ts/Pages/Admin/Classes/Index.tsx
import { ClassList } from "@/Components/shared/lists/ClassList";

export default function ClassesIndex({
    classes,
}: {
    classes: PaginationType<ClassModel>;
}) {
    return (
        <AuthenticatedLayout>
            <ClassList data={classes} variant="admin" />
        </AuthenticatedLayout>
    );
}

// resources/ts/Pages/Teacher/Classes/Index.tsx
export default function TeacherClassesIndex({
    classes,
}: {
    classes: PaginationType<ClassModel>;
}) {
    return (
        <AuthenticatedLayout>
            <ClassList data={classes} variant="teacher" />
        </AuthenticatedLayout>
    );
}
```

### Backend Implementation

#### Service with PaginatesResources

```php
// app/Services/Admin/ClassQueryService.php
namespace App\Services\Admin;

use App\Traits\PaginatesResources;
use App\Models\ClassModel;

class ClassQueryService
{
    use PaginatesResources;

    public function getClassesForIndex(
        int $academicYearId,
        array $filters = [],
        ?int $perPage = null
    ): LengthAwarePaginator {
        $query = ClassModel::where('academic_year_id', $academicYearId)
            ->with(['level', 'academicYear'])
            ->withCount('activeEnrollments');

        return $this->paginateQuery($query, $filters, $perPage);
    }

    protected function applySearchFilter(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('display_name', 'like', "%{$search}%");
        });
    }

    protected function getEntityName(): string
    {
        return 'classes';
    }
}
```

#### Controller with HandlesIndexRequests

```php
// app/Http/Controllers/Admin/ClassController.php
namespace App\Http\Controllers\Admin;

use App\Http\Traits\HandlesIndexRequests;
use App\Http\Resources\ClassResource;

class ClassController extends Controller
{
    use HandlesIndexRequests;

    public function index(Request $request): Response
    {
        $selectedYearId = $this->getSelectedAcademicYearId($request);

        ['filters' => $filters, 'per_page' => $perPage] = $this->extractIndexParams(
            $request,
            ['search', 'level_id', 'status']
        );

        $classes = $this->classQueryService->getClassesForIndex(
            $selectedYearId,
            $filters,
            $perPage
        );

        return Inertia::render('Admin/Classes/Index', [
            'classes' => ClassResource::collection($classes)
                ->each(fn($r) => $r->withContext('admin')),
        ]);
    }
}
```

#### API Resource with Context

```php
// app/Http/Resources/ClassResource.php
namespace App\Http\Resources;

class ClassResource extends JsonResource
{
    protected string $context = 'full';

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'max_students' => $this->when(
                $this->shouldIncludeField('max_students'),
                $this->max_students
            ),
            // ... other fields
        ];
    }

    public function withContext(string $context): self
    {
        $this->context = $context;
        return $this;
    }

    protected function shouldIncludeField(string $field): bool
    {
        $fieldContextMap = [
            'max_students' => ['admin', 'full'],
        ];

        return !isset($fieldContextMap[$field]) ||
               in_array($this->context, $fieldContextMap[$field]);
    }
}
```

## Configuration

### Pagination (`config/app.php`)

```php
'pagination' => [
    'default_per_page' => env('PAGINATION_DEFAULT_PER_PAGE', 15),
    'max_per_page' => env('PAGINATION_MAX_PER_PAGE', 100),

    'entities' => [
        'classes' => ['default' => 15, 'max' => 50],
        'subjects' => ['default' => 20, 'max' => 100],
        // ... per-entity overrides
    ],
],
```

## Benefits

### Code Reduction

- **Before:** 80+ lines of DataTable config per page
- **After:** 3-5 lines using specific list component
- **Savings:** ~90% code reduction

### Consistency

- Standardized pagination across all entities
- Consistent filter handling
- Uniform permission checking
- Same UX patterns across roles

### Maintainability

- Single source of truth for list configurations
- Easy to add new columns or actions
- Changes propagate to all usages
- Testable in isolation

### Development Speed

- New entity lists in minutes, not hours
- Copy-paste patterns from existing implementations
- No need to rebuild DataTable configs
- Backend/frontend patterns align

## Next Steps

1. Create specific entity list components:
    - ClassList ✅ (example in this doc)
    - SubjectList
    - AssessmentList (refactor existing)
    - UserList
    - EnrollmentList
    - RoleList / LevelList

2. Migrate existing pages to use new components

3. Create comprehensive tests for BaseEntityList

4. Document migration patterns for new developers

## Files Structure

```
resources/ts/Components/shared/lists/
├── BaseEntityList.tsx          # Generic component
├── index.ts                    # Exports
├── types/
│   └── listConfig.ts          # Type definitions
├── ClassList.tsx              # Specific implementations
├── SubjectList.tsx
├── AssessmentList.tsx
└── ... (other entity lists)

app/Traits/
├── PaginatesResources.php     # Backend pagination trait

app/Http/Traits/
├── HandlesIndexRequests.php   # Controller request handling

app/Http/Resources/
├── ClassResource.php          # Context-aware resources
├── SubjectResource.php
└── ... (other resources)
```

## See Also

- [FRONTEND_BACKEND_DUPLICATION_ANALYSIS.md](../../../../docs/FRONTEND_BACKEND_DUPLICATION_ANALYSIS.md) - Complete analysis and refactoring plan
- [DataTable Component](../datatable/DataTable.tsx) - Underlying table component
- [Component Architecture Docs](../../../../docs/COMPONENT_ARCHITECTURE.md) - Overall component design
