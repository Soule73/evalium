import React from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Button, ExamGeneralConfig, QuestionsManager, Section } from '@/Components';
import { useEditExam, useDeleteHistory } from '@/hooks';
import { Exam } from '@/types';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';

interface Props {
    exam: Exam;
}

export default function ExamEdit({ exam }: Props) {
    const clearHistoryRef = React.useRef<() => void>(() => { });

    const {
        data,
        errors,
        processing,
        questions,
        handleQuestionsChange,
        handleQuestionDelete,
        handleChoiceDelete,
        handleFieldChange,
        handleSubmit
    } = useEditExam(exam, () => {
        if (clearHistoryRef.current) {
            clearHistoryRef.current();
        }
    });

    const { clearHistory } = useDeleteHistory({
        questions,
        onQuestionsChange: handleQuestionsChange
    });

    React.useEffect(() => {
        clearHistoryRef.current = clearHistory;
    }, [clearHistory]);

    const totalPoints = questions.reduce((sum, question) => sum + question.points, 0);
    const pointsLabel = totalPoints !== 1 ? trans('exam_pages.common.s') : '';

    return (
        <AuthenticatedLayout
            title={trans('exam_pages.edit.title')}
            breadcrumb={breadcrumbs.examEdit(exam.title, exam.id)}
        >
            <form onSubmit={handleSubmit} noValidate className="space-y-6">
                <Section
                    title={trans('exam_pages.edit.title')}
                    subtitle={trans('exam_pages.edit.subtitle', { points: totalPoints, plural: pointsLabel })}
                    actions={
                        <div className="flex items-center justify-end space-x-4">
                            <Button
                                type="button"
                                color="secondary"
                                variant="outline"
                                size="sm"
                                onClick={() => window.history.back()}
                            >
                                {trans('exam_pages.edit.cancel')}
                            </Button>
                            <Button
                                type="submit"
                                color="primary"
                                variant="solid"
                                size="sm"
                                loading={processing}
                            >
                                {trans('exam_pages.edit.submit')}
                            </Button>
                        </div>
                    }
                >
                    <ExamGeneralConfig
                        data={data}
                        errors={errors}
                        onFieldChange={handleFieldChange}
                    />
                </Section>

                <QuestionsManager
                    questions={questions}
                    onQuestionsChange={handleQuestionsChange}
                    onQuestionDelete={handleQuestionDelete}
                    onChoiceDelete={handleChoiceDelete}
                    errors={errors}
                />
            </form>
        </AuthenticatedLayout>
    );
}