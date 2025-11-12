# Exam Take - Hooks & Utilities

This directory contains all hooks and utilities related to the exam-taking flow.

## Structure

```
hooks/features/exam/take/
├── index.ts                      # Barrel export for all take hooks
├── useTakeExam.ts               # Master orchestration hook
├── useExamAnswers.ts            # Answer state management (Zustand)
├── useExamTimer.ts              # Countdown timer with auto-submit
├── useExamSubmission.ts         # Form submission with Inertia
├── useExamAnswerSave.ts         # Debounced answer persistence
├── useExamSecurity.ts           # Security monitoring and enforcement
├── useExamSecurityViolation.ts  # Violation detection and termination
├── useExamFullscreen.ts         # Fullscreen requirement management
└── useAutoSave.ts               # Generic auto-save utility

utils/exam/take/
├── index.ts                     # Barrel export for all utilities
├── timeUtils.ts                 # Time formatting and calculations
├── answerUtils.ts               # Answer validation and formatting
└── securityUtils.ts             # Security checks and violation handling
```

## Hooks

### useTakeExam (Master Hook)
Orchestrates the entire exam-taking flow by coordinating all sub-hooks.

**Dependencies:**
- `useExamAnswers` - Answer state (Zustand store)
- `useExamSubmission` - Form submission
- `useExamSecurityViolation` - Violation handling
- `useExamSecurity` - Security monitoring
- `useExamFullscreen` - Fullscreen enforcement
- `useExamTimer` - Countdown timer
- `useExamAnswerSave` - Answer persistence
- `useAutoSave` - Periodic auto-save

**Returns:**
```typescript
{
  security: SecurityState,
  processing: boolean,
  handleAnswerChange: (questionId, value) => void,
  handleSubmit: () => void,
  enterFullscreen: () => Promise<void>
}
```

**Usage:**
```typescript
const {
  handleAnswerChange,
  handleSubmit,
  enterFullscreen,
  security,
  processing
} = useTakeExam({ exam, questions, userAnswers });
```

### useExamAnswers
Manages answer state using Zustand store for global state management.

**Features:**
- Initializes answers from backend data
- Updates answers in Zustand store
- Supports multiple answer formats (text, single choice, multiple choice)

**Returns:**
```typescript
{
  answers: Record<number, string | number | number[]>,
  updateAnswer: (questionId, value) => void,
  setAnswers: (answers) => void
}
```

### useExamTimer
Countdown timer with auto-submit when time expires.

**Features:**
- Uses Zustand for global time state
- Auto-submits exam when time reaches 0
- Prevents multiple submissions
- Uses `minutesToSeconds` utility

**Returns:**
```typescript
{
  timeLeft: number  // seconds remaining
}
```

### useExamSubmission
Handles exam submission with Inertia forms.

**Features:**
- Uses Zustand for submission state
- Manages confirmation modal state
- Handles success/error callbacks
- Prevents double submissions

**Returns:**
```typescript
{
  isSubmitting: boolean,
  showConfirmModal: boolean,
  setShowConfirmModal: (show) => void,
  processing: boolean,
  handleSubmit: (answers) => void,
  handleAbandon: () => void,
  updateSubmissionData: (answers) => void
}
```

### useExamAnswerSave
Debounced answer persistence to backend.

**Features:**
- Debounced saves (500ms delay)
- Batch answer formatting with `formatAnswersForSubmission`
- Force save for immediate persistence
- Cleanup on unmount

**Returns:**
```typescript
{
  saveAnswerIndividual: (questionId, value, allAnswers) => void,
  saveAllAnswers: (answers) => Promise<void>,
  forceSave: (answers) => Promise<void>,
  cleanup: () => void
}
```

### useExamSecurity
Monitors and enforces exam security features.

**Features:**
- Fullscreen enforcement
- Tab switch detection
- Copy/paste prevention
- Context menu blocking
- Developer tools detection
- Uses security utilities from `securityUtils.ts`

**Returns:**
```typescript
{
  isFullscreen: boolean,
  securityViolations: SecurityEvent[],
  violations: SecurityEvent[],
  isIdle: boolean,
  isBlocked: boolean,
  attemptCount: number,
  enterFullscreen: () => Promise<void>,
  exitFullscreen: () => Promise<void>,
  clearViolations: () => void,
  resetViolations: () => void,
  getSecurityScore: () => number,
  securityEnabled: boolean
}
```

### useExamSecurityViolation
Handles critical security violations and exam termination.

**Features:**
- Uses Zustand for violation state
- Translates violation messages
- Terminates exam on critical violations
- Saves answers before termination

**Returns:**
```typescript
{
  examTerminated: boolean,
  terminationReason: string,
  handleViolation: (type, answers) => void,
  terminateExamForViolation: (type, answers) => Promise<void>
}
```

### useExamFullscreen
Manages fullscreen requirement and modal display.

**Features:**
- Uses Zustand for modal state
- Checks fullscreen support with `isFullscreenSupported`
- Handles fullscreen entry/exit
- Configurable via exam config

**Returns:**
```typescript
{
  showFullscreenModal: boolean,
  fullscreenRequired: boolean,
  examCanStart: boolean,
  enterFullscreen: () => Promise<void>,
  exitFullscreen: () => Promise<void>
}
```

### useAutoSave
Generic auto-save utility (not exam-specific).

**Features:**
- Configurable interval (default 30s)
- Debounce time (default 2s)
- Retry mechanism
- BeforeUnload protection

**Returns:**
```typescript
{
  data: T,
  isModified: boolean,
  isSaving: boolean,
  lastSaved: Date | null,
  saveCount: number,
  updateData: (updates) => void,
  saveNow: () => Promise<void>,
  resetModified: () => void
}
```

## Utilities

### timeUtils.ts
Time formatting and calculation utilities.

**Functions:**
- `formatExamTime(seconds)` - Format to MM:SS
- `getTimeRemainingPercentage(timeLeft, duration)` - Calculate percentage
- `isTimeCritical(timeLeft, duration)` - Check if < 10% remaining
- `getTimeColorClass(timeLeft, duration)` - Get Tailwind color class
- `minutesToSeconds(minutes)` - Convert minutes to seconds
- `secondsToMinutes(seconds)` - Convert seconds to minutes

### answerUtils.ts
Answer validation and formatting utilities.

**Functions:**
- `formatAnswersForSubmission(answers)` - Format for backend
- `isAnswerValid(value)` - Check if answer is not empty
- `countAnsweredQuestions(answers)` - Count valid answers
- `getExamCompletionPercentage(answers, totalQuestions)` - Calculate completion
- `areAllQuestionsAnswered(answers, totalQuestions)` - Check if all answered
- `getUnansweredQuestions(answers, questionIds)` - Get unanswered IDs

### securityUtils.ts
Security validation and violation handling utilities.

**Constants:**
- `EXAM_VIOLATION_TYPES` - All violation types

**Functions:**
- `getViolationTranslationKey(violationType)` - Get i18n key
- `isCriticalViolation(violationType)` - Check if should terminate
- `getViolationSeverity(violationType)` - Get severity level
- `isFullscreenSupported()` - Check browser support
- `isInFullscreen()` - Check current state

## State Management (Zustand)

The exam take flow uses a centralized Zustand store: `useExamTakeStore`

**State:**
```typescript
{
  answers: Record<number, string | number | number[]>,
  isSubmitting: boolean,
  examTerminated: boolean,
  terminationReason: string,
  timeLeft: number,
  showConfirmModal: boolean,
  showFullscreenModal: boolean,
  examCanStart: boolean
}
```

**Actions:**
```typescript
{
  setAnswer: (questionId, value) => void,
  setAnswers: (answers) => void,
  setIsSubmitting: (isSubmitting) => void,
  setExamTerminated: (terminated, reason?) => void,
  setTimeLeft: (timeLeft | (prev) => number) => void,
  setShowConfirmModal: (show) => void,
  setShowFullscreenModal: (show) => void,
  setExamCanStart: (canStart) => void,
  reset: () => void
}
```

## Translations

Security violations are translated via `lang/{locale}/exam_security.php`:

```php
'violations' => [
    'tab_switch' => 'Tab switch detected',
    'fullscreen_exit' => 'Fullscreen mode exited',
    'dev_tools' => 'Developer tools detected',
    'copy_paste' => 'Copy-paste detected',
    'right_click' => 'Right-click detected',
    'print' => 'Print attempt detected',
    'idle_timeout' => 'Inactivity timeout exceeded',
    'suspicious_activity' => 'Suspicious activity detected',
    'default' => 'Security violation',
]
```

## Performance Optimizations

1. **useCallback** on all event handlers to prevent unnecessary re-renders
2. **useMemo** for expensive calculations and translations
3. **Zustand with useShallow** for granular state subscriptions
4. **Debounced saves** to reduce backend requests
5. **Refs** for timer callbacks to avoid re-initialization

## Usage Example

```typescript
// In Take.tsx
import { useTakeExam } from '@/hooks/features/exam/take';
import { useExamTakeStore } from '@/stores/useExamTakeStore';
import { useShallow } from 'zustand/react/shallow';

export default function Take({ exam, questions, userAnswers }) {
  // Get state from Zustand store
  const { 
    answers, 
    timeLeft, 
    showConfirmModal, 
    setShowConfirmModal,
    isSubmitting,
    examTerminated,
    terminationReason,
    showFullscreenModal,
    examCanStart
  } = useExamTakeStore(useShallow((state) => ({
    answers: state.answers,
    timeLeft: state.timeLeft,
    showConfirmModal: state.showConfirmModal,
    setShowConfirmModal: state.setShowConfirmModal,
    isSubmitting: state.isSubmitting,
    examTerminated: state.examTerminated,
    terminationReason: state.terminationReason,
    showFullscreenModal: state.showFullscreenModal,
    examCanStart: state.examCanStart,
  })));

  // Get handlers from master hook
  const {
    security,
    processing,
    handleAnswerChange,
    handleSubmit,
    enterFullscreen,
  } = useTakeExam({ exam, questions, userAnswers });

  // Component logic...
}
```

## Testing Considerations

1. **Mock Zustand store** in tests
2. **Test debounce behavior** with `jest.useFakeTimers()`
3. **Mock fullscreen API** (not available in JSDOM)
4. **Test violation scenarios** individually
5. **Mock Inertia router** for submission tests
