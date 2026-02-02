# Types TypeScript - Architecture Modulaire

**Date**: 2 Février 2026  
**Objectif**: Organiser les types pour faciliter la migration MCD

---

## 📁 Structure

```
types/
├── index.ts                    # Export principal centralisé
├── api/                        # Types API (existant)
├── datatable.ts               # Types DataTable (existant)
├── role.ts                    # Types Role (existant)
└── models/                    # Nouveaux types organisés
    ├── mcd/                   # ✅ NOUVEAUX TYPES MCD
    │   ├── index.ts
    │   ├── academicYear.ts    # AcademicYear, AcademicYearFormData
    │   ├── semester.ts        # Semester, SemesterFormData
    │   ├── subject.ts         # Subject, SubjectFormData
    │   ├── class.ts           # ClassModel, ClassFormData, ClassStatistics
    │   ├── classSubject.ts    # ClassSubject, ClassSubjectFormData, ClassSubjectHistory
    │   ├── enrollment.ts      # Enrollment, EnrollmentFormData, TransferStudentFormData
    │   ├── assessment.ts      # Assessment, AssessmentFormData, AssessmentStatistics
    │   └── assessmentAssignment.ts  # AssessmentAssignment, SaveAnswersData, GradingData
    │
    ├── legacy/                # ❌ ANCIENS TYPES (à supprimer après migration)
    │   ├── index.ts
    │   ├── group.ts           # Group (sera remplacé par ClassModel)
    │   ├── exam.ts            # Exam (sera remplacé par Assessment)
    │   └── examAssignment.ts  # ExamAssignment (sera remplacé par AssessmentAssignment)
    │
    └── shared/                # ✅ TYPES PARTAGÉS (réutilisables)
        ├── index.ts
        ├── user.ts            # User, GroupWithPivot
        ├── role.ts            # Role
        ├── level.ts           # Level
        ├── question.ts        # Question, QuestionType
        ├── choice.ts          # Choice, QuestionResult
        └── answer.ts          # Answer, BackendAnswerData
```

---

## 🎯 Usage

### Import des types MCD (nouveaux)

```typescript
import type {
    AcademicYear,
    AcademicYearFormData,
    ClassModel,
    ClassFormData,
    Subject,
    Assessment,
} from "@/types";

// OU import direct depuis le module
import type { AcademicYear } from "@/types/models/mcd/academicYear";
```

### Import des types partagés

```typescript
import type { User, Level, Question, Choice, Answer } from "@/types";

// Ces types sont utilisables partout (legacy + MCD)
```

### Import des types legacy (anciens)

```typescript
import type { Exam, Group, ExamAssignment } from "@/types";

// ⚠️ À ÉVITER dans les nouveaux composants
// Ces types seront supprimés après migration
```

---

## 📦 Modules

### 🆕 MCD (Nouvelle Architecture)

#### AcademicYear (Année Académique)

```typescript
interface AcademicYear {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    is_current: boolean;

    semesters?: Semester[];
    classes?: ClassModel[];
}

interface AcademicYearFormData {
    name: string;
    start_date: string;
    end_date: string;
    is_current?: boolean;
}
```

#### Semester (Semestre)

```typescript
interface Semester {
    id: number;
    academic_year_id: number;
    name: string;
    order_number: 1 | 2;
    start_date: string;
    end_date: string;

    academic_year?: AcademicYear;
    class_subjects?: ClassSubject[];
}
```

#### Subject (Matière)

```typescript
interface Subject {
    id: number;
    level_id: number;
    name: string;
    code: string;
    description?: string;

    level?: Level;
    class_subjects?: ClassSubject[];
}
```

#### ClassModel (Classe)

```typescript
interface ClassModel {
    id: number;
    academic_year_id: number;
    level_id: number;
    name: string;
    max_students: number;

    academic_year?: AcademicYear;
    level?: Level;
    enrollments?: Enrollment[];
    class_subjects?: ClassSubject[];
    students?: User[];
}

interface ClassStatistics {
    total_students: number;
    active_students: number;
    withdrawn_students: number;
    subjects_count: number;
    assessments_count: number;
}
```

#### ClassSubject ⭐ (Enseignement - CENTRAL)

```typescript
interface ClassSubject {
    id: number;
    class_id: number;
    subject_id: number;
    teacher_id: number;
    semester_id?: number;
    coefficient: number;
    valid_from: string;
    valid_to?: string;

    class?: ClassModel;
    subject?: Subject;
    teacher?: User;
    semester?: Semester;
    assessments?: Assessment[];
}

interface ClassSubjectHistory {
    // Historisation changements teacher
    id: number;
    class_subject_id: number;
    teacher_id: number;
    valid_from: string;
    valid_to?: string;
    replaced_at?: string;
    replaced_by?: number;
}
```

#### Enrollment (Inscription)

```typescript
type EnrollmentStatus = "active" | "withdrawn" | "transferred" | "completed";

interface Enrollment {
    id: number;
    class_id: number;
    student_id: number;
    status: EnrollmentStatus;
    enrolled_date: string;
    left_date?: string;

    class?: ClassModel;
    student?: User;
}
```

#### Assessment (Évaluation)

```typescript
type AssessmentType = "devoir" | "examen" | "tp" | "controle" | "projet";

interface Assessment {
    id: number;
    class_subject_id: number;
    title: string;
    description?: string;
    type: AssessmentType;
    coefficient: number;
    duration: number;
    assessment_date: string;
    is_published: boolean;

    class_subject?: ClassSubject;
    teacher?: User;
    questions?: Question[];
    assignments?: AssessmentAssignment[];
}

interface AssessmentStatistics {
    total_assigned: number;
    in_progress: number;
    not_started: number;
    completed: number;
    average_score?: number;
    highest_score?: number;
    lowest_score?: number;
}
```

#### AssessmentAssignment (Assignation)

```typescript
type AssessmentAssignmentStatus =
    | "not_started"
    | "in_progress"
    | "submitted"
    | "graded";

interface AssessmentAssignment {
    id: number;
    assessment_id: number;
    student_id: number;
    assigned_at: string;
    started_at?: string;
    submitted_at?: string;
    score?: number;
    auto_score?: number;
    status: AssessmentAssignmentStatus;

    assessment?: Assessment;
    student?: User;
    answers?: Answer[];
}
```

---

### ♻️ Shared (Types Partagés)

Ces types sont utilisables dans **legacy** ET **MCD**:

- `User` - Utilisateur (avec roles, enrollments, etc.)
- `Role` - Rôle utilisateur
- `Level` - Niveau d'études (BTS, Licence, Master...)
- `Question` - Question (compatible exam + assessment)
- `Choice` - Choix de réponse
- `Answer` - Réponse étudiant

---

### ❌ Legacy (Anciens Types)

**⚠️ À SUPPRIMER après migration complète:**

- `Group` → Remplacé par `ClassModel`
- `Exam` → Remplacé par `Assessment`
- `ExamAssignment` → Remplacé par `AssessmentAssignment`

**Ne pas utiliser ces types dans les nouveaux composants!**

---

## 🔄 Migration Progressive

### Étape actuelle: Cohabitation

Les deux architectures coexistent:

```typescript
// ✅ Nouveaux composants utilisent MCD
import { AcademicYear, ClassModel, Assessment } from "@/types";

// ❌ Anciens composants utilisent legacy (temporairement)
import { Group, Exam, ExamAssignment } from "@/types";
```

### Après migration: Nettoyage

1. Supprimer `types/models/legacy/`
2. Mettre à jour tous les imports
3. Vérifier avec TypeScript compiler

---

## 📝 Conventions

### Nommage

- **Interface**: PascalCase (`AcademicYear`, `ClassModel`)
- **Type**: PascalCase (`AssessmentType`, `EnrollmentStatus`)
- **FormData**: Suffixe `FormData` (`AcademicYearFormData`)
- **Statistics**: Suffixe `Statistics` (`AssessmentStatistics`)

### Organisation

- 1 fichier = 1 entité principale
- Types associés dans le même fichier (FormData, Statistics, etc.)
- Export via `index.ts` de chaque module

### Relations

- Toujours typer les relations optionnelles (`?`)
- Inclure les compteurs (`_count`) si disponibles
- Typer les pivots explicitement (`GroupWithPivot`)

---

## 🎯 Avantages Structure Modulaire

1. **Séparation claire**: MCD / Legacy / Shared
2. **Nettoyage facile**: Supprimer `legacy/` après migration
3. **Auto-complétion**: Import suggestions dans IDE
4. **Maintenabilité**: 1 fichier = 1 responsabilité
5. **Évolutivité**: Ajouter nouveaux types sans polluer index.ts

---

**Document généré**: Types Architecture Complete ✅
