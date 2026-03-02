import { forwardRef, useImperativeHandle } from 'react';
import 'easymde/dist/easymde.min.css';
import 'katex/dist/katex.min.css';
import { type MarkdownEditorHandle, useMarkdownEditor } from './useMarkdownEditor';

export type { MarkdownEditorHandle } from './useMarkdownEditor';

interface MarkdownEditorProps {
    value?: string;
    onChange?: (value: string) => void;
    placeholder?: string;
    required?: boolean;
    id?: string;
    className?: string;
    rows?: number;
    disabled?: boolean;
    error?: string;
    label?: string;
    helpText?: string;

    // Toolbar features
    enableBold?: boolean;
    enableItalic?: boolean;
    enableHeading?: boolean;
    enableStrikethrough?: boolean;
    enableCode?: boolean;
    enableQuote?: boolean;
    enableUnorderedList?: boolean;
    enableOrderedList?: boolean;
    enableLink?: boolean;
    enableImage?: boolean;
    enableTable?: boolean;
    enableHorizontalRule?: boolean;
    enablePreview?: boolean;
    enableSideBySide?: boolean;
    enableFullscreen?: boolean;
    enableGuide?: boolean;
    enableUndo?: boolean;
    enableRedo?: boolean;
    enableMathInline?: boolean;
    enableMathDisplay?: boolean;

    // Advanced options
    enableSpellChecker?: boolean;
    enableStatus?: boolean;
    enableAutofocus?: boolean;
    enableLineNumbers?: boolean;
    enableLineWrapping?: boolean;
    tabSize?: number;
    minHeight?: string;
    maxHeight?: string;

    // Customization
    customToolbar?: (string | '|')[];
    theme?: string;
    editorClassName?: string;

    // Image upload
    enableImageUpload?: boolean;
    imageUploadFunction?: (
        file: File,
        onSuccess: (url: string) => void,
        onError: (error: string) => void,
    ) => void;
    imageUploadEndpoint?: string;
    imageMaxSize?: number;
}

export const MarkdownEditor = forwardRef<MarkdownEditorHandle, MarkdownEditorProps>(
    (
        {
            value = '',
            onChange,
            placeholder = 'Type your answer here...',
            required = false,
            id,
            className = '',
            rows = 6,
            disabled = false,
            error,
            label,
            helpText,

            // All other props are passed to the hook
            ...editorOptions
        },
        ref,
    ) => {
        const componentId = id || `markdown-editor-${Math.random().toString(36).substr(2, 9)}`;

        // Use the hook to handle all editor logic
        const { textareaRef, editorMethods } = useMarkdownEditor({
            value,
            onChange,
            placeholder,
            disabled,
            ...editorOptions,
        });

        // Expose methods via ref
        useImperativeHandle(ref, () => editorMethods);

        return (
            <div className={`markdown-editor-field ${className}`}>
                {label && (
                    <label
                        htmlFor={componentId}
                        className="block text-sm font-medium text-gray-700 mb-2"
                    >
                        {label}
                        {required && <span className="text-red-500 ml-1">*</span>}
                    </label>
                )}

                <div
                    className={`markdown-editor-container relative ${editorOptions.editorClassName || ''} ${error ? 'ring-2 ring-red-500 rounded-lg' : ''}`}
                >
                    <textarea
                        ref={textareaRef}
                        id={componentId}
                        placeholder={placeholder}
                        rows={rows}
                        className="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors resize-none"
                        style={{ display: 'none' }}
                    />
                </div>

                {error && <p className="mt-2 text-sm text-red-600">{error}</p>}

                {helpText && !error && <p className="mt-2 text-sm text-gray-500">{helpText}</p>}

                {/* Editor styles */}
                <style
                    dangerouslySetInnerHTML={{
                        __html: `
                .EasyMDEContainer .editor-toolbar .fa-calculator:before {
                    content: "∑";
                    font-family: serif;
                    font-weight: bold;
                }
                .EasyMDEContainer .editor-toolbar .fa-superscript:before {
                    content: "∫";
                    font-family: serif;
                    font-weight: bold;
                }
                `,
                    }}
                />
            </div>
        );
    },
);

MarkdownEditor.displayName = 'MarkdownEditor';

export default MarkdownEditor;
