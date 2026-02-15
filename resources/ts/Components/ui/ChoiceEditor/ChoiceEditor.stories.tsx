import type { Meta, StoryObj } from '@storybook/react';
import { useState } from 'react';
import ChoiceEditor from './ChoiceEditor';

const meta: Meta<typeof ChoiceEditor> = {
    title: 'UI/Forms/ChoiceEditor',
    component: ChoiceEditor,
    parameters: {
        layout: 'padded',
        docs: {
            description: {
                component:
                    'A flexible editor for question choices with support for both simple text and Markdown editing modes, including live preview capability.',
            },
        },
    },
    argTypes: {
        value: { control: 'text' },
        required: { control: 'boolean' },
        readOnly: { control: 'boolean' },
        isMarkdownMode: { control: 'boolean' },
        showPreview: { control: 'boolean' },
    },
};

export default meta;
type Story = StoryObj<typeof ChoiceEditor>;

export const Default: Story = {
    render: (args) => {
        const [value, setValue] = useState('');
        return <ChoiceEditor {...args} value={value} onChange={setValue} />;
    },
};

export const SimpleMode: Story = {
    render: () => {
        const [value, setValue] = useState('This is a simple text answer');
        const [isMarkdown, setIsMarkdown] = useState(false);
        const [showPreview, setShowPreview] = useState(false);

        return (
            <div className="w-full max-w-2xl">
                <ChoiceEditor
                    value={value}
                    onChange={setValue}
                    isMarkdownMode={isMarkdown}
                    showPreview={showPreview}
                    onToggleMarkdownMode={() => setIsMarkdown(!isMarkdown)}
                    onTogglePreview={() => setShowPreview(!showPreview)}
                />
            </div>
        );
    },
};

export const MarkdownMode: Story = {
    render: () => {
        const [value, setValue] = useState(
            '# Title\n\nThis is **bold** and this is *italic*.\n\n$$E = mc^2$$',
        );
        const [isMarkdown, setIsMarkdown] = useState(true);
        const [showPreview, setShowPreview] = useState(false);

        return (
            <div className="w-full max-w-4xl">
                <ChoiceEditor
                    value={value}
                    onChange={setValue}
                    isMarkdownMode={isMarkdown}
                    showPreview={showPreview}
                    onToggleMarkdownMode={() => setIsMarkdown(!isMarkdown)}
                    onTogglePreview={() => setShowPreview(!showPreview)}
                />
            </div>
        );
    },
};

export const WithPreview: Story = {
    render: () => {
        const [value, setValue] = useState(
            '## Mathematical Formula\n\nThe quadratic formula: $x = \\frac{-b \\pm \\sqrt{b^2-4ac}}{2a}$\n\n```javascript\nconst solve = (a, b, c) => {\n  return (-b + Math.sqrt(b*b - 4*a*c)) / (2*a);\n};\n```',
        );
        const [isMarkdown, setIsMarkdown] = useState(true);
        const [showPreview, setShowPreview] = useState(true);

        return (
            <div className="w-full">
                <ChoiceEditor
                    value={value}
                    onChange={setValue}
                    isMarkdownMode={isMarkdown}
                    showPreview={showPreview}
                    onToggleMarkdownMode={() => setIsMarkdown(!isMarkdown)}
                    onTogglePreview={() => setShowPreview(!showPreview)}
                />
            </div>
        );
    },
};

export const WithError: Story = {
    render: () => {
        const [value, setValue] = useState('');
        const [isMarkdown, setIsMarkdown] = useState(false);
        const [showPreview, setShowPreview] = useState(false);

        return (
            <div className="w-full max-w-2xl">
                <ChoiceEditor
                    value={value}
                    onChange={setValue}
                    required
                    error="This field is required"
                    isMarkdownMode={isMarkdown}
                    showPreview={showPreview}
                    onToggleMarkdownMode={() => setIsMarkdown(!isMarkdown)}
                    onTogglePreview={() => setShowPreview(!showPreview)}
                />
            </div>
        );
    },
};

export const ReadOnly: Story = {
    render: () => {
        const [value] = useState('This is a read-only choice');

        return (
            <div className="w-full max-w-2xl">
                <ChoiceEditor value={value} onChange={() => {}} readOnly />
            </div>
        );
    },
};

export const ComplexMarkdown: Story = {
    render: () => {
        const [value, setValue] = useState(`# Advanced Mathematics

## Integral Calculus

The definite integral of $f(x)$ from $a$ to $b$:

$$\\int_a^b f(x)\\,dx = F(b) - F(a)$$

### Code Example

\`\`\`python
import numpy as np

def integrate(f, a, b, n=1000):
    x = np.linspace(a, b, n)
    y = f(x)
    return np.trapz(y, x)
\`\`\`

> **Note**: This uses the trapezoidal rule for numerical integration.

| Method | Accuracy | Speed |
|--------|----------|-------|
| Trapezoidal | Medium | Fast |
| Simpson's | High | Medium |
| Gaussian | Very High | Slow |
`);
        const [isMarkdown, setIsMarkdown] = useState(true);
        const [showPreview, setShowPreview] = useState(true);

        return (
            <div className="w-full">
                <ChoiceEditor
                    value={value}
                    onChange={setValue}
                    isMarkdownMode={isMarkdown}
                    showPreview={showPreview}
                    onToggleMarkdownMode={() => setIsMarkdown(!isMarkdown)}
                    onTogglePreview={() => setShowPreview(!showPreview)}
                />
            </div>
        );
    },
};
