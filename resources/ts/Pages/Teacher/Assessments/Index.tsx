import React from 'react';
import { router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { PaginationType } from '@/types/datatable';
import { Button, Section } from '@/Components';
import { Assessment, PageProps } from '@/types';
import { route } from 'ziggy-js';
import { hasPermission } from '@/utils';
import { trans } from '@/utils';
import { breadcrumbs } from '@/utils';
import { AssessmentList } from '@/Components/features/assessment/AssessmentList';

interface Props extends PageProps {
  assessments: PaginationType<Assessment>;
}

const AssessmentIndex: React.FC<Props> = ({ assessments }) => {
  const { auth } = usePage<PageProps>().props;
  const canCreateAssessments = hasPermission(auth.permissions, 'create assessments');

  return (
    <AuthenticatedLayout
      title={trans('assessment_pages.page_titles.index')}
      breadcrumb={breadcrumbs.teacherAssessments()}
    >
      <Section
        title={trans('assessment_pages.index.title')}
        subtitle={trans('assessment_pages.index.subtitle')}
        actions={canCreateAssessments && (
          <Button
            size='sm'
            variant='outline'
            color='secondary'
            onClick={() => router.visit(route('teacher.assessments.create'))}
          >
            {trans('assessment_pages.index.new_assessment')}
          </Button>
        )}
      >
        <AssessmentList data={assessments} />
      </Section>
    </AuthenticatedLayout>
  );
};

export default AssessmentIndex;
