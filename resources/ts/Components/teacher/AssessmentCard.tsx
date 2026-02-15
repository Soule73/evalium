import { type Assessment } from '@/types';
import { formatDate } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import {
  DocumentTextIcon,
  ClockIcon,
  CalendarDaysIcon,
  CheckCircleIcon,
} from '@heroicons/react/24/outline';

interface AssessmentCardProps {
  assessment: Assessment;
  onClick?: () => void;
}

export function AssessmentCard({ assessment, onClick }: AssessmentCardProps) {
  const { t } = useTranslations();
  const completionRate = assessment.assignments_count && assessment.assignments_count > 0
    ? (assessment.completed_assignments_count || 0) / assessment.assignments_count
    : 0;

  const statusBadge = assessment.is_published
    ? { color: 'bg-green-100 text-green-800', label: t('teacher_pages.assessments.filters.published') }
    : { color: 'bg-gray-100 text-gray-800', label: t('teacher_pages.assessments.filters.draft') };

  const typeBadge = {
    color: 'bg-indigo-100 text-indigo-800',
    label: t(`teacher_pages.assessments.types.${assessment.type}`)
  };

  return (
    <div
      className="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow cursor-pointer"
      onClick={onClick}
    >
      <div className="flex justify-between items-start mb-4">
        <div className="flex-1">
          <h3 className="text-lg font-semibold text-gray-900 mb-1">
            {assessment.title}
          </h3>
          <p className="text-sm text-gray-600">
            {assessment.class_subject?.class?.name} - {assessment.class_subject?.subject?.name}
          </p>
        </div>
        <div className="flex gap-2 ml-4">
          <span className={`px-2 py-1 text-xs font-medium rounded-full ${typeBadge.color}`}>
            {typeBadge.label}
          </span>
          <span className={`px-2 py-1 text-xs font-medium rounded-full ${statusBadge.color}`}>
            {statusBadge.label}
          </span>
        </div>
      </div>

      {assessment.description && (
        <p className="text-sm text-gray-600 mb-4 line-clamp-2">
          {assessment.description}
        </p>
      )}

      <div className="grid grid-cols-2 gap-4 mb-4">
        <div className="flex items-center text-sm text-gray-600">
          <CalendarDaysIcon className="w-4 h-4 mr-2" />
          <span>{formatDate(assessment.scheduled_at ?? '') || '-'}</span>
        </div>
        <div className="flex items-center text-sm text-gray-600">
          <ClockIcon className="w-4 h-4 mr-2" />
          <span>{assessment.duration_minutes} {t('teacher_pages.assessments.minutes')}</span>
        </div>
        <div className="flex items-center text-sm text-gray-600">
          <DocumentTextIcon className="w-4 h-4 mr-2" />
          <span>
            {assessment.questions_count || 0} {t('teacher_pages.assessments.card.questions')}
          </span>
        </div>
        <div className="flex items-center text-sm text-gray-600">
          <CheckCircleIcon className="w-4 h-4 mr-2" />
          <span>
            {t('teacher_pages.assessments.card.coefficient')}: {assessment.coefficient}
          </span>
        </div>
      </div>

      <div className="border-t border-gray-200 pt-4">
        <div className="flex justify-between items-center mb-2">
          <span className="text-sm text-gray-600">
            {t('teacher_pages.assessments.card.completion')}
          </span>
          <span className="text-sm font-medium text-gray-900">
            {assessment.completed_assignments_count || 0}/{assessment.assignments_count || 0}
          </span>
        </div>
        <div className="w-full bg-gray-200 rounded-full h-2">
          <div
            className="bg-indigo-600 h-2 rounded-full transition-all duration-300"
            style={{ width: `${completionRate * 100}%` }}
          />
        </div>
      </div>
    </div>
  );
}
