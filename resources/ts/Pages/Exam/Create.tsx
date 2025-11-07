import React from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Button, ExamGeneralConfig, QuestionsManager, Section } from '@/Components';
import { useCreateExam } from '@/hooks';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';

const ExamCreate: React.FC = () => {
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
        <AuthenticatedLayout
            title={trans('exam_pages.create.title')}
            breadcrumb={breadcrumbs.examCreate()}
        >
            <form onSubmit={handleSubmit} noValidate className="space-y-6">
                <Section
                    title={trans('exam_pages.create.title')}
                    subtitle={trans('exam_pages.create.subtitle')}
                    actions={
                        <div className="flex items-center justify-end space-x-4">
                            <Button
                                type="button"
                                color="secondary"
                                variant="outline"
                                size="sm"
                                onClick={() => window.history.back()}
                            >
                                {trans('exam_pages.create.cancel')}
                            </Button>
                            <Button
                                type="submit"
                                color="primary"
                                variant="solid"
                                size="sm"
                                loading={processing}
                                disabled={questions.length === 0}
                            >
                                {trans('exam_pages.create.submit')}
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

export default ExamCreate;