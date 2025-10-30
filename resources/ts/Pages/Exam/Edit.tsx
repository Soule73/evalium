import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components';
import Section from '@/Components/Section';
import QuestionsManager from '@/Components/exam/QuestionsManager';
import ExamGeneralConfig from '@/Components/ExamGeneralConfig';
import { useEditExam, useDeleteHistory } from '@/hooks';
import { Exam } from '@/types';

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

    return (
        <AuthenticatedLayout title="Modifier l'examen">
            <form onSubmit={handleSubmit} noValidate className="space-y-6">
                <Section
                    title="Modifier l'examen"
                    subtitle={`Modifiez les paramètres de votre examen et ajustez vos questions. Total: ${totalPoints} point${totalPoints !== 1 ? 's' : ''}`}
                    actions={
                        <div className="flex items-center justify-end space-x-4">
                            <Button
                                type="button"
                                color="secondary"
                                variant="outline"
                                size="sm"
                                onClick={() => window.history.back()}
                            >
                                Annuler
                            </Button>
                            <Button
                                type="submit"
                                color="primary"
                                variant="solid"
                                size="sm"
                                loading={processing}
                            >
                                Modifier l'examen
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