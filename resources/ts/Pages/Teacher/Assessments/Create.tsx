import React, { useMemo } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Button, QuestionsManager, Section } from '@/Components';
import { AssessmentGeneralConfig } from '@/Components/shared/AssessmentGeneralConfig';
import { useCreateAssessment } from '@/hooks/features/assessment';
import { useAssessmentFormStore } from '@/stores';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { type ClassSubject } from '@/types';

interface Props {
  classSubjects: ClassSubject[];
}

const AssessmentCreate: React.FC<Props> = ({ classSubjects }) => {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();
  const hasQuestions = useAssessmentFormStore((state) => state.questions.length > 0);

  const {
    data,
    errors,
    processing,
    handleFieldChange,
    handleSubmit
  } = useCreateAssessment();

  const translations = useMemo(() => ({
    title: t('assessment_pages.create.title'),
    subtitle: t('assessment_pages.create.subtitle'),
    cancel: t('assessment_pages.create.cancel'),
    submit: t('assessment_pages.create.submit'),
  }), [t]);

  return (
    <AuthenticatedLayout
      title={translations.title}
      breadcrumb={breadcrumbs.createTeacherAssessment()}
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
                disabled={!hasQuestions}
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
};

export default AssessmentCreate;
