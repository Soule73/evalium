import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { type Subject, type Level, type ClassModel, type PageProps } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section } from '@/Components';
import { SubjectList } from '@/Components/shared/lists';
import { type SubjectRouteContext } from '@/types/route-context';
import { route } from 'ziggy-js';

interface SubjectWithClasses extends Subject {
  classes?: ClassModel[];
  classes_count?: number;
  assessments_count?: number;
}

interface Props extends PageProps {
  subjects: PaginationType<Subject | SubjectWithClasses>;
  levels?: Level[];
  classes?: ClassModel[];
  filters?: Record<string, string>;
  routeContext: SubjectRouteContext;
}

export default function SubjectsIndex({ subjects, levels, classes, routeContext }: Props) {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();
  const isAdmin = routeContext.role === 'admin';

  const translations = useMemo(
    () => ({
      title: isAdmin
        ? t('admin_pages.subjects.title')
        : t('teacher_subject_pages.index.title'),
      subtitle: isAdmin
        ? t('admin_pages.subjects.subtitle')
        : t('teacher_subject_pages.index.section_subtitle', { count: subjects.total }),
      create: t('admin_pages.subjects.create'),
    }),
    [t, isAdmin, subjects.total],
  );

  const handleCreate = () => {
    router.visit(route('admin.subjects.create'));
  };

  const breadcrumb = isAdmin
    ? breadcrumbs.admin.subjects()
    : breadcrumbs.teacher.subjects();

  return (
    <AuthenticatedLayout title={translations.title} breadcrumb={breadcrumb}>
      <Section
        title={translations.title}
        subtitle={translations.subtitle}
        actions={
          isAdmin && routeContext.editRoute !== null ? (
            <Button size="sm" variant="solid" color="primary" onClick={handleCreate}>
              {translations.create}
            </Button>
          ) : undefined
        }
      >
        {isAdmin ? (
          <SubjectList data={subjects} variant="admin" levels={levels ?? []} />
        ) : (
          <SubjectList data={subjects} variant="teacher" classes={classes ?? []} />
        )}
      </Section>
    </AuthenticatedLayout>
  );
}
