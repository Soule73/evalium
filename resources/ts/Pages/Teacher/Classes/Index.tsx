import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { type ClassModel, type PageProps } from '@/types';
import { breadcrumbs, trans } from '@/utils';
import { Section } from '@/Components';
import { ClassList } from '@/Components/shared/lists';

interface Props extends PageProps {
  classes: PaginationType<ClassModel>;
}

export default function TeacherClassIndex({ classes }: Props) {
  return (
    <AuthenticatedLayout
      title={trans('teacher_class_pages.index.title')}
      breadcrumb={breadcrumbs.teacher.classes()}
    >
      <Section
        title={trans('teacher_class_pages.index.section_title')}
        subtitle={trans('teacher_class_pages.index.section_subtitle', { count: classes.total })}
      >
        <ClassList data={classes} variant="teacher" />
      </Section>
    </AuthenticatedLayout>
  );
}
