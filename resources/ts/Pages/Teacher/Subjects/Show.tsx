import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Subject, type ClassModel, type ClassSubject, type Assessment, type PageProps } from '@/types';
import { type PaginationType } from '@/types/datatable';
import { breadcrumbs } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button, Section, Stat } from '@/Components';
import { route } from 'ziggy-js';
import { BookOpenIcon, AcademicCapIcon, DocumentTextIcon } from '@heroicons/react/24/outline';
import { AssessmentList } from '@/Components/shared/lists';

interface SubjectWithDetails extends Subject {
  classes?: ClassModel[];
  class_subjects?: ClassSubject[];
  total_assessments?: number;
}

interface Props extends PageProps {
  subject: SubjectWithDetails;
  assessments: PaginationType<Assessment>;
  filters: {
    search?: string;
  };
}

export default function TeacherSubjectShow({ subject, assessments }: Props) {
  const { t } = useTranslations();

  const translations = useMemo(() => ({
    back: t('teacher_subject_pages.show.back'),
    code: t('teacher_subject_pages.show.code'),
    classesCount: t('teacher_subject_pages.show.classes_count'),
    totalAssessments: t('teacher_subject_pages.show.total_assessments'),
    assessmentsSectionTitle: t('teacher_subject_pages.show.assessments_section_title')
  }), [t]);

  const assessmentsSectionSubtitleTranslation = useMemo(() => t('teacher_subject_pages.show.assessments_section_subtitle', { count: assessments.total }), [t, assessments.total]);

  const handleBack = () => {
    router.visit(route('teacher.subjects.index'));
  };

  return (
    <AuthenticatedLayout
      title={subject.name}
      breadcrumb={breadcrumbs.teacher.showSubject(subject)}
    >
      <div className="space-y-6">
        <Section
          title={subject.name}
          subtitle={subject.code}
          actions={
            <Button size="sm" variant="outline" color="secondary" onClick={handleBack}>
              {translations.back}
            </Button>
          }
        >
          <Stat.Group columns={3}>
            <Stat.Item
              icon={BookOpenIcon}
              title={translations.code}
              value={<span className="text-sm text-gray-900">{subject.code || '-'}</span>}
            />
            <Stat.Item
              icon={AcademicCapIcon}
              title={translations.classesCount}
              value={<span className="text-sm font-semibold text-gray-900">{subject.classes?.length || 0}</span>}
            />
            <Stat.Item
              icon={DocumentTextIcon}
              title={translations.totalAssessments}
              value={<span className="text-sm font-semibold text-gray-900">{subject.total_assessments || 0}</span>}
            />
          </Stat.Group>
        </Section>

        <Section
          title={translations.assessmentsSectionTitle}
          subtitle={assessmentsSectionSubtitleTranslation}
        >
          <AssessmentList data={assessments} variant="teacher" />
        </Section>
      </div>
    </AuthenticatedLayout>
  );
}
