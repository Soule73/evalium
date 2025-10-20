import { useEffect, useRef, useState, forwardRef, useImperativeHandle } from 'react';
import EasyMDE from 'easymde';
import 'easymde/dist/easymde.min.css';
import 'katex/dist/katex.min.css';

declare global {
    interface Window {
        EasyMDE: typeof EasyMDE;
    }
}

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

export interface MarkdownEditorRef {
    focus: () => void;
    getValue: () => string;
    setValue: (value: string) => void;
}

export const MarkdownEditor = forwardRef<MarkdownEditorRef, MarkdownEditorProps>(({
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
    editorClassName = '',

    // Upload d'images
    enableImageUpload = false,
    imageUploadFunction,
    imageUploadEndpoint,
    // 2MB par défaut
    imageMaxSize = 2048000,
}, ref) => {
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const editorRef = useRef<EasyMDE | null>(null);
    const [isReady, setIsReady] = useState(false);
    const componentId = id || `markdown-editor-${Math.random().toString(36).substr(2, 9)}`;

    useImperativeHandle(ref, () => ({
        focus: () => {
            if (editorRef.current) {
                const codemirror = (editorRef.current as any).codemirror;
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
    }));

    const debounce = (func: Function, delay: number) => {
        let timeoutId: NodeJS.Timeout;
        return (...args: any[]) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => func.apply(null, args), delay);
        };
    };

    const debouncedOnChange = debounce((newValue: string) => {
        if (onChange) {
            onChange(newValue);
        }
    }, 500);

    /**
     * Crée les actions personnalisées pour les formules mathématiques
     */
    const createMathActions = () => {
        return {
            mathInline: {
                name: "math-inline",
                action: (editor: any) => {
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
                action: (editor: any) => {
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
     * Construit la toolbar dynamiquement selon les props activées
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

    useEffect(() => {
        // Charger les bibliothèques globalement pour la prévisualisation
        const loadLibraries = async () => {
            try {
                if (typeof window !== 'undefined') {
                    // Charger marked si pas déjà disponible
                    if (!(window as any).marked) {
                        const { marked } = await import('marked');
                        (window as any).marked = marked;
                    }

                    // Charger KaTeX si les formules mathématiques sont activées
                    if ((enableMathInline || enableMathDisplay) && !(window as any).katex) {
                        const katex = await import('katex');
                        (window as any).katex = katex;
                    }
                }
            } catch (error) {
                console.warn('Impossible de charger les bibliothèques de prévisualisation:', error);
            }
        };

        loadLibraries();
    }, [enableMathInline, enableMathDisplay]);

    useEffect(() => {
        if (!textareaRef.current) return;

        const toolbar = disabled ? false : buildToolbar();
        const mathActions = createMathActions();

        // Configuration personnalisée de la toolbar avec les actions mathématiques
        let toolbarConfig: any = toolbar;
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
            // Configuration du rendu markdown avec support KaTeX
            previewRender: (plainText: string) => {
                try {
                    // Vérifier si KaTeX et marked sont disponibles globalement
                    const marked = (window as any).marked;
                    const katex = (window as any).katex;

                    if (marked) {
                        // Traitement des formules mathématiques si KaTeX est disponible
                        let processedText = plainText;

                        if (katex) {
                            // Formules display ($$...$$)
                            processedText = processedText.replace(/\$\$(.*?)\$\$/gs, (_, formula) => {
                                try {
                                    return katex.renderToString(formula.trim(), {
                                        displayMode: true,
                                        throwOnError: false
                                    });
                                } catch (e) {
                                    return `<div class="katex-error">Erreur: ${formula}</div>`;
                                }
                            });

                            // Formules inline ($...$)
                            processedText = processedText.replace(/\$([^$\n]+?)\$/g, (_, formula) => {
                                try {
                                    return katex.renderToString(formula.trim(), {
                                        displayMode: false,
                                        throwOnError: false
                                    });
                                } catch (e) {
                                    return `<span class="katex-error">Erreur: ${formula}</span>`;
                                }
                            });
                        } else {
                            // Si KaTeX n'est pas disponible, afficher les formules en code
                            processedText = processedText.replace(/\$\$(.*?)\$\$/gs, (_, formula) => {
                                return `<div class="math-display"><code>$$${formula}$$</code></div>`;
                            });
                            processedText = processedText.replace(/\$([^$\n]+?)\$/g, (_, formula) => {
                                return `<code class="math-inline">$${formula}$</code>`;
                            });
                        }

                        // Configuration de marked
                        marked.setOptions({
                            breaks: true,
                            gfm: true
                        });

                        // Conversion markdown
                        return marked.parse(processedText);
                    }

                    // Fallback basique si marked n'est pas disponible
                    return plainText.replace(/\n/g, '<br>');
                } catch (error) {
                    console.warn('Erreur lors du rendu de la prévisualisation:', error);
                    return plainText.replace(/\n/g, '<br>');
                }
            },
            shortcuts: {
                "toggleBold": enableBold ? "Ctrl-B" : null,
                "toggleItalic": enableItalic ? "Ctrl-I" : null,
                "togglePreview": enablePreview ? "Ctrl-P" : null,
                "toggleSideBySide": enableSideBySide ? "F9" : null,
                "toggleFullScreen": enableFullscreen ? "F11" : null,
            },
        } as any);

        editorRef.current = editor;
        setIsReady(true);

        setTimeout(() => {
            const container = textareaRef.current?.parentElement;
            const previewElements = container?.querySelectorAll('.editor-preview, .editor-preview-side');
            previewElements?.forEach(element => {
                element.classList.add('prose', 'prose-sm', 'max-w-none', 'p-4');
            });
        }, 100);

        editor.codemirror.on("change", () => {
            const newValue = editor.value();
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
        editorClassName
    ]);

    useEffect(() => {
        if (editorRef.current && isReady) {
            const currentValue = editorRef.current.value();
            if (currentValue !== value) {
                editorRef.current.value(value || '');
            }
        }
    }, [value, isReady]);

    useEffect(() => {
        if (editorRef.current && isReady) {
            const codemirror = (editorRef.current as any).codemirror;
            if (codemirror) {
                codemirror.setOption('readOnly', disabled);
            }
        }
    }, [disabled, isReady]);

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

            <div className={`markdown-editor-container relative ${editorClassName} ${error ? 'ring-2 ring-red-500 rounded-lg' : ''}`}>
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

            {/* Styles pour les formules mathématiques */}
            <style dangerouslySetInnerHTML={{
                __html: `
                .EasyMDEContainer .editor-preview .katex {
                    font-size: 1.1em;
                }
                .EasyMDEContainer .editor-preview .katex-display {
                    margin: 1rem 0;
                    text-align: center;
                }
                .EasyMDEContainer .editor-preview .katex-display .katex {
                    display: inline-block;
                    background: #f8fafc;
                    padding: 0.5rem 1rem;
                    border-radius: 0.5rem;
                    border: 1px solid #e2e8f0;
                }
                .EasyMDEContainer .editor-preview .katex-error {
                    color: #dc2626;
                    background: #fee2e2;
                    padding: 0.25rem 0.5rem;
                    border-radius: 0.25rem;
                    font-family: monospace;
                    font-size: 0.875rem;
                }
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
                .EasyMDEContainer .editor-preview .math-display {
                    margin: 1rem 0;
                    text-align: center;
                }
                .EasyMDEContainer .editor-preview .math-display code {
                    background: #f8fafc;
                    padding: 0.5rem 1rem;
                    border-radius: 0.5rem;
                    border: 1px solid #e2e8f0;
                    display: inline-block;
                    font-family: "Times New Roman", serif;
                }
                .EasyMDEContainer .editor-preview .math-inline {
                    background: #f1f5f9;
                    padding: 0.125rem 0.25rem;
                    border-radius: 0.25rem;
                    font-family: "Times New Roman", serif;
                    color: #374151;
                }
                `
            }} />
        </div>
    );
});

MarkdownEditor.displayName = 'MarkdownEditor';

export default MarkdownEditor;