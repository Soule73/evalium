import type { Meta, StoryObj } from '@storybook/react';
import { useState } from 'react';
import { MarkdownEditor } from '@evalium/ui';

const meta: Meta<typeof MarkdownEditor> = {
    title: 'UI/Forms/MarkdownEditor',
    component: MarkdownEditor,
    parameters: {
        layout: 'padded',
        docs: {
            description: {
                component: 'A powerful Markdown editor with live preview, syntax highlighting, and math formula support using EasyMDE.',
            },
        },
    },
    argTypes: {
        placeholder: { control: 'text' },
        required: { control: 'boolean' },
        disabled: { control: 'boolean' },
        rows: { control: 'number' },
        enableBold: { control: 'boolean' },
        enableItalic: { control: 'boolean' },
        enableHeading: { control: 'boolean' },
        enableCode: { control: 'boolean' },
        enablePreview: { control: 'boolean' },
        enableFullscreen: { control: 'boolean' },
        enableMathInline: { control: 'boolean' },
        enableMathDisplay: { control: 'boolean' },
    },
};

export default meta;
type Story = StoryObj<typeof MarkdownEditor>;

export const Default: Story = {
    render: (args) => {
        const [value, setValue] = useState('');
        return (
            <div className="w-full max-w-4xl">
                <MarkdownEditor {...args} value={value} onChange={setValue} />
            </div>
        );
    },
};

export const WithLabel: Story = {
    render: () => {
        const [value, setValue] = useState('');
        return (
            <div className="w-full max-w-4xl">
                <MarkdownEditor
                    label="Question Description"
                    placeholder="Enter your question details..."
                    value={value}
                    onChange={setValue}
                    helpText="Use Markdown to format your text"
                />
            </div>
        );
    },
};

export const WithContent: Story = {
    render: () => {
        const [value, setValue] = useState(`# Sample Question

This is an **example** of a question with *rich* formatting.

## Mathematical Formula

The quadratic formula is:

$$x = \\frac{-b \\pm \\sqrt{b^2-4ac}}{2a}$$

## Code Example

\`\`\`javascript
function solve(a, b, c) {
  const discriminant = b * b - 4 * a * c;
  return (-b + Math.sqrt(discriminant)) / (2 * a);
}
\`\`\`

> **Important**: Make sure to check the discriminant before calculating!
`);
        return (
            <div className="w-full max-w-4xl">
                <MarkdownEditor
                    value={value}
                    onChange={setValue}
                    label="Question Content"
                />
            </div>
        );
    },
};

export const Required: Story = {
    render: () => {
        const [value, setValue] = useState('');
        return (
            <div className="w-full max-w-4xl">
                <MarkdownEditor
                    label="Required Field"
                    value={value}
                    onChange={setValue}
                    required
                    helpText="This field is mandatory"
                />
            </div>
        );
    },
};

export const WithError: Story = {
    render: () => {
        const [value, setValue] = useState('');
        return (
            <div className="w-full max-w-4xl">
                <MarkdownEditor
                    label="Question Text"
                    value={value}
                    onChange={setValue}
                    required
                    error="This field is required"
                />
            </div>
        );
    },
};

export const Disabled: Story = {
    render: () => {
        const [value] = useState('This content is read-only and cannot be edited.');
        return (
            <div className="w-full max-w-4xl">
                <MarkdownEditor
                    label="Read-Only Content"
                    value={value}
                    onChange={() => { }}
                    disabled
                />
            </div>
        );
    },
};

export const MinimalToolbar: Story = {
    render: () => {
        const [value, setValue] = useState('');
        return (
            <div className="w-full max-w-4xl">
                <MarkdownEditor
                    label="Simple Editor"
                    value={value}
                    onChange={setValue}
                    enableBold
                    enableItalic
                    enableHeading={false}
                    enableCode
                    enableLink
                    enablePreview
                    helpText="Simplified toolbar with basic formatting only"
                />
            </div>
        );
    },
};

export const MathFocused: Story = {
    render: () => {
        const [value, setValue] = useState(`# Mathematical Expressions

## Inline Math
The value of $\\pi$ is approximately 3.14159.

## Display Math
$$\\sum_{n=1}^{\\infty} \\frac{1}{n^2} = \\frac{\\pi^2}{6}$$

## Complex Formulas
$$\\int_{-\\infty}^{\\infty} e^{-x^2} dx = \\sqrt{\\pi}$$
`);
        return (
            <div className="w-full max-w-4xl">
                <MarkdownEditor
                    label="Math Problem"
                    value={value}
                    onChange={setValue}
                    enableMathInline
                    enableMathDisplay
                    enablePreview
                    helpText="Use $ for inline math and $$ for display math"
                />
            </div>
        );
    },
};

export const CustomHeight: Story = {
    render: () => {
        const [value, setValue] = useState('');
        return (
            <div className="w-full max-w-4xl">
                <MarkdownEditor
                    label="Extended Editor"
                    value={value}
                    onChange={setValue}
                    rows={15}
                    minHeight="500px"
                    helpText="This editor has a larger initial height"
                />
            </div>
        );
    },
};

export const WithAllFeatures: Story = {
    render: () => {
        const [value, setValue] = useState(`# Complete Example

## Text Formatting
**Bold text**, *italic text*, ~~strikethrough~~

## Lists
- Item 1
- Item 2
  - Nested item

1. First
2. Second
3. Third

## Code Blocks

\`\`\`python
def fibonacci(n):
    if n <= 1:
        return n
    return fibonacci(n-1) + fibonacci(n-2)
\`\`\`

## Math
Inline: $E = mc^2$

Display:
$$F = G\\frac{m_1 m_2}{r^2}$$

## Tables
| Name | Value | Unit |
|------|-------|------|
| Speed | 299792458 | m/s |
| Mass | 9.109×10⁻³¹ | kg |

## Links & Images
[GitHub](https://github.com)

---

> This is a blockquote with **formatting**
`);
        return (
            <div className="w-full max-w-4xl">
                <MarkdownEditor
                    label="Full Featured Editor"
                    value={value}
                    onChange={setValue}
                    enableBold
                    enableItalic
                    enableHeading
                    enableStrikethrough
                    enableCode
                    enableQuote
                    enableUnorderedList
                    enableOrderedList
                    enableLink
                    enableImage
                    enableTable
                    enableHorizontalRule
                    enablePreview
                    enableSideBySide
                    enableFullscreen
                    enableGuide
                    enableMathInline
                    enableMathDisplay
                />
            </div>
        );
    },
};
