import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { route } from 'ziggy-js';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Section } from '@/Components';
import { ClassSubjectList } from '@/Components/shared/lists';
import { type ClassSubject, type PageProps } from '@/types';
import { type PaginationType } from '@/types/datatable';

interface Props extends PageProps {
  classSubjects: PaginationType<ClassSubject>;
}

/**
 * Page listing all active class-subject assignments for the authenticated teacher.
 */
export default function TeacherClassSubjectsIndex({ classSubjects }: Props) {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();

  return (
    <AuthenticatedLayout
      title={t('teacher_class_pages.class_subjects.title')}
      breadcrumb={breadcrumbs.teacher.classSubjects()}
    >
      <Section
        title={t('teacher_class_pages.class_subjects.title')}
        subtitle={t('teacher_class_pages.class_subjects.subtitle')}
      >
        <ClassSubjectList
          data={classSubjects}
          variant="teacher"
          showTeacherColumn={false}
          onView={(cs) =>
            router.visit(route('teacher.classes.show', { id: cs.class_id }))
          }
        />
      </Section>
    </AuthenticatedLayout>
  );
}
