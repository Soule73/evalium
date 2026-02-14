import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Assessment, type PageProps, type PaginationType } from '@/types';
import { breadcrumbs, trans } from '@/utils';
import { Section } from '@/Components';
import { AssessmentList } from '@/Components/shared/lists';

interface FilterOption {
  id: number;
  name: string;
}

interface Props extends PageProps {
  assessments: PaginationType<Assessment>;
  filters: {
    search?: string;
    class_id?: string;
    subject_id?: string;
    teacher_id?: string;
    type?: string;
    delivery_mode?: string;
  };
  classes: FilterOption[];
  subjects: FilterOption[];
  teachers: FilterOption[];
}

export default function AdminAssessmentsIndex({
  assessments,
}: Props) {
  return (
    <AuthenticatedLayout
      title={trans('admin_pages.assessments.title')}
      breadcrumb={breadcrumbs.admin.assessments()}
    >
      <div className="space-y-6">
        <Section
          title={trans('admin_pages.assessments.title')}
          subtitle={trans('admin_pages.assessments.subtitle')}
        >
          <AssessmentList
            data={assessments}
            variant="admin"
            onView={(item) => {
              const assessment = item as Assessment;
              router.visit(route('admin.assessments.show', assessment.id));
            }}
          />
        </Section>
      </div>
    </AuthenticatedLayout>
  );
}
