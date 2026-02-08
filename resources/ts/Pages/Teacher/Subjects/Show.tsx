import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Subject, ClassModel, ClassSubject, Assessment, PageProps } from '@/types';
import { PaginationType } from '@/types/datatable';
import { breadcrumbs, trans } from '@/utils';
import { Button, Section } from '@/Components';
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
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="flex items-start space-x-3">
              <BookOpenIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('teacher_subject_pages.show.code')}
                </div>
                <div className="mt-1 text-sm text-gray-900">
                  {subject.code || '-'}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <AcademicCapIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('teacher_subject_pages.show.classes_count')}
                </div>
                <div className="mt-1 text-sm font-semibold text-gray-900">
                  {subject.classes?.length || 0}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <DocumentTextIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('teacher_subject_pages.show.total_assessments')}
                </div>
                <div className="mt-1 text-sm font-semibold text-gray-900">
                  {subject.total_assessments || 0}
                </div>
              </div>
            </div>
          </div>
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
