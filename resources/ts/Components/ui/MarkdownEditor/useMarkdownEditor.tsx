import { useEffect, useRef, useState, useMemo } from 'react';
import { createRoot } from 'react-dom/client';
import EasyMDE from 'easymde';
import type { ToolbarButton } from 'easymde';
import MarkdownRenderer from '../MarkdownRenderer/MarkdownRenderer';

interface UseMarkdownEditorOptions {
    value?: string;
    onChange?: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;

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

export interface MarkdownEditorHandle {
    focus: () => void;
    getValue: () => string;
    setValue: (value: string) => void;
}

/**
 * Custom hook to manage the Markdown editor with EasyMDE
 */
export const useMarkdownEditor = (options: UseMarkdownEditorOptions) => {
    const {
        value = '',
        onChange,
        placeholder = 'Type your answer here...',
        disabled = false,

        // Toolbar features (enabled by default)
        enableBold = true,
        enableItalic = true,
        enableHeading = true,
        enableStrikethrough = false,
        enableCode = false,
        enableQuote = true,
        enableUnorderedList = true,
        enableOrderedList = true,
        enableLink = true,
        enableImage = false,
        enableTable = true,
        enableHorizontalRule = false,
        enablePreview = true,
        enableSideBySide = false,
        enableFullscreen = false,
        enableGuide = true,
        enableUndo = false,
        enableRedo = false,
        enableMathInline = false,
        enableMathDisplay = false,

        // Advanced options
        enableSpellChecker = false,
        enableStatus = false,
        enableAutofocus = false,
        enableLineNumbers = false,
        enableLineWrapping = true,
        tabSize = 4,
        minHeight,
        maxHeight,

        // Customization
        customToolbar,
        theme,

        // Image upload
        enableImageUpload = false,
        imageUploadFunction,
        imageUploadEndpoint,
        imageMaxSize = 2048000,
    } = options;

    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const editorRef = useRef<EasyMDE | null>(null);
    const [isReady, setIsReady] = useState(false);
    const isInternalChangeRef = useRef(false);
    const isInitializedRef = useRef(false);

    const debounce = <A extends unknown[]>(func: (...args: A) => void, delay: number) => {
        let timeoutId: NodeJS.Timeout;
        return (...args: A) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func(...args), delay);
        };
    };

    const debouncedOnChange = useMemo(
        () =>
            debounce((newValue: string) => {
                if (onChange) {
                    onChange(newValue);
                }
            }, 300),
        [onChange],
    );

    const createMathActions = () => {
        return {
            mathInline: {
                name: 'math-inline',
                action: (editor: EasyMDE) => {
                    const cm = editor.codemirror;
                    const selectedText = cm.getSelection();
                    const replaceText = selectedText ? `$${selectedText}$` : '$formula$';
                    cm.replaceSelection(replaceText);
                    if (!selectedText) {
                        const cursor = cm.getCursor();
                        cm.setCursor(cursor.line, cursor.ch - 1);
                    }
                },
                className: 'fa fa-calculator',
                title: 'Inline math formula ($...$)',
            },
            mathDisplay: {
                name: 'math-display',
                action: (editor: EasyMDE) => {
                    const cm = editor.codemirror;
                    const selectedText = cm.getSelection();
                    const replaceText = selectedText
                        ? `$$\n${selectedText}\n$$`
                        : '$$\nformula\n$$';
                    cm.replaceSelection(replaceText);
                    if (!selectedText) {
                        const cursor = cm.getCursor();
                        cm.setCursor(cursor.line - 1, 7);
                    }
                },
                className: 'fa fa-superscript',
                title: 'Display math formula ($$...$$)',
            },
        };
    };

    /**
     * Builds the toolbar dynamically based on enabled options
     */
    const buildToolbar = (): (string | '|')[] => {
        if (customToolbar) {
            return customToolbar;
        }

        const toolbar: (string | '|')[] = [];

        // Basic formatting group
        const basicFormatting: string[] = [];
        if (enableBold) basicFormatting.push('bold');
        if (enableItalic) basicFormatting.push('italic');
        if (enableStrikethrough) basicFormatting.push('strikethrough');
        if (enableCode) basicFormatting.push('code');
        if (enableHeading) basicFormatting.push('heading');

        if (basicFormatting.length > 0) {
            toolbar.push(...basicFormatting);
            toolbar.push('|');
        }

        // Lists and quotes group
        const listsAndQuotes: string[] = [];
        if (enableQuote) listsAndQuotes.push('quote');
        if (enableUnorderedList) listsAndQuotes.push('unordered-list');
        if (enableOrderedList) listsAndQuotes.push('ordered-list');

        if (listsAndQuotes.length > 0) {
            toolbar.push(...listsAndQuotes);
            toolbar.push('|');
        }

        // Insertion group
        const insertions: string[] = [];
        if (enableLink) insertions.push('link');
        if (enableImage) insertions.push('image');
        if (enableTable) insertions.push('table');
        if (enableHorizontalRule) insertions.push('horizontal-rule');

        if (insertions.length > 0) {
            toolbar.push(...insertions);
            toolbar.push('|');
        }

        // Math formulas group
        const mathFormulas: string[] = [];
        if (enableMathInline) mathFormulas.push('math-inline');
        if (enableMathDisplay) mathFormulas.push('math-display');

        if (mathFormulas.length > 0) {
            toolbar.push(...mathFormulas);
            toolbar.push('|');
        }

        // Actions group
        const actions: string[] = [];
        if (enableUndo) actions.push('undo');
        if (enableRedo) actions.push('redo');

        if (actions.length > 0) {
            toolbar.push(...actions);
            toolbar.push('|');
        }

        // Display group
        const display: string[] = [];
        if (enablePreview) display.push('preview');
        if (enableSideBySide) display.push('side-by-side');
        if (enableFullscreen) display.push('fullscreen');

        if (display.length > 0) {
            toolbar.push(...display);
            toolbar.push('|');
        }

        // Guide
        if (enableGuide) {
            toolbar.push('guide');
        }

        // Remove the trailing separator if present
        if (toolbar[toolbar.length - 1] === '|') {
            toolbar.pop();
        }

        return toolbar;
    };

    /**
     * Custom preview rendering function
     */
    const renderPreview = (plainText: string, preview: HTMLElement) => {
        const container = document.createElement('div');
        const root = createRoot(container);

        root.render(<MarkdownRenderer>{plainText}</MarkdownRenderer>);

        setTimeout(() => {
            preview.innerHTML = container.innerHTML;
            root.unmount();
        }, 0);

        return 'Loading...';
    };

    /**
     * EasyMDE editor initialization
     */
    useEffect(() => {
        if (!textareaRef.current) return;

        const toolbar = disabled ? false : buildToolbar();
        const mathActions = createMathActions();

        let toolbarConfig: false | (string | '|' | ToolbarButton)[] = toolbar;
        if (toolbar && !disabled) {
            toolbarConfig = (toolbar as string[]).map((item) => {
                if (item === 'math-inline') return mathActions.mathInline;
                if (item === 'math-display') return mathActions.mathDisplay;
                return item;
            });
        }

        const editor = new EasyMDE({
            element: textareaRef.current,
            placeholder: placeholder,
            spellChecker: enableSpellChecker,
            autofocus: enableAutofocus,
            status: enableStatus,
            initialValue: value || '',
            toolbar: toolbarConfig,
            lineNumbers: enableLineNumbers,
            lineWrapping: enableLineWrapping,
            tabSize: tabSize,
            minHeight: minHeight,
            maxHeight: maxHeight,
            theme: theme,
            uploadImage: enableImageUpload,
            imageUploadFunction: imageUploadFunction,
            imageUploadEndpoint: imageUploadEndpoint,
            imageMaxSize: imageMaxSize,
            previewRender: renderPreview,
            shortcuts: {
                toggleBold: enableBold ? 'Ctrl-B' : null,
                toggleItalic: enableItalic ? 'Ctrl-I' : null,
                togglePreview: enablePreview ? 'Ctrl-P' : null,
                toggleSideBySide: enableSideBySide ? 'F9' : null,
                toggleFullScreen: enableFullscreen ? 'F11' : null,
            },
        });

        editorRef.current = editor;
        setIsReady(true);

        editor.codemirror.on('change', () => {
            const newValue = editor.value();
            isInternalChangeRef.current = true;
            debouncedOnChange(newValue);
        });

        return () => {
            if (editorRef.current) {
                editorRef.current.toTextArea();
                editorRef.current = null;
            }
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [
        placeholder,
        disabled,
        enableSpellChecker,
        enableAutofocus,
        enableStatus,
        enableLineNumbers,
        enableLineWrapping,
        tabSize,
        minHeight,
        maxHeight,
        theme,
        enableImageUpload,
        imageUploadFunction,
        imageUploadEndpoint,
        imageMaxSize,
        // Toolbar props
        enableBold,
        enableItalic,
        enableHeading,
        enableStrikethrough,
        enableCode,
        enableQuote,
        enableUnorderedList,
        enableOrderedList,
        enableLink,
        enableImage,
        enableTable,
        enableHorizontalRule,
        enablePreview,
        enableSideBySide,
        enableFullscreen,
        enableGuide,
        enableUndo,
        enableRedo,
        enableMathInline,
        enableMathDisplay,
        customToolbar,
    ]);

    /**
     * Sync value only when it comes from outside
     */
    useEffect(() => {
        if (editorRef.current && isReady) {
            if (!isInternalChangeRef.current && !isInitializedRef.current) {
                const currentValue = editorRef.current.value();
                if (currentValue !== value) {
                    editorRef.current.value(value || '');
                    isInitializedRef.current = true;
                }
            }
        }
        isInternalChangeRef.current = false;
    }, [value, isReady]);

    useEffect(() => {
        if (editorRef.current && isReady) {
            const codemirror = editorRef.current.codemirror;
            if (codemirror) {
                codemirror.setOption('readOnly', disabled);
            }
        }
    }, [disabled, isReady]);

    const editorMethods: MarkdownEditorHandle = {
        focus: () => {
            if (editorRef.current) {
                const codemirror = editorRef.current.codemirror;
                if (codemirror) {
                    codemirror.focus();
                }
            }
        },
        getValue: () => {
            return editorRef.current ? editorRef.current.value() : value;
        },
        setValue: (newValue: string) => {
            if (editorRef.current) {
                editorRef.current.value(newValue);
            }
            if (onChange) {
                onChange(newValue);
            }
        },
    };

    return {
        textareaRef,
        editorMethods,
        isReady,
    };
};
