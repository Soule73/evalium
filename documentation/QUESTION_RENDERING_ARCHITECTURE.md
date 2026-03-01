# Question Rendering Architecture

> Status: **Implemented** — `develop/v1.1-improvements`
> Last updated: 24 February 2026

---

## Overview

Question rendering uses a **Strategy + Context** pattern to handle all rendering modes (take, review, grade, results, preview) through a single unified component tree, replacing the previous dual-tree approach that duplicated logic across interactive and read-only components.

---

## File Structure

```
resources/ts/
├── types/
│   └── question-rendering.ts          ← QuestionRenderMode, QuestionRenderConfig, buildRenderConfig
│
└── Components/features/assessment/
    │
    ├── question/                       ← Unified rendering system
    │   ├── QuestionContext.tsx         ← React Context + QuestionProvider + useQuestionContext
    │   ├── QuestionCard.tsx            ← Orchestrator: reads context, builds QuestionResult, dispatches to renderer
    │   ├── QuestionHeader.tsx          ← Unified header (index, correct/incorrect icon, score display, type badge)
    │   ├── index.ts                    ← Public exports
    │   │
    │   ├── renderers/
    │   │   ├── ChoiceRenderer.tsx      ← multiple / one_choice / boolean (shared, D2)
    │   │   ├── TextRenderer.tsx        ← text (MarkdownEditor in take, read-only elsewhere)
    │   │   ├── FileRenderer.tsx        ← file (FileUploadZone in take, FileList elsewhere)
    │   │   └── index.ts               ← QuestionRenderers registry (Record<QuestionType, FC>)
    │   │
    │   └── score/
    │       └── ScoreInput.tsx          ← Grade mode score + feedback fields (replaces renderScoreInput render prop)
    │
    └── QuestionRenderer.tsx            ← QuestionList component (wrapper, API: questions + title?)
```

---

## Core Types

```typescript
// types/question-rendering.ts

type QuestionRenderMode = 'take' | 'review' | 'grade' | 'results' | 'preview';
type QuestionViewerRole = 'student' | 'teacher' | 'admin';

interface QuestionRenderConfig {
    mode: QuestionRenderMode;
    role: QuestionViewerRole;
    isInteractive: boolean;
    showCorrectAnswers: boolean;
    showScoreInput: boolean;
    suppressNoAnswerWarning: boolean;
    labelVariant: 'student' | 'teacher';
    canEditScores: boolean;
}
```

`buildRenderConfig(mode, role, options?)` derives all flags from the mode and role — no scattered boolean props.

---

## Mode Reference

| Mode | `isInteractive` | `showCorrectAnswers` | `showScoreInput` | `suppressNoAnswerWarning` |
|---|:---:|:---:|:---:|:---:|
| `take` | ✓ | — | — | — |
| `review` | — | ✓ | — | — |
| `grade` | — | ✓ | ✓ | — |
| `results` | — | server-side override | — | — |
| `preview` | — | — | — | ✓ |

---

## Component Roles

### `QuestionProvider`

Wraps a group of questions with rendering context. Placed **once per page**, not per card.

```tsx
<QuestionProvider
    mode="grade"
    role="teacher"
    userAnswers={userAnswers}
    scoreOverrides={editableScores}
    onScoreChange={(id, value) => setEditableScores(prev => ({ ...prev, [id]: value }))}
    feedbackOverrides={feedbacks}
    onFeedbackChange={handleFeedbackChange}
    canEditScores={canGradeAssessments}
>
    <QuestionList title={t('...')} questions={assessment.questions ?? []} />
</QuestionProvider>
```

### `QuestionCard`

Orchestrator consumed by `QuestionList`. Responsible for:
1. Reading config and answer data from context
2. Computing `QuestionResult` via `buildQuestionResult(question, userAnswers[question.id], overrides)`
3. Dispatching to the correct renderer via the `QuestionRenderers` registry
4. Rendering `QuestionHeader`, optional feedback `AlertEntry`, and `ScoreInput`

Memoized with `React.memo` — re-renders only when its own question changes.

### `QuestionRenderers` Registry

```typescript
// renderers/index.ts
export const QuestionRenderers: Record<QuestionType, React.FC<QuestionRendererProps>> = {
    multiple:   ChoiceRenderer,
    one_choice: ChoiceRenderer,
    boolean:    ChoiceRenderer,
    text:       TextRenderer,
    file:       FileRenderer,
};
```

**Adding a new question type**: create a renderer file, add one entry to this registry. No other file needs modification.

### `QuestionList`

Thin wrapper that renders a list of `QuestionCard` components. Public API intentionally minimal:

```tsx
<QuestionList questions={questions} title="Optional section title" />
```

The `QuestionProvider` must be placed by the parent page.

---

## Renderer Contract

Each renderer receives `question` and `result`, and reads its config from context via `useQuestionContext()`:

```typescript
interface QuestionRendererProps {
    question: Question;
    result: QuestionResult;
}
```

- **`isInteractive`** — render interactive input (take mode) or read-only output
- **`isDisabled`** — disable input without leaving take mode (Work page: submitted assignment)
- **`labelVariant`** — `'student'` → "Your answer", `'teacher'` → "Student answer"
- **`suppressNoAnswerWarning`** — omit the "no answer" alert (preview mode)

---

## Page Usage

### Take (student answering)

```tsx
<QuestionProvider key={currentQ.id} mode="take" role="student"
    answers={answers} onAnswerChange={handleAnswerChange}>
    <QuestionCard question={currentQ} questionIndex={currentQuestionIndex} />
</QuestionProvider>
```

### Work (student, assignment may be submitted)

```tsx
<QuestionProvider key={currentQ.id} mode="take" role="student"
    isDisabled={!!assignment.submitted_at}
    assessmentId={assessment.id}
    answers={answers} onAnswerChange={handleAnswerChange}
    fileAnswers={fileAnswersByQuestion}
    onFileAnswerSaved={handleFileAnswerSaved}
    onFileAnswerRemoved={handleFileAnswerRemoved}>
    <QuestionCard question={currentQ} questionIndex={index} />
</QuestionProvider>
```

### Review (teacher, read-only)

```tsx
<QuestionProvider mode="review" role={routeContext.role} userAnswers={userAnswers}>
    <QuestionList title={t('...')} questions={assessment.questions ?? []} />
</QuestionProvider>
```

### Grade (teacher, editable scores)

```tsx
<QuestionProvider mode="grade" role={routeContext.role}
    userAnswers={userAnswers}
    canEditScores={canGradeAssessments}
    scoreOverrides={editableScores}
    onScoreChange={(id, value) => setEditableScores(prev => ({ ...prev, [id]: value }))}
    feedbackOverrides={feedbacks}
    onFeedbackChange={handleFeedbackChange}>
    <QuestionList title={t('...')} questions={assessment.questions ?? []} />
</QuestionProvider>
```

### Results (student, post-submission)

```tsx
<QuestionProvider mode="results" role="student"
    userAnswers={userAnswers} showCorrectAnswers={showCorrectAnswers}>
    <QuestionList questions={assessment.questions || []} />
</QuestionProvider>
```

### Show / Preview (assessment preview)

```tsx
<QuestionProvider mode="preview" role={routeContext.role} userAnswers={{}}>
    <QuestionList questions={assessment.questions ?? []} />
</QuestionProvider>
```

---

## Extending the System

### Adding a new question type

1. Create `renderers/MyTypeRenderer.tsx` implementing `QuestionRendererProps`
2. Add `my_type: MyTypeRenderer` to `renderers/index.ts`
3. Add `my_type` to the `QuestionType` union in `@/types`

No other file needs modification.

### Adding a new rendering mode

1. Add the mode literal to `QuestionRenderMode` in `types/question-rendering.ts`
2. Update `buildRenderConfig()` to derive the correct flags for the new mode
3. Implement mode-specific rendering inside the relevant renderers via `config.mode` or the derived flags

---

## Invariants

- `QuestionProvider` is placed **once per page**, never inside `QuestionCard` or `QuestionList`
- `QuestionCard` **never receives answer data as props** — all data flows through context
- `buildQuestionResult` is called **only inside `QuestionCard`** — renderers receive the normalized `QuestionResult` DTO
- `QuestionRenderers` is the **single dispatch point** for question types — no `if/switch` on `question.type` elsewhere
