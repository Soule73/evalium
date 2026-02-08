import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Subject, ClassModel, ClassSubject, Assessment, PageProps } from '@/types';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import { breadcrumbs, trans, formatDate } from '@/utils';
import { Button, Section, Badge, DataTable } from '@/Components';
import { route } from 'ziggy-js';
import { BookOpenIcon, AcademicCapIcon, DocumentTextIcon } from '@heroicons/react/24/outline';

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

  const handleViewAssessment = (assessment: Assessment) => {
    router.visit(route('teacher.assessments.show', assessment.id));
  };

  const handleCreateAssessment = (classSubjectId: number) => {
    router.visit(route('teacher.assessments.create', { class_subject_id: classSubjectId }));
  };

  const assessmentsTableConfig: DataTableConfig<Assessment> = {
    columns: [
      {
        key: 'title',
        label: trans('teacher_subject_pages.show.assessment_title'),
        render: (assessment) => (
          <div>
            <div className="font-medium text-gray-900">{assessment.title}</div>
            <div className="text-sm text-gray-500">
              {assessment.class_subject?.class?.name}
            </div>
          </div>
        ),
      },
      {
        key: 'type',
        label: trans('teacher_subject_pages.show.type'),
        render: (assessment) => {
          const typeColors: Record<string, 'info' | 'success' | 'warning'> = {
            devoir: 'info',
            examen: 'warning',
            tp: 'success',
            controle: 'warning',
            projet: 'info',
          };
          return (
            <Badge
              label={trans(`teacher_subject_pages.show.type_${assessment.type}`)}
              type={typeColors[assessment.type] || 'gray'}
              size="sm"
            />
          );
        },
      },
      {
        key: 'scheduled_at',
        label: trans('teacher_subject_pages.show.date'),
        render: (assessment) => (
          <span className="text-sm text-gray-600">
            {formatDate(assessment.scheduled_at, 'short')}
          </span>
        ),
      },
      {
        key: 'status',
        label: trans('teacher_subject_pages.show.status'),
        render: (assessment) => (
          <Badge
            label={assessment.is_published
              ? trans('teacher_subject_pages.show.published')
              : trans('teacher_subject_pages.show.draft')}
            type={assessment.is_published ? 'success' : 'gray'}
            size="sm"
          />
        ),
      },
      {
        key: 'actions',
        label: trans('teacher_subject_pages.show.actions'),
        className: 'text-right',
        render: (assessment) => (
          <Button
            size="sm"
            variant="outline"
            color="secondary"
            onClick={() => handleViewAssessment(assessment)}
          >
            {trans('teacher_subject_pages.show.view')}
          </Button>
        ),
      },
    ],
    searchPlaceholder: trans('teacher_subject_pages.show.search_assessments'),
    perPageOptions: [10, 25, 50],
    emptyState: {
      title: trans('teacher_subject_pages.show.no_assessments'),
      subtitle: trans('teacher_subject_pages.show.no_assessments_description'),
    },
    emptySearchState: {
      title: trans('teacher_subject_pages.show.no_assessments_found'),
      subtitle: trans('teacher_subject_pages.show.no_assessments_found_description'),
      resetLabel: trans('teacher_subject_pages.show.reset_search'),
    },
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
          title={trans('teacher_subject_pages.show.classes_section_title')}
          subtitle={trans('teacher_subject_pages.show.classes_section_subtitle')}
        >
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {(subject.class_subjects || []).map((cs) => (
              <div
                key={cs.id}
                className="p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition-colors"
              >
                <div className="flex justify-between items-start">
                  <div>
                    <h4 className="font-medium text-gray-900">{cs.class?.name}</h4>
                    <p className="text-sm text-gray-500">{cs.class?.level?.name}</p>
                    <p className="text-xs text-gray-400 mt-1">
                      {trans('teacher_subject_pages.show.coefficient')}: {cs.coefficient}
                    </p>
                  </div>
                  <Button
                    size="sm"
                    variant="solid"
                    color="primary"
                    onClick={() => handleCreateAssessment(cs.id)}
                  >
                    {trans('teacher_subject_pages.show.create_assessment')}
                  </Button>
                </div>
              </div>
            ))}
          </div>
        </Section>

        <Section
          title={trans('teacher_subject_pages.show.assessments_section_title')}
          subtitle={trans('teacher_subject_pages.show.assessments_section_subtitle', { count: assessments.total })}
        >
          <DataTable data={assessments} config={assessmentsTableConfig} />
        </Section>
      </div>
    </AuthenticatedLayout>
  );
}
