import { forwardRef, useImperativeHandle } from 'react';
import { useMarkdownEditor } from '@/hooks';
import 'easymde/dist/easymde.min.css';
import 'katex/dist/katex.min.css';
import { MarkdownEditorHandle } from '@/hooks/forms/useMarkdownEditor';

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

    // Fonctionnalités de la toolbar
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

    // Options avancées
    enableSpellChecker?: boolean;
    enableStatus?: boolean;
    enableAutofocus?: boolean;
    enableLineNumbers?: boolean;
    enableLineWrapping?: boolean;
    tabSize?: number;
    minHeight?: string;
    maxHeight?: string;

    // Personnalisation
    customToolbar?: (string | '|')[];
    theme?: string;
    editorClassName?: string;

    // Upload d'images
    enableImageUpload?: boolean;
    imageUploadFunction?: (file: File, onSuccess: (url: string) => void, onError: (error: string) => void) => void;
    imageUploadEndpoint?: string;
    imageMaxSize?: number;
}

export const MarkdownEditor = forwardRef<MarkdownEditorHandle, MarkdownEditorProps>(({
    value = '',
    onChange,
    placeholder = 'Saisissez votre réponse ici...',
    required = false,
    id,
    className = '',
    rows = 6,
    disabled = false,
    error,
    label,
    helpText,

    // Toutes les autres props seront passées au hook
    ...editorOptions
}, ref) => {
    const componentId = id || `markdown-editor-${Math.random().toString(36).substr(2, 9)}`;

    // Utiliser le hook pour gérer toute la logique
    const { textareaRef, editorMethods } = useMarkdownEditor({
        value,
        onChange,
        placeholder,
        disabled,
        ...editorOptions
    });

    // Exposer les méthodes via ref
    useImperativeHandle(ref, () => editorMethods); return (
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

            <div className={`markdown-editor-container relative ${editorOptions.editorClassName || ''} ${error ? 'ring-2 ring-red-500 rounded-lg' : ''}`}>
                <textarea
                    ref={textareaRef}
                    id={componentId}
                    placeholder={placeholder}
                    rows={rows}
                    className="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none"
                    style={{ display: 'none' }}
                />
            </div>

            {error && (
                <p className="mt-2 text-sm text-red-600">
                    {error}
                </p>
            )}

            {helpText && !error && (
                <p className="mt-2 text-sm text-gray-500">
                    {helpText}
                </p>
            )}

            {/* Styles pour l'éditeur */}
            <style dangerouslySetInnerHTML={{
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
                `
            }} />
        </div>
    );
});

MarkdownEditor.displayName = 'MarkdownEditor';

export default MarkdownEditor;