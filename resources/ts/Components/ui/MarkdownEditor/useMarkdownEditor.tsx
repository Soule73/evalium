import { useEffect, useRef, useState, useMemo } from 'react';
import { createRoot } from 'react-dom/client';
import EasyMDE from 'easymde';
import MarkdownRenderer from '../MarkdownRenderer/MarkdownRenderer';

interface UseMarkdownEditorOptions {
    value?: string;
    onChange?: (value: string) => void;
    placeholder?: string;
    disabled?: boolean;

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

export interface MarkdownEditorHandle {
    focus: () => void;
    getValue: () => string;
    setValue: (value: string) => void;
}

/**
 * Hook personnalisé pour gérer l'éditeur Markdown avec EasyMDE
 */
export const useMarkdownEditor = (options: UseMarkdownEditorOptions) => {
    const {
        value = '',
        onChange,
        placeholder = 'Saisissez votre réponse ici...',
        disabled = false,

        // Fonctionnalités de la toolbar (activées par défaut)
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

        // Options avancées
        enableSpellChecker = false,
        enableStatus = false,
        enableAutofocus = false,
        enableLineNumbers = false,
        enableLineWrapping = true,
        tabSize = 4,
        minHeight,
        maxHeight,

        // Personnalisation
        customToolbar,
        theme,

        // Upload d'images
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

    const debounce = (func: Function, delay: number) => {
        let timeoutId: NodeJS.Timeout;
        return (...args: unknown[]) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(null, args), delay);
        };
    };

    const debouncedOnChange = useMemo(
        () => debounce((newValue: string) => {
            if (onChange) {
                onChange(newValue);
            }
        }, 300),
        [onChange]
    );

    const createMathActions = () => {
        return {
            mathInline: {
                name: "math-inline",
                action: (editor: EasyMDE) => {
                    const cm = editor.codemirror;
                    const selectedText = cm.getSelection();
                    const replaceText = selectedText ? `$${selectedText}$` : '$formule$';
                    cm.replaceSelection(replaceText);
                    if (!selectedText) {
                        const cursor = cm.getCursor();
                        cm.setCursor(cursor.line, cursor.ch - 1);
                    }
                },
                className: "fa fa-calculator",
                title: "Formule mathématique inline ($...$)",
            },
            mathDisplay: {
                name: "math-display",
                action: (editor: EasyMDE) => {
                    const cm = editor.codemirror;
                    const selectedText = cm.getSelection();
                    const replaceText = selectedText ? `$$\n${selectedText}\n$$` : '$$\nformule\n$$';
                    cm.replaceSelection(replaceText);
                    if (!selectedText) {
                        const cursor = cm.getCursor();
                        cm.setCursor(cursor.line - 1, 7);
                    }
                },
                className: "fa fa-superscript",
                title: "Formule mathématique display ($$...$$)",
            }
        };
    };

    /**
     * Construit la toolbar dynamiquement selon les options activées
     */
    const buildToolbar = (): (string | '|')[] => {
        if (customToolbar) {
            return customToolbar;
        }

        const toolbar: (string | '|')[] = [];

        // Groupe formatage de base
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

        // Groupe listes et citations
        const listsAndQuotes: string[] = [];
        if (enableQuote) listsAndQuotes.push('quote');
        if (enableUnorderedList) listsAndQuotes.push('unordered-list');
        if (enableOrderedList) listsAndQuotes.push('ordered-list');

        if (listsAndQuotes.length > 0) {
            toolbar.push(...listsAndQuotes);
            toolbar.push('|');
        }

        // Groupe insertion
        const insertions: string[] = [];
        if (enableLink) insertions.push('link');
        if (enableImage) insertions.push('image');
        if (enableTable) insertions.push('table');
        if (enableHorizontalRule) insertions.push('horizontal-rule');

        if (insertions.length > 0) {
            toolbar.push(...insertions);
            toolbar.push('|');
        }

        // Groupe formules mathématiques
        const mathFormulas: string[] = [];
        if (enableMathInline) mathFormulas.push('math-inline');
        if (enableMathDisplay) mathFormulas.push('math-display');

        if (mathFormulas.length > 0) {
            toolbar.push(...mathFormulas);
            toolbar.push('|');
        }

        // Groupe actions
        const actions: string[] = [];
        if (enableUndo) actions.push('undo');
        if (enableRedo) actions.push('redo');

        if (actions.length > 0) {
            toolbar.push(...actions);
            toolbar.push('|');
        }

        // Groupe affichage
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

        // Supprimer le dernier séparateur s'il existe
        if (toolbar[toolbar.length - 1] === '|') {
            toolbar.pop();
        }

        return toolbar;
    };

    /**
     * Fonction de rendu personnalisée pour la prévisualisation
     */
    const renderPreview = (plainText: string, preview: HTMLElement) => {
        // Créer un conteneur temporaire pour le rendu React
        const container = document.createElement('div');
        const root = createRoot(container);

        // Rendre le MarkdownRenderer dans le conteneur
        root.render(<MarkdownRenderer>{plainText}</MarkdownRenderer>);

        // Attendre que le rendu soit terminé, puis copier le contenu
        setTimeout(() => {
            preview.innerHTML = container.innerHTML;
            root.unmount();
        }, 0);

        return "Chargement...";
    };

    /**
     * Initialisation de l'éditeur EasyMDE
     */
    useEffect(() => {
        if (!textareaRef.current) return;

        const toolbar = disabled ? false : buildToolbar();
        const mathActions = createMathActions();

        let toolbarConfig: false | (string | '|' | import('easymde').ToolbarButton)[] = toolbar;
        if (toolbar && !disabled) {
            toolbarConfig = (toolbar as string[]).map(item => {
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
                "toggleBold": enableBold ? "Ctrl-B" : null,
                "toggleItalic": enableItalic ? "Ctrl-I" : null,
                "togglePreview": enablePreview ? "Ctrl-P" : null,
                "toggleSideBySide": enableSideBySide ? "F9" : null,
                "toggleFullScreen": enableFullscreen ? "F11" : null,
            },
        });

        editorRef.current = editor;
        setIsReady(true);

        editor.codemirror.on("change", () => {
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
     * Synchroniser la valeur uniquement si elle vient de l'extérieur
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
        }
    };

    return {
        textareaRef,
        editorMethods,
        isReady
    };
};
