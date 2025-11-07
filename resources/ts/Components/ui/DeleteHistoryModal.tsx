import React, { useState } from 'react';
import { Button } from './Button';
import { DeletedQuestion, DeletedChoice } from '@/hooks/useDeleteHistory';
import {
    TrashIcon,
    ArrowUturnLeftIcon,
    ClockIcon,
    QuestionMarkCircleIcon,
    CheckCircleIcon
} from '@heroicons/react/24/outline';
import { formatDate, getQuestionTypeLabel } from '@/utils';
import { MarkdownRenderer } from '../forms';

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
                        Historique des suppressions
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
                            Vider l'historique
                        </Button>
                    )}
                </div>

                {!hasItems ? (
                    <div className="text-center py-8">
                        <ClockIcon className="w-12 h-12 text-gray-300 mx-auto mb-4" />
                        <p className="text-gray-500">Aucun élément supprimé</p>
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
                                    Questions ({deletedQuestions.length})
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setActiveTab('choices')}
                                    className={`py-2 px-1 border-b-2 font-medium text-sm ${activeTab === 'choices'
                                        ? 'border-blue-500 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                        }`}
                                >
                                    Choix ({deletedChoices.length})
                                </button>
                            </nav>
                        </div>

                        {/* Questions tab */}
                        {activeTab === 'questions' && (
                            <div className="space-y-3 max-h-96 overflow-y-auto">
                                {deletedQuestions.length === 0 ? (
                                    <p className="text-gray-500 text-center py-4">Aucune question supprimée</p>
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
                                                            {getQuestionTypeLabel(deletedQuestion.question.type)}
                                                        </span>
                                                        <span className="text-xs text-gray-500">
                                                            {deletedQuestion.question.points} point{deletedQuestion.question.points !== 1 ? 's' : ''}
                                                        </span>
                                                    </div>
                                                    <MarkdownRenderer>
                                                        {truncateText(deletedQuestion.question.content)}
                                                    </MarkdownRenderer>
                                                    <p className="text-xs text-gray-500">
                                                        Supprimé le {formatDate(deletedQuestion.deletedAt)}
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
                                                    Restaurer
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
                                    <p className="text-gray-500 text-center py-4">Aucun choix supprimé</p>
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
                                                            Choix {deletedChoice.choice.is_correct ? 'correct' : 'incorrect'}
                                                        </span>
                                                    </div>
                                                    <p className="text-sm text-gray-900 mb-2">
                                                        {truncateText(deletedChoice.choice.content)}
                                                    </p>
                                                    <p className="text-xs text-gray-500">
                                                        Supprimé le {formatDate(deletedChoice.deletedAt)}
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
                                                    Restaurer
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
                        Fermer
                    </Button>
                </div>
            </div>
        </div>
    );
};

export default DeleteHistoryModal;