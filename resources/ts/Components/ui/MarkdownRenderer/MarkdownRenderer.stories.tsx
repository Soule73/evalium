import type { Meta, StoryObj } from '@storybook/react';
import MarkdownRenderer from './MarkdownRenderer';

const meta: Meta<typeof MarkdownRenderer> = {
    title: 'UI/Display/MarkdownRenderer',
    component: MarkdownRenderer,
    parameters: {
        layout: 'padded',
        docs: {
            description: {
                component: 'A component for rendering Markdown content with support for GitHub Flavored Markdown, mathematical formulas (KaTeX), and syntax highlighting (Prism.js).',
            },
        },
    },
    argTypes: {
        children: { control: 'text' },
        className: { control: 'text' },
    },
};

export default meta;
type Story = StoryObj<typeof MarkdownRenderer>;

export const Default: Story = {
    args: {
        children: '# Hello World\n\nThis is a **simple** Markdown example with *italic* text.',
    },
};

export const Headings: Story = {
    args: {
        children: `# Heading 1
## Heading 2
### Heading 3
#### Heading 4
##### Heading 5
###### Heading 6

Regular paragraph text follows the headings.`,
    },
};

export const TextFormatting: Story = {
    args: {
        children: `# Text Formatting Examples

This is **bold text** and this is *italic text*.

You can also use __bold__ and _italic_ with underscores.

Here is ~~strikethrough text~~.

Inline \`code\` looks like this.`,
    },
};

export const Lists: Story = {
    args: {
        children: `# List Examples

## Unordered List
- First item
- Second item
- Third item
  - Nested item 1
  - Nested item 2

## Ordered List
1. First step
2. Second step
3. Third step
   1. Sub-step A
   2. Sub-step B

## Mixed List
- Unordered item
  1. Ordered nested
  2. Another ordered
- Back to unordered`,
    },
};

export const Links: Story = {
    args: {
        children: `# Links

Here is a [link to Google](https://www.google.com).

You can also use [reference links][ref].

[ref]: https://github.com "GitHub Homepage"

Auto-link: https://www.example.com`,
    },
};

export const CodeBlocks: Story = {
    args: {
        children: `# Code Examples

## JavaScript
\`\`\`javascript
function factorial(n) {
  if (n <= 1) return 1;
  return n * factorial(n - 1);
}

console.log(factorial(5)); // 120
\`\`\`

## Python
\`\`\`python
def fibonacci(n):
    if n <= 1:
        return n
    return fibonacci(n-1) + fibonacci(n-2)

print(fibonacci(10))
\`\`\`

## PHP
\`\`\`php
<?php
function greet($name) {
    return "Hello, " . $name . "!";
}

echo greet("World");
?>
\`\`\``,
    },
};

export const MathFormulas: Story = {
    args: {
        children: `# Mathematical Formulas

## Inline Math
The famous equation $E = mc^2$ relates energy and mass.

The quadratic formula is $x = \\frac{-b \\pm \\sqrt{b^2-4ac}}{2a}$.

## Display Math
$$\\int_{-\\infty}^{\\infty} e^{-x^2} dx = \\sqrt{\\pi}$$

$$\\sum_{n=1}^{\\infty} \\frac{1}{n^2} = \\frac{\\pi^2}{6}$$

## Complex Formulas
$$F = G\\frac{m_1 m_2}{r^2}$$

$$\\nabla \\times \\vec{B} = \\mu_0 \\vec{J} + \\mu_0\\epsilon_0\\frac{\\partial \\vec{E}}{\\partial t}$$`,
    },
};

export const Tables: Story = {
    args: {
        children: `# Tables

| Name | Age | Country |
|------|-----|---------|
| Alice | 25 | USA |
| Bob | 30 | Canada |
| Charlie | 28 | UK |

## Alignment

| Left Aligned | Center Aligned | Right Aligned |
|:-------------|:--------------:|--------------:|
| Left | Center | Right |
| A | B | C |`,
    },
};

export const Blockquotes: Story = {
    args: {
        children: `# Blockquotes

> This is a blockquote.
> It can span multiple lines.

> **Note**: You can use formatting inside blockquotes.
> - Even lists!
> - Like this one.

> Nested quotes work too:
> > This is nested
> > > And this is even more nested`,
    },
};

export const Images: Story = {
    args: {
        children: `# Images

![Sample Image](https://via.placeholder.com/600x300/4F46E5/FFFFFF?text=Sample+Image)

Caption text can go below the image using alt text.`,
    },
};

export const HorizontalRules: Story = {
    args: {
        children: `# Horizontal Rules

Content before the rule.

---

Content after the first rule.

***

Content after the second rule.`,
    },
};

export const CompleteExample: Story = {
    args: {
        children: `# Complete Markdown Example

## Introduction

This document demonstrates **all features** of the MarkdownRenderer component.

## Text Formatting

We support *italic*, **bold**, and ~~strikethrough~~ text. You can also use inline \`code\`.

## Lists

### Shopping List
- Apples
- Bananas
- Oranges
  - Valencia
  - Navel

### Todo List
1. Wake up
2. Brush teeth
3. Have breakfast

## Code Examples

\`\`\`javascript
const greet = (name) => {
  console.log(\`Hello, \${name}!\`);
};

greet("World");
\`\`\`

## Mathematical Formulas

The Pythagorean theorem: $a^2 + b^2 = c^2$

$$\\int_0^1 x^2 dx = \\frac{1}{3}$$

## Tables

| Feature | Supported | Priority |
|---------|-----------|----------|
| GFM | ✓ | High |
| Math | ✓ | High |
| Syntax | ✓ | Medium |

## Important Note

> **Warning**: This is an important message!
> 
> Always test your code before deploying.

## Links

Check out [GitHub](https://github.com) for more information.

---

That's all folks! 🎉`,
    },
};

export const QuestionExample: Story = {
    args: {
        children: `# Physics Problem

## Question

A ball is thrown vertically upward with an initial velocity $v_0 = 20 \\ m/s$.

Calculate:
1. The maximum height reached
2. The time to reach maximum height
3. The total time in air

## Solution

Using the kinematic equation:

$$v^2 = v_0^2 + 2a\\Delta y$$

At maximum height, $v = 0$:

$$0 = v_0^2 - 2g\\Delta y$$

Therefore:

$$\\Delta y = \\frac{v_0^2}{2g} = \\frac{(20)^2}{2(9.8)} = 20.4 \\ m$$

> **Answer**: The maximum height is approximately 20.4 meters.

## Code Verification

\`\`\`python
v0 = 20  # m/s
g = 9.8  # m/s²

max_height = v0**2 / (2 * g)
time_to_max = v0 / g
total_time = 2 * time_to_max

print(f"Max height: {max_height:.2f} m")
print(f"Time to max: {time_to_max:.2f} s")
print(f"Total time: {total_time:.2f} s")
\`\`\``,
    },
};

export const CustomClassName: Story = {
    args: {
        children: '# Custom Styling\n\nThis example has a custom className applied.',
        className: 'bg-blue-50 p-6 rounded-lg border-2 border-blue-200',
    },
};
