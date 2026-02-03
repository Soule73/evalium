import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { ClassModel, Enrollment, ClassSubject, Assessment, PageProps } from '@/types';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import { breadcrumbs, trans } from '@/utils';
import { Button, Section, Badge, DataTable } from '@/Components';
import { route } from 'ziggy-js';
import { AcademicCapIcon, UserGroupIcon, BookOpenIcon, CalendarIcon } from '@heroicons/react/24/outline';

interface Props extends PageProps {
  class: ClassModel & {
    enrollments?: Enrollment[];
    class_subjects?: ClassSubject[];
    assessments?: Assessment[];
  };
}

export default function TeacherClassShow({ class: classItem }: Props) {
  const handleBack = () => {
    router.visit(route('teacher.classes.index'));
  };

  const handleViewAssessments = () => {
    router.visit(route('teacher.assessments.index', { class_id: classItem.id }));
  };

  const handleCreateAssessment = (classSubjectId: number) => {
    router.visit(route('teacher.assessments.create', { class_subject_id: classSubjectId }));
  };

  const handleViewAssessment = (assessment: Assessment) => {
    router.visit(route('teacher.assessments.show', assessment.id));
  };

  const activeEnrollments = classItem.enrollments?.filter(e => e.status === 'active') || [];
  const mySubjects = classItem.class_subjects || [];
  const recentAssessments = classItem.assessments?.slice(0, 5) || [];

  const subjectsTableConfig: DataTableConfig<ClassSubject> = {
    columns: [
      {
        key: 'subject',
        label: trans('teacher_pages.classes.subject_name'),
        render: (cs) => (
          <div>
            <div className="font-medium text-gray-900">{cs.subject?.name}</div>
            <div className="text-sm text-gray-500">{cs.subject?.code}</div>
          </div>
        ),
      },
      {
        key: 'coefficient',
        label: trans('teacher_pages.classes.coefficient'),
        render: (cs) => (
          <span className="text-sm text-gray-900">{cs.coefficient}</span>
        ),
      },
      {
        key: 'assessments_count',
        label: trans('teacher_pages.classes.assessments_count'),
        render: (cs) => (
          <Badge label={String(cs.assessments_count || 0)} type="info" size="sm" />
        ),
      },
      {
        key: 'actions',
        label: trans('teacher_pages.classes.actions'),
        render: (cs) => (
          <Button
            size="sm"
            variant="solid"
            color="primary"
            onClick={() => handleCreateAssessment(cs.id)}
          >
            {trans('teacher_pages.classes.create_assessment')}
          </Button>
        ),
      },
    ],
    emptyState: {
      title: trans('teacher_pages.classes.no_subjects'),
      subtitle: trans('teacher_pages.classes.no_subjects_description'),
    },
  };

  const subjectsPagination: PaginationType<ClassSubject> = {
    data: mySubjects,
    current_page: 1,
    last_page: 1,
    per_page: mySubjects.length,
    total: mySubjects.length,
    from: 1,
    to: mySubjects.length,
    first_page_url: '',
    last_page_url: '',
    next_page_url: null,
    prev_page_url: null,
    path: '',
    links: [],
  };

  const assessmentsTableConfig: DataTableConfig<Assessment> = {
    columns: [
      {
        key: 'title',
        label: trans('teacher_pages.classes.assessment_title'),
        render: (assessment) => (
          <div>
            <div className="font-medium text-gray-900">{assessment.title}</div>
            <div className="text-sm text-gray-500">
              {assessment.class_subject?.subject?.name}
            </div>
          </div>
        ),
      },
      {
        key: 'type',
        label: trans('teacher_pages.classes.assessment_type'),
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
              label={trans(`teacher_pages.classes.type_${assessment.type}`)}
              type={typeColors[assessment.type] || 'gray'}
              size="sm"
            />
          );
        },
      },
      {
        key: 'date',
        label: trans('teacher_pages.classes.assessment_date'),
        render: (assessment) => (
          <span className="text-sm text-gray-600">
            {new Date(assessment.assessment_date).toLocaleDateString()}
          </span>
        ),
      },
      {
        key: 'status',
        label: trans('teacher_pages.classes.status'),
        render: (assessment) => (
          <Badge
            label={assessment.is_published
              ? trans('teacher_pages.classes.published')
              : trans('teacher_pages.classes.draft')}
            type={assessment.is_published ? 'success' : 'gray'}
            size="sm"
          />
        ),
      },
      {
        key: 'actions',
        label: trans('teacher_pages.classes.actions'),
        render: (assessment) => (
          <Button
            size="sm"
            variant="outline"
            color="secondary"
            onClick={() => handleViewAssessment(assessment)}
          >
            {trans('teacher_pages.classes.view_details')}
          </Button>
        ),
      },
    ],
    emptyState: {
      title: trans('teacher_pages.classes.no_assessments'),
      subtitle: trans('teacher_pages.classes.no_assessments_description'),
    },
  };

  const assessmentsPagination: PaginationType<Assessment> = {
    data: recentAssessments,
    current_page: 1,
    last_page: 1,
    per_page: recentAssessments.length,
    total: recentAssessments.length,
    from: 1,
    to: recentAssessments.length,
    first_page_url: '',
    last_page_url: '',
    next_page_url: null,
    prev_page_url: null,
    path: '',
    links: [],
  };

  return (
    <AuthenticatedLayout
      title={classItem.display_name || classItem.name}
      breadcrumb={breadcrumbs.teacher.showClass(classItem)}
    >
      <div className="space-y-6">
        <Section
          title={classItem.display_name || classItem.name}
          subtitle={trans('teacher_pages.classes.show_subtitle')}
          actions={
            <div className="flex space-x-3">
              <Button size="sm" variant="outline" color="secondary" onClick={handleBack}>
                {trans('teacher_pages.classes.back')}
              </Button>
              <Button size="sm" variant="solid" color="primary" onClick={handleViewAssessments}>
                {trans('teacher_pages.classes.all_assessments')}
              </Button>
            </div>
          }
        >
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="flex items-start space-x-3">
              <AcademicCapIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('teacher_pages.classes.level')}
                </div>
                <div className="mt-1 text-sm text-gray-900">
                  {classItem.level?.name || '-'}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <CalendarIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('teacher_pages.classes.academic_year')}
                </div>
                <div className="mt-1 text-sm text-gray-900">
                  {classItem.academic_year?.name || '-'}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <UserGroupIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('teacher_pages.classes.students')}
                </div>
                <div className="mt-1 text-sm font-semibold text-gray-900">
                  {activeEnrollments.length}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <BookOpenIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('teacher_pages.classes.my_subjects')}
                </div>
                <div className="mt-1 text-sm font-semibold text-gray-900">
                  {mySubjects.length}
                </div>
              </div>
            </div>
          </div>
        </Section>

        <Section
          title={trans('teacher_pages.classes.subjects_section_title')}
          subtitle={trans('teacher_pages.classes.subjects_section_subtitle')}
        >
          <DataTable data={subjectsPagination} config={subjectsTableConfig} />
        </Section>

        <Section
          title={trans('teacher_pages.classes.recent_assessments_title')}
          subtitle={trans('teacher_pages.classes.recent_assessments_subtitle')}
          actions={
            recentAssessments.length > 0 && (
              <Button size="sm" variant="outline" color="secondary" onClick={handleViewAssessments}>
                {trans('teacher_pages.classes.view_all')}
              </Button>
            )
          }
        >
          <DataTable data={assessmentsPagination} config={assessmentsTableConfig} />
        </Section>
      </div>
    </AuthenticatedLayout>
  );
}
