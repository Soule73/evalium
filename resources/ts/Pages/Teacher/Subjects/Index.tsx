import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { PaginationType } from '@/types/datatable';
import { Subject, ClassModel, PageProps } from '@/types';
import { breadcrumbs, trans } from '@/utils';
import { Section } from '@/Components';
import { SubjectList } from '@/Components/shared/lists';

interface SubjectWithClasses extends Subject {
  classes?: ClassModel[];
  classes_count?: number;
  assessments_count?: number;
}

interface Props extends PageProps {
  subjects: PaginationType<SubjectWithClasses>;
  classes: ClassModel[];
}

export default function TeacherSubjectsIndex({ subjects, classes }: Props) {
  return (
    <AuthenticatedLayout
      title={trans('teacher_subject_pages.index.title')}
      breadcrumb={breadcrumbs.teacher.subjects()}
    >
      <Section
        title={trans('teacher_subject_pages.index.section_title')}
        subtitle={trans('teacher_subject_pages.index.section_subtitle', { count: subjects.total })}
      >
        <SubjectList data={subjects} variant="teacher" classes={classes} />
      </Section>
    </AuthenticatedLayout>
  );
}
