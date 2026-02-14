import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Subject, ClassModel, ClassSubject, Assessment, PageProps } from '@/types';
import { PaginationType } from '@/types/datatable';
import { breadcrumbs, trans } from '@/utils';
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
              {trans('teacher_subject_pages.show.back')}
            </Button>
          }
        >
          <Stat.Group columns={3}>
            <Stat.Item
              icon={BookOpenIcon}
              title={trans('teacher_subject_pages.show.code')}
              value={<span className="text-sm text-gray-900">{subject.code || '-'}</span>}
            />
            <Stat.Item
              icon={AcademicCapIcon}
              title={trans('teacher_subject_pages.show.classes_count')}
              value={<span className="text-sm font-semibold text-gray-900">{subject.classes?.length || 0}</span>}
            />
            <Stat.Item
              icon={DocumentTextIcon}
              title={trans('teacher_subject_pages.show.total_assessments')}
              value={<span className="text-sm font-semibold text-gray-900">{subject.total_assessments || 0}</span>}
            />
          </Stat.Group>
        </Section>

        <Section
          title={trans('teacher_subject_pages.show.assessments_section_title')}
          subtitle={trans('teacher_subject_pages.show.assessments_section_subtitle', { count: assessments.total })}
        >
          <AssessmentList data={assessments} variant="teacher" />
        </Section>
      </div>
    </AuthenticatedLayout>
  );
}
