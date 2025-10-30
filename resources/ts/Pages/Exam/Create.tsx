import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components';
import Section from '@/Components/Section';
import QuestionsManager from '@/Components/exam/QuestionsManager';
import ExamGeneralConfig from '@/Components/ExamGeneralConfig';
import { useCreateExam } from '@/hooks';

const TeacherExamCreate: React.FC = () => {
    const {
        data,
        errors,
        processing,
        questions,
        handleQuestionsChange,
        handleFieldChange,
        handleSubmit
    } = useCreateExam();

    return (
        <AuthenticatedLayout title="Créer un examen">
            <form onSubmit={handleSubmit} noValidate className="space-y-6">
                <Section
                    title="Créer un examen"
                    subtitle="Configurez les paramètres de votre examen et ajoutez vos questions."
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
                                disabled={questions.length === 0}
                            >
                                Créer l'examen
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
                    errors={errors}
                />
            </form>
        </AuthenticatedLayout>
    );
};

export default TeacherExamCreate;