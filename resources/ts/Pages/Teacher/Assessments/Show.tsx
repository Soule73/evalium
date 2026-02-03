import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import {
  PencilIcon,
  TrashIcon,
  DocumentDuplicateIcon,
  UserGroupIcon,
  ClipboardDocumentCheckIcon,
} from '@heroicons/react/24/outline';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Button, Section, Badge } from '@/Components';
import { Assessment, AssessmentStatistics } from '@/types';
import { breadcrumbs, trans, formatDate } from '@/utils';

interface Props {
  assessment: Assessment;
  statistics: AssessmentStatistics;
}

export default function Show({ assessment, statistics }: Props) {
  const handleEdit = () => {
    router.visit(route('teacher.assessments.edit', assessment.id));
  };

  const handleDelete = () => {
    if (confirm(trans('teacher_pages.assessments.confirm_delete'))) {
      router.delete(route('teacher.assessments.destroy', assessment.id), {
        onSuccess: () => router.visit(route('teacher.assessments.index')),
      });
    }
  };

  const handleDuplicate = () => {
    router.post(route('teacher.assessments.duplicate', assessment.id));
  };

  const handleManageAssignments = () => {
    router.visit(route('teacher.assessment-assignments.index', { assessment_id: assessment.id }));
  };

  const handleViewGrading = () => {
    router.visit(route('teacher.grading.index', { assessment_id: assessment.id }));
  };

  const handleTogglePublish = () => {
    router.patch(route('teacher.assessments.toggle-publish', assessment.id));
  };

  const statusBadge = assessment.is_published
    ? { type: 'success' as const, label: trans('teacher_pages.assessments.filters.published') }
    : { type: 'gray' as const, label: trans('teacher_pages.assessments.filters.draft') };

  const completionRate = statistics.total_assigned > 0
    ? (statistics.completed / statistics.total_assigned) * 100
    : 0;

  return (
    <AuthenticatedLayout
      title={assessment.title}
      breadcrumb={breadcrumbs.showTeacherAssessment(assessment)}
    >
      <Section
        title={assessment.title}
        subtitle={trans('teacher_pages.assessments.show.subtitle')}
        actions={
          <div className="flex gap-2">
            <Button
              onClick={handleTogglePublish}
              color={assessment.is_published ? 'warning' : 'success'}
              variant="outline"
              size="sm"
            >
              {assessment.is_published
                ? trans('teacher_pages.assessments.unpublish')
                : trans('teacher_pages.assessments.publish')
              }
            </Button>
            <Button
              onClick={handleEdit}
              color="secondary"
              variant="outline"
              size="sm"
            >
              <PencilIcon className="w-4 h-4 mr-1" />
              {trans('common.edit')}
            </Button>
            <Button
              onClick={handleDuplicate}
              color="secondary"
              variant="outline"
              size="sm"
            >
              <DocumentDuplicateIcon className="w-4 h-4 mr-1" />
              {trans('teacher_pages.assessments.duplicate')}
            </Button>
            <Button
              onClick={handleDelete}
              color="danger"
              variant="outline"
              size="sm"
            >
              <TrashIcon className="w-4 h-4 mr-1" />
              {trans('common.delete')}
            </Button>
          </div>
        }
      >
        <div className="space-y-6">
          <div className="bg-white border border-gray-200 rounded-lg p-6">
            <div className="flex justify-between items-start mb-6">
              <div>
                <h3 className="text-lg font-semibold text-gray-900 mb-2">
                  {trans('teacher_pages.assessments.show.basic_info')}
                </h3>
                <p className="text-sm text-gray-600">
                  {assessment.class_subject?.class?.name} - {assessment.class_subject?.subject?.name}
                </p>
              </div>
              <Badge type={statusBadge.type} label={statusBadge.label} />
            </div>

            {assessment.description && (
              <div className="mb-6">
                <p className="text-gray-700">{assessment.description}</p>
              </div>
            )}

            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
              <div>
                <p className="text-sm text-gray-600">{trans('teacher_pages.assessments.show.type')}</p>
                <p className="font-medium text-gray-900 capitalize">
                  {trans(`teacher_pages.assessments.types.${assessment.type}`)}
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-600">{trans('teacher_pages.assessments.show.date')}</p>
                <p className="font-medium text-gray-900">
                  {formatDate(assessment.assessment_date)}
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-600">{trans('teacher_pages.assessments.show.duration')}</p>
                <p className="font-medium text-gray-900">
                  {assessment.duration} {trans('teacher_pages.assessments.minutes')}
                </p>
              </div>
              <div>
                <p className="text-sm text-gray-600">{trans('teacher_pages.assessments.show.coefficient')}</p>
                <p className="font-medium text-gray-900">{assessment.coefficient}</p>
              </div>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="bg-white border border-gray-200 rounded-lg p-6">
              <div className="flex items-center justify-between mb-2">
                <h4 className="text-sm font-medium text-gray-600">
                  {trans('teacher_pages.assessments.show.total_assigned')}
                </h4>
                <UserGroupIcon className="w-5 h-5 text-blue-500" />
              </div>
              <p className="text-3xl font-bold text-gray-900">{statistics.total_assigned}</p>
            </div>

            <div className="bg-white border border-gray-200 rounded-lg p-6">
              <div className="flex items-center justify-between mb-2">
                <h4 className="text-sm font-medium text-gray-600">
                  {trans('teacher_pages.assessments.show.completed')}
                </h4>
                <ClipboardDocumentCheckIcon className="w-5 h-5 text-green-500" />
              </div>
              <p className="text-3xl font-bold text-gray-900">{statistics.completed}</p>
              <div className="mt-2">
                <div className="w-full bg-gray-200 rounded-full h-2">
                  <div
                    className="bg-green-600 h-2 rounded-full"
                    style={{ width: `${completionRate}%` }}
                  />
                </div>
                <p className="text-xs text-gray-500 mt-1">
                  {completionRate.toFixed(0)}% {trans('teacher_pages.assessments.show.completion_rate')}
                </p>
              </div>
            </div>

            <div className="bg-white border border-gray-200 rounded-lg p-6">
              <div className="flex items-center justify-between mb-2">
                <h4 className="text-sm font-medium text-gray-600">
                  {trans('teacher_pages.assessments.show.average_score')}
                </h4>
              </div>
              <p className="text-3xl font-bold text-gray-900">
                {statistics.average_score ? `${statistics.average_score.toFixed(2)}/20` : '-'}
              </p>
              {statistics.highest_score !== undefined && statistics.lowest_score !== undefined && (
                <div className="mt-2 text-xs text-gray-500">
                  <p>{trans('teacher_pages.assessments.show.highest')}: {statistics.highest_score.toFixed(2)}</p>
                  <p>{trans('teacher_pages.assessments.show.lowest')}: {statistics.lowest_score.toFixed(2)}</p>
                </div>
              )}
            </div>
          </div>

          <div className="bg-white border border-gray-200 rounded-lg p-6">
            <h3 className="text-lg font-semibold text-gray-900 mb-4">
              {trans('teacher_pages.assessments.show.questions')} ({assessment.questions_count || 0})
            </h3>

            {assessment.questions && assessment.questions.length > 0 ? (
              <div className="space-y-4">
                {assessment.questions.map((question, index) => (
                  <div key={question.id} className="border border-gray-200 rounded-lg p-4">
                    <div className="flex justify-between items-start mb-2">
                      <h4 className="font-medium text-gray-900">
                        {trans('teacher_pages.assessments.show.question_number', { number: index + 1 })}
                      </h4>
                      <Badge
                        type="info"
                        label={`${question.points} ${trans('teacher_pages.assessments.show.points')}`}
                      />
                    </div>
                    <p className="text-gray-700 mb-3">{question.content}</p>
                    <div className="flex items-center gap-2 text-sm text-gray-600">
                      <Badge
                        type="gray"
                        label={trans(`teacher_pages.assessments.question_types.${question.type}`)}
                      />
                      {question.choices && question.choices.length > 0 && (
                        <span>• {question.choices.length} {trans('teacher_pages.assessments.show.choices')}</span>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center py-8 text-gray-500">
                <p>{trans('teacher_pages.assessments.show.no_questions')}</p>
              </div>
            )}
          </div>

          <div className="flex gap-4">
            <Button
              onClick={handleManageAssignments}
              color="primary"
              className="flex-1"
            >
              <UserGroupIcon className="w-5 h-5 mr-2" />
              {trans('teacher_pages.assessments.show.manage_assignments')}
            </Button>
            {assessment.is_published && (
              <Button
                onClick={handleViewGrading}
                color="secondary"
                className="flex-1"
              >
                <ClipboardDocumentCheckIcon className="w-5 h-5 mr-2" />
                {trans('teacher_pages.assessments.show.view_grading')}
              </Button>
            )}
          </div>
        </div>
      </Section>
    </AuthenticatedLayout>
  );
}
