import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { ClassModel, Enrollment, ClassSubject, Assessment, PageProps, User } from '@/types';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import { breadcrumbs, trans, formatDate } from '@/utils';
import { Button, Section, Badge, DataTable } from '@/Components';
import { route } from 'ziggy-js';
import { AcademicCapIcon, UserGroupIcon, BookOpenIcon, CalendarIcon } from '@heroicons/react/24/outline';

interface EnrollmentWithStudent extends Enrollment {
  student?: User;
}

interface Props extends PageProps {
  class: ClassModel;
  subjects: PaginationType<ClassSubject>;
  assessments: PaginationType<Assessment>;
  students: PaginationType<EnrollmentWithStudent>;
}

export default function TeacherClassShow({ class: classItem, subjects, assessments, students }: Props) {
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

  const mySubjectsCount = subjects.total;
  const studentsCount = students.total;

  const subjectsTableConfig: DataTableConfig<ClassSubject> = {
    columns: [
      {
        key: 'subject',
        label: trans('teacher_class_pages.show.subject_name'),
        render: (cs) => (
          <div>
            <div className="font-medium text-gray-900">{cs.subject?.name}</div>
            <div className="text-sm text-gray-500">{cs.subject?.code}</div>
          </div>
        ),
      },
      {
        key: 'coefficient',
        label: trans('teacher_class_pages.show.coefficient'),
        render: (cs) => (
          <span className="text-sm text-gray-900">{cs.coefficient}</span>
        ),
      },
      {
        key: 'assessments_count',
        label: trans('teacher_class_pages.show.assessments_count'),
        render: (cs) => (
          <Badge label={String(cs.assessments?.length || 0)} type="info" size="sm" />
        ),
      },
      {
        key: 'actions',
        label: trans('teacher_class_pages.index.actions'),
        render: (cs) => (
          <Button
            size="sm"
            variant="solid"
            color="primary"
            onClick={() => handleCreateAssessment(cs.id)}
          >
            {trans('teacher_class_pages.show.create_assessment')}
          </Button>
        ),
      },
    ],
    searchPlaceholder: trans('teacher_class_pages.show.search_subjects') || 'Search subjects...',
    perPageOptions: [10, 25, 50],
    emptyState: {
      title: trans('teacher_class_pages.show.no_subjects'),
      subtitle: trans('teacher_class_pages.show.no_subjects_description'),
    },
    emptySearchState: {
      title: trans('teacher_class_pages.show.no_subjects_found'),
      subtitle: trans('teacher_class_pages.show.no_subjects_found_description'),
      resetLabel: trans('teacher_class_pages.index.reset_search') || 'Clear search',
    },
  };



  const assessmentsTableConfig: DataTableConfig<Assessment> = {
    columns: [
      {
        key: 'title',
        label: trans('teacher_class_pages.show.assessment_title'),
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
        label: trans('teacher_class_pages.show.assessment_type'),
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
              label={trans(`teacher_class_pages.show.type_${assessment.type}`)}
              type={typeColors[assessment.type] || 'gray'}
              size="sm"
            />
          );
        },
      },
      {
        key: 'date',
        label: trans('teacher_class_pages.show.assessment_date'),
        render: (assessment) => (
          <span className="text-sm text-gray-600">
            {formatDate(assessment.scheduled_at ?? '', 'datetime') || '-'}
          </span>
        ),
      },
      {
        key: 'status',
        label: trans('teacher_class_pages.show.status'),
        render: (assessment) => (
          <Badge
            label={assessment.is_published
              ? trans('teacher_class_pages.show.published')
              : trans('teacher_class_pages.show.draft')}
            type={assessment.is_published ? 'success' : 'gray'}
            size="sm"
          />
        ),
      },
      {
        key: 'actions',
        label: trans('teacher_class_pages.index.actions'),
        render: (assessment) => (
          <Button
            size="sm"
            variant="outline"
            color="secondary"
            onClick={() => handleViewAssessment(assessment)}
          >
            {trans('teacher_class_pages.show.view_details')}
          </Button>
        ),
      },
    ],
    searchPlaceholder: trans('teacher_class_pages.show.search_assessments') || 'Search assessments...',
    perPageOptions: [10, 25, 50],
    emptyState: {
      title: trans('teacher_class_pages.show.no_assessments'),
      subtitle: trans('teacher_class_pages.show.no_assessments_description'),
    },
    emptySearchState: {
      title: trans('teacher_class_pages.show.no_assessments_found'),
      subtitle: trans('teacher_class_pages.show.no_assessments_found_description'),
      resetLabel: trans('teacher_class_pages.index.reset_search') || 'Clear search',
    },
  };

  const studentsTableConfig: DataTableConfig<EnrollmentWithStudent> = {
    columns: [
      {
        key: 'student',
        label: trans('teacher_class_pages.show.student_name'),
        render: (enrollment) => (
          <div>
            <div className="font-medium text-gray-900">{enrollment.student?.name}</div>
            <div className="text-sm text-gray-500">{enrollment.student?.email}</div>
          </div>
        ),
      },
      {
        key: 'enrolled_at',
        label: trans('teacher_class_pages.show.enrolled_at'),
        render: (enrollment) => (
          <span className="text-sm text-gray-600">
            {enrollment.enrolled_at ? formatDate(enrollment.enrolled_at, 'short') : '-'}
          </span>
        ),
      },
    ],
    searchPlaceholder: trans('teacher_class_pages.show.search_students'),
    perPageOptions: [10, 25, 50],
    emptyState: {
      title: trans('teacher_class_pages.show.no_students'),
      subtitle: trans('teacher_class_pages.show.no_students_description'),
    },
    emptySearchState: {
      title: trans('teacher_class_pages.show.no_students_found'),
      subtitle: trans('teacher_class_pages.show.no_students_found_description'),
      resetLabel: trans('teacher_class_pages.index.reset_search'),
    },
  };

  return (
    <AuthenticatedLayout
      title={classItem.name}
      breadcrumb={breadcrumbs.teacher.showClass(classItem)}
    >
      <div className="space-y-6">
        <Section
          title={classItem.name}
          subtitle={trans('teacher_class_pages.show.show_subtitle')}
          actions={
            <div className="flex space-x-3">
              <Button size="sm" variant="outline" color="secondary" onClick={handleBack}>
                {trans('teacher_class_pages.show.back')}
              </Button>
              <Button size="sm" variant="solid" color="primary" onClick={handleViewAssessments}>
                {trans('teacher_class_pages.show.all_assessments')}
              </Button>
            </div>
          }
        >
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="flex items-start space-x-3">
              <AcademicCapIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('teacher_class_pages.show.level')}
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
                  {trans('teacher_class_pages.show.academic_year')}
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
                  {trans('teacher_class_pages.show.students')}
                </div>
                <div className="mt-1 text-sm font-semibold text-gray-900">
                  {studentsCount}
                </div>
              </div>
            </div>

            <div className="flex items-start space-x-3">
              <BookOpenIcon className="w-5 h-5 text-gray-400 mt-1" />
              <div>
                <div className="text-sm font-medium text-gray-500">
                  {trans('teacher_class_pages.show.my_subjects')}
                </div>
                <div className="mt-1 text-sm font-semibold text-gray-900">
                  {mySubjectsCount}
                </div>
              </div>
            </div>
          </div>
        </Section>

        <Section
          title={trans('teacher_class_pages.show.subjects_section_title')}
          subtitle={trans('teacher_class_pages.show.subjects_section_subtitle')}
        >
          <DataTable data={subjects} config={subjectsTableConfig} />
        </Section>

        <Section
          title={trans('teacher_class_pages.show.recent_assessments_title')}
          subtitle={trans('teacher_class_pages.show.recent_assessments_subtitle')}
          actions={
            assessments.total > 0 && (
              <Button size="sm" variant="outline" color="secondary" onClick={handleViewAssessments}>
                {trans('teacher_class_pages.show.view_all')}
              </Button>
            )
          }
        >
          <DataTable data={assessments} config={assessmentsTableConfig} />
        </Section>

        <Section
          title={trans('teacher_class_pages.show.students_section_title')}
          subtitle={trans('teacher_class_pages.show.students_section_subtitle', { count: students.total })}
        >
          <DataTable data={students} config={studentsTableConfig} />
        </Section>
      </div>
    </AuthenticatedLayout>
  );
}
