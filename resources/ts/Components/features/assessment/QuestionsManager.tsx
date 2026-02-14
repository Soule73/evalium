import React from 'react';
import {
    PlusIcon,
    ChevronDownIcon,
    InformationCircleIcon,
    ClockIcon
} from '@heroicons/react/24/outline';
import { useTranslations } from '@/hooks/shared/useTranslations';
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    SortableContext,
    sortableKeyboardCoordinates,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { type QuestionType } from '@/types';
import { useAssessmentFormStore } from '@/stores';
import { getQuestionTypeLabel } from '@/utils';
import { useQuestionOptions } from './questionOptions';
import { SortableQuestionItem } from './SortableQuestionItem';
import { Section, Button, ConfirmationModal } from '@examena/ui';
import { DeleteHistoryModal } from '@/Components/shared/DeleteHistoryModal';
import { useQuestionsManager } from '@/hooks/features/assessment/useQuestionsManager';

interface QuestionsManagerProps {
    errors?: Record<string, string>;
}

interface Props {
    addQuestion: (kind: QuestionType) => void;
}

function QuestionMenu({ addQuestion }: Props) {
    const questionOptions = useQuestionOptions();

    return (
        <div className="absolute right-0 mt-2 w-64 rounded-xl shadow-lg bg-white ring-1 ring-gray-100 z-10">
            <div className="py-2">
                {questionOptions.map((opt) => (
                    <button
                        key={opt.key}
                        type="button"
                        onClick={() => addQuestion(opt.key)}
                        className="group flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 w-full text-left transition-colors"
                    >
                        <div className={`flex items-center justify-center w-8 h-8 rounded-lg mr-3 ${opt.bg} ${opt.text} ${opt.hoverBg} transition-colors`}>
                            {opt.svg}
                        </div>

                        <div>
                            <div className="font-medium">{opt.title}</div>
                            <div className="text-xs text-gray-500">{opt.subtitle}</div>
                        </div>
                    </button>
                ))}
            </div>
        </div>
    );
}

const QuestionsManager: React.FC<QuestionsManagerProps> = ({
    errors = {}
}) => {
    const questions = useAssessmentFormStore((state) => state.questions);

    const {
        collapsedQuestions,
        showAddDropdown,
        handleDragEnd,
        addQuestion,
        removeQuestion,
        updateQuestion,
        addChoice,
        removeChoice,
        updateChoice,
        toggleCollapse,
        toggleAddDropdown,
        getQuestionTypeIcon,
        confirmationModal,
        historyModalOpen,
        setHistoryModalOpen,
        deleteHistory,
        setConfirmationModal
    } = useQuestionsManager();

    const { t } = useTranslations();

    const translations = {
        title: t('components.questions_manager.title'),
        subtitle: t('components.questions_manager.subtitle'),
        historyButton: t('components.questions_manager.history_button', { count: deleteHistory.getDeletedQuestionsCount() + deleteHistory.getDeletedChoicesCount() }),
        addQuestion: t('components.questions_manager.add_question'),
        noQuestionsTitle: t('components.questions_manager.no_questions_title'),
        noQuestionsSubtitle: t('components.questions_manager.no_questions_subtitle'),
        deleteConfirm: t('components.questions_manager.delete_confirm'),
        deleteCancel: t('components.questions_manager.delete_cancel'),
        deleteNotice: t('components.questions_manager.delete_notice'),
    };

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        })
    );

    return (
        <DndContext
            sensors={sensors}
            collisionDetection={closestCenter}
            onDragEnd={handleDragEnd}
        >
            <Section title={translations.title} subtitle={translations.subtitle}
                className=' relative'
                actions={
                    <div className="flex items-center space-x-2">
                        {deleteHistory.hasDeletedItems() && (
                            <Button
                                type="button"
                                onClick={() => setHistoryModalOpen(true)}
                                variant="outline"
                                size='sm'
                                color='secondary'
                            >
                                <ClockIcon className="-ml-1 mr-2 h-4 w-4" />
                                {translations.historyButton}
                            </Button>
                        )}
                        <Button
                            type="button"
                            onClick={toggleAddDropdown}
                            variant="outline"
                            size='sm'
                            color='secondary'
                        >
                            <PlusIcon className="-ml-1 mr-2 h-4 w-4" />
                            {translations.addQuestion}
                            <ChevronDownIcon className="-mr-1 ml-2 h-4 w-4" />
                        </Button>
                        <div className="flex justify-end">

                            <div className="relative">
                                {showAddDropdown && (
                                    <QuestionMenu addQuestion={addQuestion} />
                                )}
                            </div>
                        </div>
                    </div>
                }
            >


                {questions.length === 0 && (
                    <div className="text-center py-16 border-2 border-dashed border-gray-200 rounded-xl bg-gray-50">
                        <InformationCircleIcon className="mx-auto h-12 w-12 text-gray-400 mb-4" />
                        <h3 className="text-sm font-medium text-gray-900 mb-2">{translations.noQuestionsTitle}</h3>
                        <p className="text-sm text-gray-500">{translations.noQuestionsSubtitle}</p>
                    </div>
                )}

                <SortableContext items={questions.map((_, index) => index.toString())} strategy={verticalListSortingStrategy}>
                    <div className="space-y-4">
                        {questions.map((question, index) => (
                            <SortableQuestionItem
                                key={question.id ? question.id.toString() : `question-new-${index}`}
                                question={question}
                                index={index}
                                isCollapsed={collapsedQuestions.has(`question-${index}`)}
                                onToggleCollapse={toggleCollapse}
                                onRemoveQuestion={removeQuestion}
                                onUpdateQuestion={updateQuestion}
                                onAddChoice={addChoice}
                                onRemoveChoice={removeChoice}
                                onUpdateChoice={updateChoice}
                                getQuestionTypeLabel={getQuestionTypeLabel}
                                getQuestionTypeIcon={getQuestionTypeIcon}
                                errors={errors}
                            />
                        ))}
                    </div>
                </SortableContext>

                <ConfirmationModal
                    isOpen={confirmationModal.isOpen}
                    onClose={() => setConfirmationModal(prev => ({ ...prev, isOpen: false }))}
                    onConfirm={confirmationModal.onConfirm}
                    title={confirmationModal.title}
                    message={confirmationModal.message}
                    confirmText={translations.deleteConfirm}
                    cancelText={translations.deleteCancel}
                    type="warning"
                >

                    <p className="text-gray-600 text-sm mb-6 text-center ">
                        {translations.deleteNotice}
                    </p>
                </ConfirmationModal>

                <DeleteHistoryModal
                    isOpen={historyModalOpen}
                    onClose={() => setHistoryModalOpen(false)}
                    deletedQuestions={deleteHistory.deletedQuestions}
                    deletedChoices={deleteHistory.deletedChoices}
                    onRestoreQuestion={deleteHistory.restoreQuestion}
                    onRestoreChoice={deleteHistory.restoreChoice}
                    onClearHistory={() => {
                        deleteHistory.clearHistory();
                        setHistoryModalOpen(false);
                    }}
                />
            </Section>
        </DndContext>
    );
};

export { QuestionsManager };