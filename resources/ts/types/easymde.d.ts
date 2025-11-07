declare module 'easymde' {
    export interface CodeMirror {
        getSelection(): string;
        replaceSelection(replacement: string): void;
        getCursor(): { line: number; ch: number };
        setCursor(line: number, ch: number): void;
        getLine(line: number): string;
        replaceRange(replacement: string, from: { line: number; ch: number }, to?: { line: number; ch: number }): void;
        setValue(value: string): void;
        getValue(): string;
        focus(): void;
        refresh(): void;
        on(event: string, handler: () => void): void;
        setOption(option: string, value: unknown): void;
    }

    export interface ToolbarButton {
        name: string;
        action: (editor: EasyMDE) => void;
        className?: string;
        title?: string;
        default?: boolean;
    }

    export interface EasyMDEOptions {
        element?: HTMLElement;
        initialValue?: string;
        placeholder?: string;
        previewRender?: (plainText: string, preview: HTMLElement) => string;
        toolbar?: (string | '|' | ToolbarButton)[] | false;
        status?: false | boolean | Array<string | {
            className: string;
            defaultValue: (el: HTMLElement) => void;
            onUpdate: (el: HTMLElement) => void;
        }>;
        spellChecker?: boolean;
        autofocus?: boolean;
        lineNumbers?: boolean;
        lineWrapping?: boolean;
        tabSize?: number;
        minHeight?: string;
        maxHeight?: string;
        uploadImage?: boolean;
        imageUploadFunction?: (file: File, onSuccess: (url: string) => void, onError: (error: string) => void) => void;
        imageUploadEndpoint?: string;
        imageMaxSize?: number;
        forceSync?: boolean;
        sideBySideFullscreen?: boolean;
        theme?: string;
        shortcuts?: Record<string, string | null>;
    }

    class EasyMDE {
        constructor(options: EasyMDEOptions);
        codemirror: CodeMirror;
        value(): string;
        value(val: string): void;
        toTextArea(): void;
        isPreviewActive(): boolean;
        isSideBySideActive(): boolean;
        isFullscreenActive(): boolean;
        clearAutosavedValue(): void;
    }

    export default EasyMDE;
}
