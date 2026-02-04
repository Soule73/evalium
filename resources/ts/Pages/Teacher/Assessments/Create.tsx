import React from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Button, QuestionsManager, Section } from '@/Components';
import AssessmentGeneralConfig from '@/Components/shared/AssessmentGeneralConfig';
import { useCreateAssessment } from '@/hooks/features/assessment';
import { useAssessmentFormStore } from '@/stores';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';
import { ClassSubject } from '@/types';

interface Props {
  classSubjects: ClassSubject[];
}

const AssessmentCreate: React.FC<Props> = ({ classSubjects }) => {
  const hasQuestions = useAssessmentFormStore((state) => state.questions.length > 0);

  const {
    data,
    errors,
    processing,
    handleFieldChange,
    handleSubmit
  } = useCreateAssessment();

  return (
    <AuthenticatedLayout
      title={trans('assessment_pages.create.title')}
      breadcrumb={breadcrumbs.createTeacherAssessment()}
    >
      <form onSubmit={handleSubmit} noValidate className="space-y-6">
        <Section
          title={trans('assessment_pages.create.title')}
          subtitle={trans('assessment_pages.create.subtitle')}
          actions={
            <div className="flex items-center justify-end space-x-4">
              <Button
                type="button"
                color="secondary"
                variant="outline"
                size="sm"
                onClick={() => window.history.back()}
              >
                {trans('assessment_pages.create.cancel')}
              </Button>
              <Button
                type="submit"
                color="primary"
                variant="solid"
                size="sm"
                loading={processing}
                disabled={!hasQuestions}
              >
                {trans('assessment_pages.create.submit')}
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
