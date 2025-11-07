import React from 'react';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import remarkMath from 'remark-math';
import rehypeKatex from 'rehype-katex';
import rehypePrismPlus from 'rehype-prism-plus';
import 'katex/dist/katex.min.css';
import 'prismjs/themes/prism-tomorrow.css';

interface MarkdownRendererProps {
    children: string;
    className?: string;
}

/**
 * Renders Markdown content with custom styling for various elements using ReactMarkdown and remark-gfm.
 * Now supports mathematical formulas with KaTeX and syntax highlighting with Prism.
 *
 * @component
 * @param {MarkdownRendererProps} props - The props for the MarkdownRenderer component.
 * @param {React.ReactNode} props.children - The Markdown content to render.
 * @param {string} [props.className] - Additional CSS classes to apply to the root container.
 *
 * @remarks
 * This component customizes the rendering of Markdown elements such as headings, lists, links, code blocks,
 * tables, blockquotes, images, and more, applying Tailwind CSS classes for consistent styling.
 * It uses the `remark-gfm` plugin to support GitHub Flavored Markdown features.
 * 
 * Mathematical formulas are supported using KaTeX:
 * - Inline formulas: $E = mc^2$
 * - Display formulas: $$\int_{-\infty}^{\infty} e^{-x^2} dx = \sqrt{\pi}$$
 * 
 * Code syntax highlighting is provided by Prism:
 * - Supports many languages: ```javascript, ```python, ```php, etc.
 *
 * @example
 * ```tsx
 * <MarkdownRenderer>
 *   {`# Hello World
 * This is **Markdown** with math: $x^2 + y^2 = z^2$
 * 
 * \`\`\`javascript
 * console.log('Hello, World!');
 * \`\`\`
 * 
 * $$\\sum_{i=1}^{n} i = \\frac{n(n+1)}{2}$$
 * `}
 * </MarkdownRenderer>
 * ```
 */
const MarkdownRenderer: React.FC<MarkdownRendererProps> = ({
    children,
    className = ''
}) => {
    return (
        <div className={`markdown-content prose prose-sm max-w-none ${className}`}>
            <style dangerouslySetInnerHTML={{
                __html: `
                .markdown-content .katex {
                    font-size: 1.1em;
                }
                .markdown-content .katex-display {
                    margin: 1rem 0;
                    text-align: center;
                }
                .markdown-content .katex-display .katex {
                    display: inline-block;
                    background: #f8fafc;
                    padding: 0.5rem 1rem;
                    border-radius: 0.5rem;
                    border: 1px solid #e2e8f0;
                }
                .markdown-content pre[class*="language-"] {
                    background: #1a202c !important;
                    border: 1px solid #2d3748;
                    border-radius: 0.5rem;
                }
                .markdown-content code[class*="language-"] {
                    background: transparent !important;
                }
                .markdown-content :not(pre) > code[class*="language-"] {
                    background: #1a202c !important;
                    color: #e2e8f0 !important;
                    padding: 0.125rem 0.25rem;
                    border-radius: 0.25rem;
                    font-size: 0.875rem;
                }
                `
            }} />
            <ReactMarkdown
                remarkPlugins={[remarkGfm, remarkMath]}
                rehypePlugins={[rehypeKatex, rehypePrismPlus]}
                components={{
                    h1: ({ children }) => (
                        <h1 className="text-2xl font-bold text-gray-900 mb-4 mt-6 first:mt-0 border-b border-gray-200 pb-2">
                            {children}
                        </h1>
                    ),
                    h2: ({ children }) => (
                        <h2 className="text-xl font-semibold text-gray-800 mb-3 mt-5 first:mt-0">
                            {children}
                        </h2>
                    ),
                    h3: ({ children }) => (
                        <h3 className="text-lg font-medium text-gray-800 mb-2 mt-4 first:mt-0">
                            {children}
                        </h3>
                    ),
                    h4: ({ children }) => (
                        <h4 className="text-base font-medium text-gray-700 mb-2 mt-3 first:mt-0">
                            {children}
                        </h4>
                    ),
                    h5: ({ children }) => (
                        <h5 className="text-sm font-medium text-gray-700 mb-1 mt-3 first:mt-0">
                            {children}
                        </h5>
                    ),
                    h6: ({ children }) => (
                        <h6 className="text-sm font-medium text-gray-600 mb-1 mt-3 first:mt-0">
                            {children}
                        </h6>
                    ),

                    // Paragraphes
                    p: ({ children }) => (
                        <p className="text-gray-700 mb-4 leading-relaxed last:mb-0">
                            {children}
                        </p>
                    ),

                    // Listes
                    ul: ({ children }) => (
                        <ul className="list-disc list-inside text-gray-700 mb-4 space-y-1 ml-4">
                            {children}
                        </ul>
                    ),
                    ol: ({ children }) => (
                        <ol className="list-decimal list-inside text-gray-700 mb-4 space-y-1 ml-4">
                            {children}
                        </ol>
                    ),
                    li: ({ children }) => (
                        <li className="leading-relaxed">
                            {children}
                        </li>
                    ),

                    // Liens
                    a: ({ href, children }) => (
                        <a
                            href={href}
                            className="text-blue-600 hover:text-blue-800 underline"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {children}
                        </a>
                    ),

                    // Code
                    code: ({ className, children }) => {
                        const isInline = !className || !className.includes('language-');
                        if (isInline) {
                            return (
                                <code className="bg-gray-100 text-red-600 px-1 py-0.5 rounded text-sm font-mono">
                                    {children}
                                </code>
                            );
                        }
                        // Prism gère automatiquement la coloration syntaxique via rehype-prism-plus
                        return (
                            <code className={className}>
                                {children}
                            </code>
                        );
                    },

                    // Blocs de code
                    pre: ({ children }) => (
                        <pre className="bg-gray-900 text-gray-100 p-4 rounded-lg text-sm font-mono overflow-x-auto mb-4 border relative">
                            {children}
                        </pre>
                    ),

                    // Citations
                    blockquote: ({ children }) => (
                        <blockquote className="border-l-4 border-blue-500 pl-4 py-2 mb-4 bg-blue-50 text-gray-700 italic">
                            {children}
                        </blockquote>
                    ),

                    // Tableaux
                    table: ({ children }) => (
                        <div className="overflow-x-auto mb-4">
                            <table className="min-w-full border border-gray-200 rounded-lg">
                                {children}
                            </table>
                        </div>
                    ),
                    thead: ({ children }) => (
                        <thead className="bg-gray-50">
                            {children}
                        </thead>
                    ),
                    tbody: ({ children }) => (
                        <tbody className="divide-y divide-gray-200">
                            {children}
                        </tbody>
                    ),
                    tr: ({ children }) => (
                        <tr className="hover:bg-gray-50">
                            {children}
                        </tr>
                    ),
                    th: ({ children }) => (
                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                            {children}
                        </th>
                    ),
                    td: ({ children }) => (
                        <td className="px-4 py-2 text-sm text-gray-700 border-b">
                            {children}
                        </td>
                    ),

                    // Règles horizontales
                    hr: () => (
                        <hr className="my-6 border-gray-300" />
                    ),

                    // Texte en gras et italique
                    strong: ({ children }) => (
                        <strong className="font-semibold text-gray-900">
                            {children}
                        </strong>
                    ),
                    em: ({ children }) => (
                        <em className="italic text-gray-700">
                            {children}
                        </em>
                    ),

                    // Images
                    img: ({ src, alt }) => (
                        <div className="mb-4">
                            <img
                                src={src}
                                alt={alt}
                                className="max-w-full h-auto rounded-lg shadow-sm border"
                            />
                            {alt && (
                                <p className="text-sm text-gray-500 mt-1 text-center italic">
                                    {alt}
                                </p>
                            )}
                        </div>
                    ),
                }}
            >
                {children}
            </ReactMarkdown>
        </div>
    );
};

export default MarkdownRenderer;