# Exam Component Utilities

Utilities for exam question rendering and display components.

## Files

### questionTypeUtils.ts

Question type display utilities.

**Functions:**
- `getTypeLabels()` - Gets all localized question type labels
- `getTypeColor(type)` - Gets Tailwind class for question type
- `getTypeLabel(type)` - Gets localized label for specific type

**Constants:**
- `TYPE_COLORS` - Question type color mappings

### choiceUtils.ts

Choice rendering and validation utilities.

**Functions:**
- `getBooleanDisplay(content)` - Determines if content represents true
- `getBooleanLabel(isTrue)` - Gets localized boolean label (Vrai/Faux)
- `getBooleanShortLabel(isTrue)` - Gets short label (V/F)
- `getBooleanBadgeClass(isTrue, shouldHighlight)` - Gets badge styling
- `getChoiceStyles(isSelected, isCorrect, shouldShowCorrect)` - Generates choice styling
- `getStatusLabelText(isSelected, isCorrect, shouldShowCorrect, isTeacherView)` - Gets status text
- `getChoiceBorder(type)` - Gets border style for choice type

### questionLabelUtils.tsx

Question index and label utilities.

**Functions:**
- `questionIndexLabel(idx, bgClass)` - Generates letter label (A, B, C...)
- `getChoiceIndexLetter(index)` - Gets letter from index
- `getIndexBgClass(isCorrect, isSelected, shouldShowCorrect)` - Gets background class

## Usage Examples

### Question Type Display
```tsx
import { getTypeColor, getTypeLabel } from '@/utils/exam/components';

<span className={getTypeColor('multiple')}>
  {getTypeLabel('multiple')}
</span>
```

### Boolean Question Rendering
```tsx
import { getBooleanDisplay, getBooleanLabel, getBooleanBadgeClass } from '@/utils/exam/components';

const isTrue = getBooleanDisplay(choice.content);
const label = getBooleanLabel(isTrue);
const badgeClass = getBooleanBadgeClass(isTrue);
```

### Choice Styling
```tsx
import { getChoiceStyles, getStatusLabelText } from '@/utils/exam/components';

const styles = getChoiceStyles(isSelected, isCorrect, true);
const statusText = getStatusLabelText(isSelected, isCorrect, true, false);
```

### Question Index Labels
```tsx
import { questionIndexLabel } from '@/utils/exam/components';

// Renders "A", "B", "C", etc.
{choices.map((choice, idx) => (
  <div key={choice.id}>
    {questionIndexLabel(idx)}
    {choice.content}
  </div>
))}
```

## Integration

All utilities are exported from `@/utils/exam/components`:

```tsx
import {
  getTypeColor,
  getTypeLabel,
  getBooleanDisplay,
  getChoiceStyles,
  questionIndexLabel,
} from '@/utils/exam/components';
```

## Related Components

These utilities are used in:
- `TakeQuestion.tsx` - Question taking interface
- `QuestionResultReadOnly.tsx` - Result display
- `QuestionReadOnlySection.tsx` - Read-only question view
- `QuestionRenderer.tsx` - Generic question renderer

## Localization

All text utilities use `trans()` function and support:
- English (`en`)
- French (`fr`)

Translation keys are in `lang/{locale}/components.php` under:
- `components.take_question.*`
- `components.question_result_readonly.*`
