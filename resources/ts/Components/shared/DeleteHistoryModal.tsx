import React, { useState } from 'react';
import {
    TrashIcon,
    ArrowUturnLeftIcon,
    ClockIcon,
    QuestionMarkCircleIcon,
    CheckCircleIcon
} from '@heroicons/react/24/outline';
import { formatDate } from '@/utils';
import { MarkdownRenderer } from '@examena/ui';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button } from '../ui';
import { type DeletedChoice, type DeletedQuestion } from '@/stores/useAssessmentFormStore';

interface DeleteHistoryModalProps {
    isOpen: boolean;
    onClose: () => void;
    deletedQuestions: DeletedQuestion[];
    deletedChoices: DeletedChoice[];
    onRestoreQuestion: (deletedQuestion: DeletedQuestion) => void;
    onRestoreChoice: (deletedChoice: DeletedChoice) => void;
    onClearHistory: () => void;
}

const DeleteHistoryModal: React.FC<DeleteHistoryModalProps> = ({
    isOpen,
    onClose,
    deletedQuestions,
    deletedChoices,
    onRestoreQuestion,
    onRestoreChoice,
    onClearHistory
}) => {
    const [activeTab, setActiveTab] = useState<'questions' | 'choices'>('questions');
    const { t } = useTranslations();

    const translations = {
        historyTitle: t('components.delete_history_modal.title'),
        clearHistory: t('components.delete_history_modal.clear_history'),
        noItems: t('components.delete_history_modal.no_items'),
        questionsTab: t('components.delete_history_modal.questions_tab'),
        choicesTab: t('components.delete_history_modal.choices_tab'),
        noQuestions: t('components.delete_history_modal.no_questions'),
        noChoices: t('components.delete_history_modal.no_choices'),
        restore: t('components.delete_history_modal.restore'),
        close: t('components.delete_history_modal.close'),
        deletedOn: t('components.delete_history_modal.deleted_on'),
        point: t('components.delete_history_modal.point'),
        points: t('components.delete_history_modal.points'),
        correctChoice: t('components.delete_history_modal.correct_choice'),
        incorrectChoice: t('components.delete_history_modal.incorrect_choice'),
        questionTypes: {
            multiple: t('formatters.question_type_multiple'),
            one_choice: t('formatters.question_type_one_choice'),
            boolean: t('formatters.question_type_boolean'),
            text: t('formatters.question_type_text'),
        }
    };

    const getQuestionTypeLabelFromCache = (type: string) => {
        return translations.questionTypes[type as keyof typeof translations.questionTypes] || type;
    };


    const truncateText = (text: string, maxLength: number = 60) => {
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    };

    const hasItems = deletedQuestions.length > 0 || deletedChoices.length > 0;

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50  "
        >
            <div className="absolute inset-0 bg-black opacity-50" onClick={onClose} />
            <div className="space-y-4 absolute right-0 top-0 h-full w-full max-w-2xl bg-white shadow-lg p-6 flex flex-col">
                <div className="flex items-center justify-between">
                    <h3 className="text-lg font-medium text-gray-900 flex items-center">
                        <ClockIcon className="w-5 h-5 mr-2 text-gray-500" />
                        {translations.historyTitle}
                    </h3>
                    {hasItems && (
                        <Button
                            type="button"
                            variant="outline"
                            color="danger"
                            size="sm"
                            onClick={onClearHistory}
                        >
                            <TrashIcon className="w-4 h-4 mr-1" />
                            {translations.clearHistory}
                        </Button>
                    )}
                </div>

                {!hasItems ? (
                    <div className="text-center py-8">
                        <ClockIcon className="w-12 h-12 text-gray-300 mx-auto mb-4" />
                        <p className="text-gray-500">{translations.noItems}</p>
                    </div>
                ) : (
                    <>
                        {/* Tabs */}
                        <div className="border-b border-gray-200">
                            <nav className="-mb-px flex space-x-8">
                                <button
                                    type="button"
                                    onClick={() => setActiveTab('questions')}
                                    className={`py-2 px-1 border-b-2 font-medium text-sm ${activeTab === 'questions'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        }`}
                                >
                                    {translations.questionsTab} ({deletedQuestions.length})
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setActiveTab('choices')}
                                    className={`py-2 px-1 border-b-2 font-medium text-sm ${activeTab === 'choices'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        }`}
                                >
                                    {translations.choicesTab} ({deletedChoices.length})
                                </button>
                            </nav>
                        </div>

                        {/* Questions tab */}
                        {activeTab === 'questions' && (
                            <div className="space-y-3 max-h-96 overflow-y-auto">
                                {deletedQuestions.length === 0 ? (
                                    <p className="text-gray-500 text-center py-4">{translations.noQuestions}</p>
                                ) : (
                                    deletedQuestions.map((deletedQuestion) => (
                                        <div
                                            key={`deleted-question-${deletedQuestion.id}`}
                                            className="border border-gray-200 rounded-lg p-4 bg-gray-50"
                                        >
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1">
                                                    <div className="flex items-center space-x-2 mb-2">
                                                        <QuestionMarkCircleIcon className="w-4 h-4 text-blue-500" />
                                                        <span className="text-sm font-medium text-blue-600">
                                                            {getQuestionTypeLabelFromCache(deletedQuestion.question.type)}
                                                        </span>
                                                        <span className="text-xs text-gray-500">
                                                            {deletedQuestion.question.points} {deletedQuestion.question.points !== 1 ? translations.points : translations.point}
                                                        </span>
                                                    </div>
                                                    <MarkdownRenderer>
                                                        {truncateText(deletedQuestion.question.content)}
                                                    </MarkdownRenderer>
                                                    <p className="text-xs text-gray-500">
                                                        {translations.deletedOn} {formatDate(deletedQuestion.deletedAt)}
                                                    </p>
                                                </div>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    color="primary"
                                                    size="sm"
                                                    onClick={() => onRestoreQuestion(deletedQuestion)}
                                                >
                                                    <ArrowUturnLeftIcon className="w-4 h-4 mr-1" />
                                                    {translations.restore}
                                                </Button>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        )}

                        {activeTab === 'choices' && (
                            <div className="space-y-3 max-h-96 overflow-y-auto">
                                {deletedChoices.length === 0 ? (
                                    <p className="text-gray-500 text-center py-4">{translations.noChoices}</p>
                                ) : (
                                    deletedChoices.map((deletedChoice) => (
                                        <div
                                            key={`deleted-choice-${deletedChoice.id}`}
                                            className="border border-gray-200 rounded-lg p-4 bg-gray-50"
                                        >
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1">
                                                    <div className="flex items-center space-x-2 mb-2">
                                                        <CheckCircleIcon
                                                            className={`w-4 h-4 ${deletedChoice.choice.is_correct ? 'text-green-500' : 'text-gray-400'}`}
                                                        />
                                                        <span className="text-sm font-medium">
                                                            {deletedChoice.choice.is_correct ? translations.correctChoice : translations.incorrectChoice}
                                                        </span>
                                                    </div>
                                                    <p className="text-sm text-gray-900 mb-2">
                                                        {truncateText(deletedChoice.choice.content)}
                                                    </p>
                                                    <p className="text-xs text-gray-500">
                                                        {translations.deletedOn} {formatDate(deletedChoice.deletedAt)}
                                                    </p>
                                                </div>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    color="primary"
                                                    size="sm"
                                                    onClick={() => onRestoreChoice(deletedChoice)}
                                                >
                                                    <ArrowUturnLeftIcon className="w-4 h-4 mr-1" />
                                                    {translations.restore}
                                                </Button>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        )}
                    </>
                )}

                <div className="flex justify-end pt-4 border-t border-gray-200">
                    <Button
                        type="button"
                        variant="outline"
                        color="secondary"
                        onClick={onClose}
                    >
                        {translations.close}
                    </Button>
                </div>
            </div>
        </div>
    );
};

export { DeleteHistoryModal };