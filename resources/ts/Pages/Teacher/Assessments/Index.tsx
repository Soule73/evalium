import React, { useMemo } from 'react';
import { router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { Button, Section } from '@/Components';
import { type Assessment, type PageProps } from '@/types';
import { route } from 'ziggy-js';
import { hasPermission } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { AssessmentList } from '@/Components/shared/lists';

interface Props extends PageProps {
  assessments: PaginationType<Assessment>;
}

const AssessmentIndex: React.FC<Props> = ({ assessments }) => {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();
  const { auth } = usePage<PageProps>().props;
  const canCreateAssessments = hasPermission(auth.permissions, 'create assessments');

  const translations = useMemo(() => ({
    pageTitle: t('assessment_pages.page_titles.index'),
    title: t('assessment_pages.index.title'),
    subtitle: t('assessment_pages.index.subtitle'),
    newAssessment: t('assessment_pages.index.new_assessment'),
  }), [t]);

  return (
    <AuthenticatedLayout
      title={translations.pageTitle}
      breadcrumb={breadcrumbs.teacherAssessments()}
    >
      <Section
        title={translations.title}
        subtitle={translations.subtitle}
        actions={canCreateAssessments && (
          <Button
            size='sm'
            variant='outline'
            color='secondary'
            onClick={() => router.visit(route('teacher.assessments.create'))}
          >
            {translations.newAssessment}
          </Button>
        )}
      >
        <AssessmentList data={assessments} />
      </Section>
    </AuthenticatedLayout>
  );
};

export default AssessmentIndex;
