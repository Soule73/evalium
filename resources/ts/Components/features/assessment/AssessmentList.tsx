import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { formatDate, formatDuration } from '@/utils';
import { Assessment, PageProps } from '@/types';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import type { FilterConfig } from '@/types/datatable';
import { hasPermission } from '@/utils';
import { trans } from '@/utils';
import { MarkdownRenderer } from '@examena/ui';
import { Toggle } from '@examena/ui';
import { DataTable } from '@/Components/shared';
import { Button } from '@examena/ui';

interface AssessmentListProps {
  data: PaginationType<Assessment>;
  variant?: 'teacher' | 'admin';
  showFilters?: boolean;
  showSearch?: boolean;
}

/**
 * Component for displaying assessments list with DataTable
 * 
 * Strategy:
 * - Dynamic display based on user permissions
 * - Conditional actions (edit, assign, toggle) based on permissions
 * - Used by teachers and admins
 */
const AssessmentList: React.FC<AssessmentListProps> = ({
  data,
  variant = 'teacher',
  showFilters = true,
  showSearch = true
}) => {
  const { auth } = usePage<PageProps>().props;
  const canViewAssessments = hasPermission(auth.permissions, 'view assessments') || hasPermission(auth.permissions, 'view any assessments');
  const canPublishAssessments = hasPermission(auth.permissions, 'update assessments');

  const [togglingAssessments, setTogglingAssessments] = useState<Set<number>>(new Set());

  const handleToggleStatus = (assessmentId: number, isPublished: boolean) => {
    if (togglingAssessments.has(assessmentId) || !canPublishAssessments) return;

    setTogglingAssessments(prev => new Set(prev).add(assessmentId));

    const routeName = isPublished ? 'teacher.assessments.unpublish' : 'teacher.assessments.publish';

    router.post(
      route(routeName, assessmentId),
      {},
      {
        preserveScroll: true,
        onFinish: () => {
          setTogglingAssessments(prev => {
            const next = new Set(prev);
            next.delete(assessmentId);
            return next;
          });
        },
      }
    );
  };


  const renderTitle = (assessment: Assessment) => (
    <div>
      <div className="text-sm font-medium text-gray-900">{assessment.title}</div>
      <div className="text-sm text-gray-500 truncate max-w-sm line-clamp-2">
        <MarkdownRenderer>
          {assessment.description ?? ''}
        </MarkdownRenderer>
      </div>
    </div>
  );

  const renderDuration = (assessment: Assessment) => (
    <span className="text-sm text-gray-900">{formatDuration(assessment.duration)}</span>
  );

  const renderStatus = (assessment: Assessment) => (
    <div className="flex items-center space-x-2">
      <Toggle
        checked={assessment.is_published}
        onChange={() => handleToggleStatus(assessment.id, assessment.is_published)}
      />
    </div>
  );

  const renderCreatedAt = (assessment: Assessment) => (
    <span className="text-sm text-gray-500">{formatDate(assessment.created_at, "datetime")}</span>
  );

  const renderActions = (assessment: Assessment) => (
    <div className="flex items-center justify-end space-x-2">
      {canViewAssessments && <Button
        size="sm"
        onClick={() => router.visit(route('teacher.assessments.show', assessment.id))}
        title={trans('components.assessment_list.view_assessment_title')}
        variant="outline"
      >
        {trans('components.assessment_list.view_assessment')}
      </Button>}
    </div>
  );

  const columns: DataTableConfig<Assessment>["columns"] =
    variant === 'admin' ? [
      { key: 'title', label: trans('components.assessment_list.assessment_label'), render: renderTitle },
      { key: 'duration', label: trans('components.assessment_list.duration_label'), render: renderDuration },
      { key: 'is_published', label: trans('components.assessment_list.status_label'), render: renderStatus },
      { key: 'created_at', label: trans('components.assessment_list.created_on'), render: renderCreatedAt },
    ] : [
      { key: 'title', label: trans('components.assessment_list.assessment_label'), render: renderTitle },
      { key: 'duration', label: trans('components.assessment_list.duration_label'), render: renderDuration },
      { key: 'is_published', label: trans('components.assessment_list.status_label'), render: renderStatus },
      { key: 'created_at', label: trans('components.assessment_list.created_on'), render: renderCreatedAt },
      { key: 'actions', label: trans('components.assessment_list.actions_label'), className: 'text-right', render: renderActions },
    ];

  const filters: FilterConfig[] = showFilters ? [
    {
      key: 'status',
      label: trans('components.assessment_list.status_label'),
      type: 'select',
      options: [
        { value: '1', label: trans('components.assessment_list.status_published') },
        { value: '0', label: trans('components.assessment_list.status_unpublished') }
      ]
    }
  ] : [];

  const searchPlaceholder = showSearch ? trans('components.assessment_list.search_placeholder') : undefined;

  const emptyState = {
    title: trans('components.assessment_list.empty_title'),
    subtitle: trans('components.assessment_list.empty_subtitle')
  };

  const emptySearchState = {
    title: trans('components.assessment_list.empty_search_title'),
    subtitle: trans('components.assessment_list.empty_search_subtitle'),
    resetLabel: trans('components.assessment_list.reset_filters')
  };

  const perPageOptions = [10, 25, 50];

  const tableConfig: DataTableConfig<Assessment> = {
    columns,
    filters,
    searchPlaceholder,
    emptyState,
    emptySearchState,
    perPageOptions
  };

  return (
    <DataTable
      data={data}
      config={tableConfig}
    />

  );
};
export default AssessmentList;
