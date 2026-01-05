import React from 'react';
import { EyeIcon, PencilIcon } from '@heroicons/react/24/outline';
import { Input } from '@examena/ui';
import MarkdownEditor from '../MarkdownEditor/MarkdownEditor';
import MarkdownRenderer from '../MarkdownRenderer/MarkdownRenderer';

interface ChoiceEditorProps {
    value: string;
    onChange: (value: string) => void;
    required?: boolean;
    error?: string;
    readOnly?: boolean;
    className?: string;
    isMarkdownMode?: boolean;
    showPreview?: boolean;
    onToggleMarkdownMode?: () => void;
    onTogglePreview?: () => void;
    placeholder?: string;
    simpleModeLabel?: string;
    markdownModeLabel?: string;
    previewLabel?: string;
    hideLabel?: string;
    previewHeaderLabel?: string;
    noContentLabel?: string;
    switchToSimpleTitle?: string;
    switchToMarkdownTitle?: string;
    showPreviewTitle?: string;
    hidePreviewTitle?: string;
}

const ChoiceEditor: React.FC<ChoiceEditorProps> = ({
    value,
    onChange,
    required = false,
    error,
    readOnly = false,
    className = "",
    isMarkdownMode = false,
    showPreview = false,
    onToggleMarkdownMode,
    onTogglePreview,
    placeholder = 'Enter your answer...',
    simpleModeLabel = 'Simple',
    markdownModeLabel = 'Markdown',
    previewLabel = 'Preview',
    hideLabel = 'Hide',
    previewHeaderLabel = 'Preview:',
    noContentLabel = 'No content',
    switchToSimpleTitle = 'Switch to simple editor',
    switchToMarkdownTitle = 'Switch to Markdown editor',
    showPreviewTitle = 'Show preview',
    hidePreviewTitle = 'Hide preview'
}) => {

    if (readOnly) {
        return (
            <Input
                type="text"
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                className={`flex-1 text-sm ${className}`}
                required={required}
                error={error}
                readOnly={readOnly}
            />
        );
    }

    return (
        <div className={`flex-1 ${className}`}>
            <div className="flex items-center space-x-2 mb-2">
                <button
                    type="button"
                    onClick={() => {
                        if (onToggleMarkdownMode) {
                            onToggleMarkdownMode();
                        }
                    }}
                    className={`inline-flex items-center px-2 py-1 text-xs font-medium rounded-md transition-colors ${isMarkdownMode
                        ? 'bg-blue-100 text-blue-700 hover:bg-blue-200'
                        : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                        }`}
                    title={isMarkdownMode ? switchToSimpleTitle : switchToMarkdownTitle}
                >
                    {isMarkdownMode ? <PencilIcon className="w-3 h-3 mr-1" /> : <PencilIcon className="w-3 h-3 mr-1" />}
                    {isMarkdownMode ? markdownModeLabel : simpleModeLabel}
                </button>

                {isMarkdownMode && (
                    <button
                        type="button"
                        onClick={() => {
                            if (onTogglePreview) {
                                onTogglePreview();
                            }
                        }}
                        className={`inline-flex items-center px-2 py-1 text-xs font-medium rounded-md transition-colors ${showPreview
                            ? 'bg-green-100 text-green-700 hover:bg-green-200'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            }`}
                        title={showPreview ? hidePreviewTitle : showPreviewTitle}
                    >
                        <EyeIcon className="w-3 h-3 mr-1" />
                        {showPreview ? hideLabel : previewLabel}
                    </button>
                )}
            </div>

            <div className={showPreview && isMarkdownMode ? 'grid grid-cols-2 gap-4' : ''}>
                <div>
                    {isMarkdownMode ? (
                        <MarkdownEditor
                            value={value}
                            onChange={onChange}
                            placeholder={placeholder}
                            required={required}
                            error={error}
                            rows={3}
                            className="text-sm"
                        />
                    ) : (
                        <Input
                            type="text"
                            value={value}
                            onChange={(e) => onChange(e.target.value)}
                            placeholder={placeholder}
                            className="text-sm"
                            required={required}
                            error={error}
                        />
                    )}
                </div>

                {showPreview && isMarkdownMode && (
                    <div className="border border-gray-200 rounded-md p-3 bg-gray-50">
                        <div className="text-xs text-gray-500 mb-2 font-medium">{previewHeaderLabel}</div>
                        <div className="text-sm">
                            {value ? (
                                <MarkdownRenderer className="text-sm">
                                    {value}
                                </MarkdownRenderer>
                            ) : (
                                <span className="text-gray-400 italic">{noContentLabel}</span>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default ChoiceEditor;
