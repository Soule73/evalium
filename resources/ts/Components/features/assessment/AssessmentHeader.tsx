import { Assessment } from '@/types';
import { formatDuration } from '@/utils';
import { ClockIcon, QuestionMarkCircleIcon, CalendarIcon } from '@heroicons/react/24/outline';
import { formatDate } from '@/utils';
import { trans } from '@/utils';
import { MarkdownRenderer } from '@examena/ui';

interface AssessmentHeaderProps {
  assessment: Assessment;
  showDescription?: boolean;
  showMetadata?: boolean;
  compact?: boolean;
}

/**
 * Reusable component for displaying assessment header
 * Used in AssessmentShow, AssessmentAssignments, etc.
 */
export default function AssessmentHeader({
  assessment,
  showDescription = true,
  showMetadata = false,
  compact = false
}: AssessmentHeaderProps) {
  return (
    <div className="space-y-3">
      <div>
        <h2 className={`${compact ? 'text-xl' : 'text-2xl'} font-bold text-gray-900`}>
          {assessment.title}
        </h2>
        {showDescription && assessment.description && (
          <div className="mt-2 text-gray-600">
            <MarkdownRenderer>{assessment.description}</MarkdownRenderer>
          </div>
        )}
      </div>

      {showMetadata && (
        <div className="flex flex-wrap gap-4 text-sm text-gray-500">
          {assessment.duration && (
            <div className="flex items-center gap-2">
              <ClockIcon className="w-4 h-4" />
              <span>{formatDuration(assessment.duration)}</span>
            </div>
          )}
          {assessment.questions && assessment.questions.length > 0 && (
            <div className="flex items-center gap-2">
              <QuestionMarkCircleIcon className="w-4 h-4" />
              <span>
                {trans('components.assessment_header.questions_count', { count: assessment.questions.length })}
              </span>
            </div>
          )}
          {assessment.created_at && (
            <div className="flex items-center gap-2">
              <CalendarIcon className="w-4 h-4" />
              <span>{trans('components.assessment_header.created_on', { date: formatDate(assessment.created_at) })}</span>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
