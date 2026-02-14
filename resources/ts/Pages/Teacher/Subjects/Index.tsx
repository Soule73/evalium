import { useMemo } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { type Subject, type ClassModel, type PageProps } from '@/types';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { useTranslations } from '@/hooks/shared/useTranslations';
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
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();

  const translations = useMemo(() => ({
    title: t('teacher_subject_pages.index.title'),
    sectionTitle: t('teacher_subject_pages.index.section_title')
  }), [t]);

  const sectionSubtitleTranslation = useMemo(() => t('teacher_subject_pages.index.section_subtitle', { count: subjects.total }), [t, subjects.total]);

  return (
    <AuthenticatedLayout
      title={translations.title}
      breadcrumb={breadcrumbs.teacher.subjects()}
    >
      <Section
        title={translations.sectionTitle}
        subtitle={sectionSubtitleTranslation}
      >
        <SubjectList data={subjects} variant="teacher" classes={classes} />
      </Section>
    </AuthenticatedLayout>
  );
}
