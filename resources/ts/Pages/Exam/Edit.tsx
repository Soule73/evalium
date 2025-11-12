import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Button, ExamGeneralConfig, QuestionsManager, Section } from '@/Components';
import { useEditExam } from '@/hooks';
import { useExamFormStore } from '@/stores';
import { Exam } from '@/types';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';

interface Props {
    exam: Exam;
}

export default function ExamEdit({ exam }: Props) {
    const totalPoints = useExamFormStore((state) =>
        state.questions.reduce((sum, q) => sum + q.points, 0)
    );

    const {
        data,
        errors,
        processing,
        handleFieldChange,
        handleSubmit
    } = useEditExam(exam);

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

                <QuestionsManager errors={errors} />
            </form>
        </AuthenticatedLayout>
    );
}