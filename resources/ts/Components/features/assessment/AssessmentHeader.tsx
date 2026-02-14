import { type Assessment } from '@/types';
import { formatDuration } from '@/utils';
import { ClockIcon, QuestionMarkCircleIcon, CalendarIcon } from '@heroicons/react/24/outline';
import { formatDate } from '@/utils';
import { trans } from '@/utils';
import { MarkdownRenderer, Stat } from '@examena/ui';

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
export function AssessmentHeader({
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
        <Stat.Group columns={3}>
          {assessment.duration_minutes && (
            <Stat.Item
              title={trans('components.assessment_header.duration')}
              value={formatDuration(assessment.duration_minutes)}
              icon={ClockIcon}
            />
          )}
          {assessment.questions && assessment.questions.length > 0 && (
            <Stat.Item
              title={trans('components.assessment_header.questions_count')}
              value={assessment.questions.length}
              icon={QuestionMarkCircleIcon}
            />
          )}
          {assessment.created_at && (
            <Stat.Item
              title={trans('components.assessment_header.created_on')}
              value={formatDate(assessment.created_at)}
              icon={CalendarIcon}
            />

          )}
        </Stat.Group>
      )}
    </div>
  );
}
