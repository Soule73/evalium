import { useMemo } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Button, QuestionsManager, Section } from '@/Components';
import { AssessmentGeneralConfig } from '@/Components/shared/AssessmentGeneralConfig';
import { useEditAssessment } from '@/hooks/features/assessment';
// import { useAssessmentFormStore } from '@/stores';
import { type Assessment, type ClassSubject } from '@/types';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';

interface Props {
    assessment: Assessment;
    classSubjects: ClassSubject[];
}

export default function AssessmentEdit({ assessment, classSubjects }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const { data, errors, processing, handleFieldChange, handleSubmit } =
        useEditAssessment(assessment);

    const translations = useMemo(
        () => ({
            title: t('assessment_pages.edit.title'),
            subtitle: t('assessment_pages.edit.subtitle'),
            cancel: t('assessment_pages.edit.cancel'),
            submit: t('assessment_pages.edit.submit'),
        }),
        [t],
    );

    return (
        <AuthenticatedLayout
            title={translations.title}
            breadcrumb={breadcrumbs.editTeacherAssessment(assessment)}
        >
            <form onSubmit={handleSubmit} noValidate className="space-y-6">
                <Section
                    title={translations.title}
                    subtitle={translations.subtitle}
                    actions={
                        <div className="flex items-center justify-end space-x-4">
                            <Button
                                type="button"
                                color="secondary"
                                variant="outline"
                                size="sm"
                                onClick={() => window.history.back()}
                            >
                                {translations.cancel}
                            </Button>
                            <Button
                                type="submit"
                                color="primary"
                                variant="solid"
                                size="sm"
                                loading={processing}
                            >
                                {translations.submit}
                            </Button>
                        </div>
                    }
                >
                    <AssessmentGeneralConfig
                        data={data}
                        errors={errors}
                        onFieldChange={handleFieldChange}
                        classSubjects={classSubjects}
                    />
                </Section>

                <QuestionsManager errors={errors} />
            </form>
        </AuthenticatedLayout>
    );
}
