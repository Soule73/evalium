import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Assessment, AssessmentAssignment, PageProps } from '@/types';
import type { PaginationType } from '@/types/datatable';
import { Section } from '@/Components';
import { AssessmentList } from '@/Components/shared/lists';
import { trans } from '@/utils';

interface StudentAssessmentsIndexProps extends PageProps {
  assignments: PaginationType<AssessmentAssignment & { assessment: Assessment }>;
  filters: {
    status?: string;
    search?: string;
  };
}

export default function Index({ assignments }: StudentAssessmentsIndexProps) {
  const translations = {
    title: trans('student_assessment_pages.index.title'),
    subtitle: trans('student_assessment_pages.index.subtitle'),
  };

  return (
    <AuthenticatedLayout title={translations.title}>
      <Section title={translations.title} subtitle={translations.subtitle}>
        <AssessmentList data={assignments} variant="student" />
      </Section>
    </AuthenticatedLayout>
  );
}
