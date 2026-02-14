import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Button, QuestionsManager, Section } from '@/Components';
import { AssessmentGeneralConfig } from '@/Components/shared/AssessmentGeneralConfig';
import { useEditAssessment } from '@/hooks/features/assessment';
// import { useAssessmentFormStore } from '@/stores';
import { type Assessment, type ClassSubject } from '@/types';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';

interface Props {
  assessment: Assessment;
  classSubjects: ClassSubject[];
}

export default function AssessmentEdit({ assessment, classSubjects }: Props) {
  // const totalPoints = useAssessmentFormStore((state) =>
  //   state.questions.reduce((sum, q) => sum + q.points, 0)
  // );

  const {
    data,
    errors,
    processing,
    handleFieldChange,
    handleSubmit
  } = useEditAssessment(assessment);

  // const pointsLabel = totalPoints !== 1 ? trans('assessment_pages.common.s') : '';

  return (
    <AuthenticatedLayout
      title={trans('assessment_pages.edit.title')}
      breadcrumb={breadcrumbs.editTeacherAssessment(assessment)}
    >
      <form onSubmit={handleSubmit} noValidate className="space-y-6">
        <Section
          title={trans('assessment_pages.edit.title')}
          subtitle={trans('assessment_pages.edit.subtitle')}
          actions={
            <div className="flex items-center justify-end space-x-4">
              <Button
                type="button"
                color="secondary"
                variant="outline"
                size="sm"
                onClick={() => window.history.back()}
              >
                {trans('assessment_pages.edit.cancel')}
              </Button>
              <Button
                type="submit"
                color="primary"
                variant="solid"
                size="sm"
                loading={processing}
              >
                {trans('assessment_pages.edit.submit')}
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
