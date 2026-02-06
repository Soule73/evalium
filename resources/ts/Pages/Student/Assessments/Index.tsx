import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Assessment, AssessmentAssignment, PageProps } from '@/types';
import type { PaginationType } from '@/types/datatable';
import { Badge, Button, DataTable, EmptyState, Section, Input } from '@/Components';
import { trans, formatDate, formatDuration } from '@/utils';
import { ClockIcon, DocumentTextIcon } from '@heroicons/react/24/outline';

interface StudentAssessmentsIndexProps extends PageProps {
  assignments: PaginationType<AssessmentAssignment & { assessment: Assessment }>;
  filters: {
    status?: string;
    search?: string;
  };
}

export default function Index({ assignments, filters }: StudentAssessmentsIndexProps) {
  const translations = {
    title: trans('student_assessment_pages.index.title'),
    subtitle: trans('student_assessment_pages.index.subtitle'),
    assessmentsCount: trans('student_assessment_pages.index.assessments_count', {
      count: assignments.total,
    }),
    subject: trans('student_assessment_pages.index.subject'),
    class: trans('student_assessment_pages.index.class'),
    teacher: trans('student_assessment_pages.index.teacher'),
    dueDate: trans('student_assessment_pages.index.due_date'),
    status: trans('student_assessment_pages.index.status'),
    actions: trans('student_assessment_pages.index.actions'),
    viewDetails: trans('student_assessment_pages.index.view_details'),
    takeAssessment: trans('student_assessment_pages.index.take_assessment'),
    continueAssessment: trans('student_assessment_pages.index.continue_assessment'),
    viewResults: trans('student_assessment_pages.index.view_results'),
    notStarted: trans('student_assessment_pages.index.not_started'),
    inProgress: trans('student_assessment_pages.index.in_progress'),
    completed: trans('student_assessment_pages.index.completed'),
    graded: trans('student_assessment_pages.index.graded'),
    filterStatus: trans('student_assessment_pages.index.filter_status'),
    filterAll: trans('student_assessment_pages.index.filter_all'),
    filterNotStarted: trans('student_assessment_pages.index.filter_not_started'),
    filterInProgress: trans('student_assessment_pages.index.filter_in_progress'),
    filterCompleted: trans('student_assessment_pages.index.filter_completed'),
    filterGraded: trans('student_assessment_pages.index.filter_graded'),
    searchPlaceholder: trans('student_assessment_pages.index.search_placeholder'),
    emptyTitle: trans('student_assessment_pages.index.empty_title'),
    emptySubtitle: trans('student_assessment_pages.index.empty_subtitle'),
    duration: trans('student_assessment_pages.index.duration'),
    questionsCount: trans('student_assessment_pages.index.questions_count'),
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'not_submitted':
        return <Badge label={translations.notStarted} type="warning" />;
      case 'submitted':
        return <Badge label={translations.completed} type="info" />;
      case 'graded':
        return <Badge label={translations.graded} type="success" />;
      default:
        return <Badge label={status} type="gray" />;
    }
  };

  const getActionButton = (assignment: AssessmentAssignment & { assessment: Assessment }) => {
    if (assignment.status === 'graded' || assignment.status === 'submitted') {
      return (
        <Button
          size="sm"
          variant="outline"
          onClick={() => router.visit(route('student.assessments.results', assignment.assessment_id))}
        >
          {translations.viewResults}
        </Button>
      );
    }

    return (
      <Button
        size="sm"
        onClick={() => router.visit(route('student.assessments.show', assignment.assessment_id))}
      >
        {translations.viewDetails}
      </Button>
    );
  };

  const columns = [
    {
      key: 'title',
      label: translations.title,
      render: (assignment: AssessmentAssignment & { assessment: Assessment }) => (
        <div>
          <div className="font-medium text-gray-900">{assignment.assessment.title}</div>
          <div className="text-sm text-gray-500">
            <ClockIcon className="inline w-4 h-4 mr-1" />
            {formatDuration(assignment.assessment.duration_minutes)} - {assignment.assessment.questions_count || 0}{' '}
            {translations.questionsCount}
          </div>
        </div>
      ),
    },
    {
      key: 'subject',
      label: translations.subject,
      render: (assignment: AssessmentAssignment & { assessment: Assessment }) => (
        <span className="text-gray-700">{assignment.assessment.class_subject?.subject?.name || '-'}</span>
      ),
    },
    {
      key: 'class',
      label: translations.class,
      render: (assignment: AssessmentAssignment & { assessment: Assessment }) => (
        <span className="text-gray-700">{assignment.assessment.class_subject?.class?.name || '-'}</span>
      ),
    },
    {
      key: 'teacher',
      label: translations.teacher,
      render: (assignment: AssessmentAssignment & { assessment: Assessment }) => (
        <span className="text-gray-700">{assignment.assessment.class_subject?.teacher?.name || '-'}</span>
      ),
    },
    {
      key: 'assessment_date',
      label: translations.dueDate,
      render: (assignment: AssessmentAssignment & { assessment: Assessment }) => (
        <span className="text-gray-700">{formatDate(assignment.assessment.scheduled_at)}</span>
      ),
    },
    {
      key: 'status',
      label: translations.status,
      render: (assignment: AssessmentAssignment & { assessment: Assessment }) => getStatusBadge(assignment.status),
    },
    {
      key: 'actions',
      label: translations.actions,
      render: (assignment: AssessmentAssignment & { assessment: Assessment }) => getActionButton(assignment),
    },
  ];

  const handleFilterChange = (key: string, value: string) => {
    router.get(
      route('student.assessments.index'),
      { ...filters, [key]: value || undefined },
      { preserveState: true, preserveScroll: true }
    );
  };

  const statusOptions = [
    { value: '', label: translations.filterAll },
    { value: 'not_submitted', label: translations.filterNotStarted },
    { value: 'submitted', label: translations.filterCompleted },
    { value: 'graded', label: translations.filterGraded },
  ];

  return (
    <AuthenticatedLayout title={translations.title}>
      <Section
        title={translations.title}
        subtitle={translations.subtitle}
        actions={
          <div className="flex items-center space-x-4">
            <Input
              value={filters.search || ''}
              onChange={(e: React.ChangeEvent<HTMLInputElement>) => handleFilterChange('search', e.target.value)}
              placeholder={translations.searchPlaceholder}
            />
            <select
              value={filters.status || ''}
              onChange={(e: React.ChangeEvent<HTMLSelectElement>) => handleFilterChange('status', e.target.value)}
              className="px-3 py-2 border border-gray-300 rounded-md"
            >
              {statusOptions.map(opt => (
                <option key={opt.value} value={opt.value}>{opt.label}</option>
              ))}
            </select>
          </div>
        }
      >
        {assignments.data.length === 0 ? (
          <EmptyState
            icon={<DocumentTextIcon className="w-12 h-12" />}
            title={translations.emptyTitle}
            subtitle={translations.emptySubtitle}
          />
        ) : (
          <DataTable
            data={assignments}
            config={{
              columns,
              emptyState: {
                title: translations.emptyTitle,
                subtitle: translations.emptySubtitle
              }
            }}
          />
        )}
      </Section>
    </AuthenticatedLayout>
  );
}
