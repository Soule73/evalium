import React from 'react';
import { EyeIcon, PencilIcon } from '@heroicons/react/24/outline';
import { Input } from '@examena/ui';
import MarkdownEditor from './MarkdownEditor';
import MarkdownRenderer from './MarkdownRenderer';

interface ChoiceEditorProps {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    required?: boolean;
    error?: string;
    readOnly?: boolean;
    className?: string;
    isMarkdownMode?: boolean;
    showPreview?: boolean;
    onToggleMarkdownMode?: () => void;
    onTogglePreview?: () => void;
}

const ChoiceEditor: React.FC<ChoiceEditorProps> = ({
    value,
    onChange,
    placeholder = "Saisissez le texte de cette option...",
    required = false,
    error,
    readOnly = false,
    className = "",
    isMarkdownMode = false,
    showPreview = false,
    onToggleMarkdownMode,
    onTogglePreview
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
                    title={isMarkdownMode ? "Basculer vers l'éditeur simple" : "Basculer vers l'éditeur Markdown"}
                >
                    {isMarkdownMode ? <PencilIcon className="w-3 h-3 mr-1" /> : <PencilIcon className="w-3 h-3 mr-1" />}
                    {isMarkdownMode ? 'Markdown' : 'Simple'}
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
                        title={showPreview ? "Masquer l'aperçu" : "Afficher l'aperçu"}
                    >
                        <EyeIcon className="w-3 h-3 mr-1" />
                        {showPreview ? 'Masquer' : 'Aperçu'}
                    </button>
                )}
            </div>

            <div className={showPreview && isMarkdownMode ? 'grid grid-cols-2 gap-4' : ''}>
                {/* Éditeur */}
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

                {/* Aperçu */}
                {showPreview && isMarkdownMode && (
                    <div className="border border-gray-200 rounded-md p-3 bg-gray-50">
                        <div className="text-xs text-gray-500 mb-2 font-medium">Aperçu :</div>
                        <div className="text-sm">
                            {value ? (
                                <MarkdownRenderer className="text-sm">
                                    {value}
                                </MarkdownRenderer>
                            ) : (
                                <span className="text-gray-400 italic">Aucun contenu</span>
                            )}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default ChoiceEditor;