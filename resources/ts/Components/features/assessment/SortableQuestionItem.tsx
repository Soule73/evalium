import React from 'react';
import {
    PlusIcon,
    TrashIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    Bars3Icon,
    PaperClipIcon,
} from '@heroicons/react/24/outline';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { type QuestionFormData, type ChoiceFormData } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { MarkdownEditor, ChoiceEditor } from '@evalium/ui';
import { Checkbox, Input } from '@/Components/ui';

interface SortableQuestionItemProps {
    question: QuestionFormData;
    index: number;
    isCollapsed: boolean;
    onToggleCollapse: (index: number) => void;
    onRemoveQuestion: (index: number) => void;
    onUpdateQuestion: (
        index: number,
        field: keyof QuestionFormData,
        value: QuestionFormData[keyof QuestionFormData],
    ) => void;
    onAddChoice: (index: number) => void;
    onRemoveChoice: (questionIndex: number, choiceIndex: number) => void;
    onUpdateChoice: (
        questionIndex: number,
        choiceIndex: number,
        field: keyof ChoiceFormData,
        value: ChoiceFormData[keyof ChoiceFormData],
    ) => void;
    getQuestionTypeLabel: (type: string) => string;
    getQuestionTypeIcon: (type: string) => {
        icon: React.ComponentType<{ className?: string }>;
        bgColor: string;
        textColor: string;
    } | null;
    errors?: Record<string, string>;
}

const SortableQuestion: React.FC<SortableQuestionItemProps> = ({
    question,
    index,
    isCollapsed,
    onToggleCollapse,
    onRemoveQuestion,
    onUpdateQuestion,
    onAddChoice,
    onRemoveChoice,
    onUpdateChoice,
    getQuestionTypeLabel,
    getQuestionTypeIcon,
    errors = {},
}) => {
    const { t } = useTranslations();
    const [choiceStates, setChoiceStates] = React.useState<
        Record<
            number,
            {
                isMarkdownMode: boolean;
                showPreview: boolean;
            }
        >
    >({});

    const toggleChoiceMarkdownMode = (choiceIndex: number) => {
        setChoiceStates((prev) => ({
            ...prev,
            [choiceIndex]: {
                isMarkdownMode: !prev[choiceIndex]?.isMarkdownMode,
                showPreview: false,
            },
        }));
    };

    const toggleChoicePreview = (choiceIndex: number) => {
        setChoiceStates((prev) => ({
            ...prev,
            [choiceIndex]: {
                ...prev[choiceIndex],
                showPreview: !prev[choiceIndex]?.showPreview,
            },
        }));
    };
    const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
        id: index.toString(),
    });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
    };

    return (
        <div
            ref={setNodeRef}
            style={style}
            className={`border border-gray-200 rounded-xl bg-white overflow-hidden ${isDragging ? 'shadow-lg' : ''}`}
        >
            <div className="px-6 py-4 bg-gray-50 flex items-center justify-between">
                <div className="flex items-center space-x-4">
                    <button
                        type="button"
                        className="cursor-grab active:cursor-grabbing p-1 text-gray-400 hover:text-gray-600 transition-colors"
                        {...attributes}
                        {...listeners}
                    >
                        <Bars3Icon className="h-5 w-5" />
                    </button>

                    <button
                        type="button"
                        onClick={() => onToggleCollapse(index)}
                        className="flex items-center space-x-3 cursor-pointer text-left hover:text-indigo-600 transition-colors"
                    >
                        {isCollapsed ? (
                            <ChevronRightIcon className="h-5 w-5 text-gray-400" />
                        ) : (
                            <ChevronDownIcon className="h-5 w-5 text-gray-400" />
                        )}

                        {(() => {
                            const iconConfig = getQuestionTypeIcon(question.type);
                            if (!iconConfig) return null;
                            const { icon: Icon, bgColor, textColor } = iconConfig;
                            return (
                                <div
                                    className={`flex items-center justify-center w-6 h-6 ${bgColor} ${textColor} rounded`}
                                >
                                    <Icon className="w-4 h-4" />
                                </div>
                            );
                        })()}
                        <div className="font-medium text-gray-900">
                            Question {index + 1} - {getQuestionTypeLabel(question.type)}
                        </div>
                    </button>
                </div>

                <div className="flex items-center space-x-2">
                    <div className="flex items-center space-x-2">
                        <Input
                            label=""
                            type="number"
                            min="1"
                            max="100"
                            value={question.points}
                            onChange={(e) =>
                                onUpdateQuestion(index, 'points', parseInt(e.target.value))
                            }
                            className="w-16! p-1! text-sm text-center"
                            error={errors[`questions.${index}.points`]}
                        />
                        <span className="text-sm text-gray-500">
                            point{question.points > 1 ? 's' : ''}
                        </span>
                    </div>
                    <button
                        type="button"
                        onClick={() => onRemoveQuestion(index)}
                        className="p-1 cursor-pointer text-red-400 hover:text-red-600 transition-colors"
                    >
                        <TrashIcon className="h-4 w-4" />
                    </button>
                </div>
            </div>

            {!isCollapsed && (
                <div className="p-6 space-y-6">
                    <MarkdownEditor
                        key={`question-content-${question.id || index}`}
                        value={question.content}
                        onChange={(value) => onUpdateQuestion(index, 'content', value)}
                        placeholder={t('components.question_item.question_placeholder')}
                        required
                        label={t('components.question_item.question_statement')}
                        rows={4}
                        helpText={t('components.question_item.question_help')}
                        error={errors[`questions.${index}.content`]}
                    />

                    {question.type === 'file' && (
                        <div className="rounded-lg bg-orange-50 border border-orange-200 p-4 flex items-start space-x-3">
                            <PaperClipIcon className="h-5 w-5 text-orange-500 mt-0.5 flex-shrink-0" />
                            <div>
                                <p className="text-sm font-medium text-orange-800">
                                    {t('components.question_item.file_upload_info_title')}
                                </p>
                                <p className="text-sm text-orange-700 mt-1">
                                    {t('components.question_item.file_upload_info_subtitle')}
                                </p>
                            </div>
                        </div>
                    )}

                    {(question.type === 'multiple' ||
                        question.type === 'one_choice' ||
                        question.type === 'boolean') && (
                        <div className="space-y-4">
                            <div className="flex justify-between items-center">
                                <label className="block text-xs font-medium text-gray-700 uppercase tracking-wide">
                                    {t('components.question_item.answer_options')}
                                </label>
                                {question.type !== 'boolean' && (
                                    <button
                                        type="button"
                                        onClick={() => onAddChoice(index)}
                                        className="inline-flex items-center px-3 py-1 border border-gray-200 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                                    >
                                        <PlusIcon className="h-3 w-3 mr-1" />
                                        {t('components.question_item.add_option')}
                                    </button>
                                )}
                            </div>

                            {errors[`questions.${index}.choices`] && (
                                <div className="text-red-600 text-sm mt-1">
                                    {errors[`questions.${index}.choices`]}
                                </div>
                            )}

                            <div className="space-y-3">
                                {question.choices.map((choice, choiceIndex) =>
                                    question.type === 'multiple' ? (
                                        <QuestionMultipleItem
                                            key={choice.id || `choice-${choiceIndex}`}
                                            choice={choice}
                                            index={index}
                                            choiceIndex={choiceIndex}
                                            onUpdateChoice={onUpdateChoice}
                                            onRemoveChoice={onRemoveChoice}
                                            showDeleteButton={question.choices.length > 2}
                                            error={
                                                errors[
                                                    `questions.${index}.choices.${choiceIndex}.content`
                                                ]
                                            }
                                            isMarkdownMode={
                                                choiceStates[choiceIndex]?.isMarkdownMode || false
                                            }
                                            showPreview={
                                                choiceStates[choiceIndex]?.showPreview || false
                                            }
                                            onToggleMarkdownMode={() =>
                                                toggleChoiceMarkdownMode(choiceIndex)
                                            }
                                            onTogglePreview={() => toggleChoicePreview(choiceIndex)}
                                        />
                                    ) : question.type === 'one_choice' ? (
                                        <QuestionSingleItem
                                            key={choice.id || `choice-${choiceIndex}`}
                                            choice={choice}
                                            index={index}
                                            choiceIndex={choiceIndex}
                                            onUpdateChoice={onUpdateChoice}
                                            onRemoveChoice={onRemoveChoice}
                                            showDeleteButton={question.choices.length > 2}
                                            error={
                                                errors[
                                                    `questions.${index}.choices.${choiceIndex}.content`
                                                ]
                                            }
                                            isMarkdownMode={
                                                choiceStates[choiceIndex]?.isMarkdownMode || false
                                            }
                                            showPreview={
                                                choiceStates[choiceIndex]?.showPreview || false
                                            }
                                            onToggleMarkdownMode={() =>
                                                toggleChoiceMarkdownMode(choiceIndex)
                                            }
                                            onTogglePreview={() => toggleChoicePreview(choiceIndex)}
                                        />
                                    ) : (
                                        question.type === 'boolean' && (
                                            <QuestionBooleanItem
                                                key={choice.id || `choice-${choiceIndex}`}
                                                choice={choice}
                                                index={index}
                                                choiceIndex={choiceIndex}
                                                onUpdateChoice={onUpdateChoice}
                                                error={
                                                    errors[
                                                        `questions.${index}.choices.${choiceIndex}.content`
                                                    ]
                                                }
                                            />
                                        )
                                    ),
                                )}
                            </div>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};

interface QuestionMultipleItemProps {
    choice: ChoiceFormData;
    index: number;
    choiceIndex: number;
    showDeleteButton?: boolean;
    error: string | undefined;
    isMarkdownMode?: boolean;
    showPreview?: boolean;
    onToggleMarkdownMode?: () => void;
    onTogglePreview?: () => void;
    onUpdateChoice: (
        questionIndex: number,
        choiceIndex: number,
        field: keyof ChoiceFormData,
        value: ChoiceFormData[keyof ChoiceFormData],
    ) => void;
    onRemoveChoice: (questionIndex: number, choiceIndex: number) => void;
}

const QuestionMultipleItem: React.FC<QuestionMultipleItemProps> = ({
    choice,
    index,
    choiceIndex,
    onUpdateChoice,
    showDeleteButton = true,
    onToggleMarkdownMode,
    onTogglePreview,
    isMarkdownMode,
    showPreview,
    error,
    onRemoveChoice,
}) => {
    const { t } = useTranslations();

    const translations = {
        placeholders: t('components.choice_editor.placeholders'),
        simple: t('components.choice_editor.simple'),
        markdown: t('components.choice_editor.markdown'),
        preview: t('components.choice_editor.preview'),
        hide: t('components.choice_editor.hide'),
        previewLabel: t('components.choice_editor.preview_label'),
        noContent: t('components.choice_editor.no_content'),
        switchSimple: t('components.choice_editor.switch_simple'),
        switchMarkdown: t('components.choice_editor.switch_markdown'),
        showPreview: t('components.choice_editor.show_preview'),
        hidePreview: t('components.choice_editor.hide_preview'),
    };
    return (
        <div
            key={choice.id || `choice-${choiceIndex}`}
            className="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border border-gray-100"
        >
            <Checkbox
                checked={choice.is_correct}
                onChange={(e) => onUpdateChoice(index, choiceIndex, 'is_correct', e.target.checked)}
                type="checkbox"
                className="shrink-0"
            />
            <ChoiceEditor
                key={`choice-editor-${index}-${choiceIndex}-${choice.id || choiceIndex}`}
                value={choice.content}
                onChange={(value) => onUpdateChoice(index, choiceIndex, 'content', value)}
                required
                error={error}
                className="flex-1"
                isMarkdownMode={isMarkdownMode || false}
                showPreview={showPreview || false}
                onToggleMarkdownMode={onToggleMarkdownMode}
                onTogglePreview={onTogglePreview}
                placeholder={translations.placeholders}
                simpleModeLabel={translations.simple}
                markdownModeLabel={translations.markdown}
                previewLabel={translations.preview}
                hideLabel={translations.hide}
                previewHeaderLabel={translations.previewLabel}
                noContentLabel={translations.noContent}
                switchToSimpleTitle={translations.switchSimple}
                switchToMarkdownTitle={translations.switchMarkdown}
                showPreviewTitle={translations.showPreview}
                hidePreviewTitle={translations.hidePreview}
            />

            {showDeleteButton && (
                <button
                    type="button"
                    onClick={() => onRemoveChoice(index, choiceIndex)}
                    className="p-1 cursor-pointer text-red-400 hover:text-red-600 transition-colors"
                >
                    <TrashIcon className="h-4 w-4" />
                </button>
            )}
        </div>
    );
};

interface QuestionSingleItemProps {
    choice: ChoiceFormData;
    index: number;
    choiceIndex: number;
    showDeleteButton?: boolean;
    error: string | undefined;
    isMarkdownMode?: boolean;
    showPreview?: boolean;
    onToggleMarkdownMode?: () => void;
    onTogglePreview?: () => void;
    onUpdateChoice: (
        questionIndex: number,
        choiceIndex: number,
        field: keyof ChoiceFormData,
        value: ChoiceFormData[keyof ChoiceFormData],
    ) => void;
    onRemoveChoice: (questionIndex: number, choiceIndex: number) => void;
}

const QuestionSingleItem: React.FC<QuestionSingleItemProps> = ({
    choice,
    index,
    choiceIndex,
    onUpdateChoice,
    showDeleteButton = true,
    error,
    isMarkdownMode,
    showPreview,
    onToggleMarkdownMode,
    onTogglePreview,
    onRemoveChoice,
}) => {
    const { t } = useTranslations();

    return (
        <div className="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
            <Checkbox
                checked={choice.is_correct}
                onChange={(e) => onUpdateChoice(index, choiceIndex, 'is_correct', e.target.checked)}
                type="radio"
                name={`question_${index}_correct`}
                className="shrink-0"
            />
            <ChoiceEditor
                key={`choice-editor-${index}-${choiceIndex}-${choice.id || choiceIndex}`}
                value={choice.content}
                onChange={(value) => onUpdateChoice(index, choiceIndex, 'content', value)}
                required
                error={error}
                className="flex-1"
                isMarkdownMode={isMarkdownMode || false}
                showPreview={showPreview || false}
                onToggleMarkdownMode={onToggleMarkdownMode}
                onTogglePreview={onTogglePreview}
                placeholder={t('components.choice_editor.placeholders')}
                simpleModeLabel={t('components.choice_editor.simple')}
                markdownModeLabel={t('components.choice_editor.markdown')}
                previewLabel={t('components.choice_editor.preview')}
                hideLabel={t('components.choice_editor.hide')}
                previewHeaderLabel={t('components.choice_editor.preview_label')}
                noContentLabel={t('components.choice_editor.no_content')}
                switchToSimpleTitle={t('components.choice_editor.switch_simple')}
                switchToMarkdownTitle={t('components.choice_editor.switch_markdown')}
                showPreviewTitle={t('components.choice_editor.show_preview')}
                hidePreviewTitle={t('components.choice_editor.hide_preview')}
            />

            {showDeleteButton && (
                <button
                    type="button"
                    onClick={() => onRemoveChoice(index, choiceIndex)}
                    className="p-1 cursor-pointer text-red-400 hover:text-red-600 transition-colors"
                >
                    <TrashIcon className="h-4 w-4" />
                </button>
            )}
        </div>
    );
};

interface QuestionBooleanItemProps {
    choice: ChoiceFormData;
    index: number;
    choiceIndex: number;
    error?: string;
    onUpdateChoice: (
        questionIndex: number,
        choiceIndex: number,
        field: keyof ChoiceFormData,
        value: ChoiceFormData[keyof ChoiceFormData],
    ) => void;
}

const QuestionBooleanItem: React.FC<QuestionBooleanItemProps> = ({
    choice,
    index,
    choiceIndex,
    onUpdateChoice,
    error,
}) => {
    const { t } = useTranslations();

    return (
        <div
            key={choice.id || `choice-${choiceIndex}`}
            className="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border border-gray-100"
        >
            <Checkbox
                checked={choice.is_correct}
                onChange={(e) => onUpdateChoice(index, choiceIndex, 'is_correct', e.target.checked)}
                type="radio"
                name={`question_${index}_correct`}
                className="shrink-0"
            />

            <Input
                type="text"
                value={
                    choice.content === 'true'
                        ? t('components.question_item.true')
                        : t('components.question_item.false')
                }
                onChange={() => {}}
                className="flex-1 text-sm"
                required
                error={error}
                readOnly={true}
            />
        </div>
    );
};

export const SortableQuestionItem = React.memo(SortableQuestion, (prevProps, nextProps) => {
    return (
        prevProps.question.id === nextProps.question.id &&
        prevProps.question.content === nextProps.question.content &&
        prevProps.question.type === nextProps.question.type &&
        prevProps.question.points === nextProps.question.points &&
        prevProps.isCollapsed === nextProps.isCollapsed &&
        prevProps.index === nextProps.index &&
        JSON.stringify(prevProps.question.choices) === JSON.stringify(nextProps.question.choices) &&
        JSON.stringify(prevProps.errors) === JSON.stringify(nextProps.errors)
    );
});
