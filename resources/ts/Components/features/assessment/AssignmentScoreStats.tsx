import { Badge, Stat } from '@/Components';
import { formatDate } from '@/utils';
import { DocumentTextIcon, ChartPieIcon, CheckCircleIcon } from '@heroicons/react/24/outline';
import { useTranslations } from '@/hooks/shared/useTranslations';
import type { AssessmentAssignment } from '@/types';

interface Props {
    calculatedTotalScore: number;
    totalPoints: number;
    percentage: number;
    assignment: AssessmentAssignment;
    showGradedAt?: boolean;
}

/**
 * Shared statistics block for assignment review and grading pages.
 *
 * Displays score, percentage and submission status using a standardised
 * Stat.Group layout. Optionally shows a fourth column with graded_at date.
 *
 * @param calculatedTotalScore - Computed score based on current answers / overrides
 * @param totalPoints - Maximum achievable points for the assessment
 * @param percentage - Computed percentage (0–100)
 * @param assignment - The student's assignment record
 * @param showGradedAt - When true, adds a graded_at column (4-column layout)
 */
export function AssignmentScoreStats({
    calculatedTotalScore,
    totalPoints,
    percentage,
    assignment,
    showGradedAt = false,
}: Props) {
    const { t } = useTranslations();

    return (
        <Stat.Group columns={showGradedAt ? 4 : 3}>
            <Stat.Item
                title={t('grading_pages.show.total_score')}
                value={`${calculatedTotalScore} / ${totalPoints}`}
                icon={DocumentTextIcon}
            />
            <Stat.Item
                title={t('grading_pages.show.percentage')}
                value={`${percentage}%`}
                icon={ChartPieIcon}
            />
            <Stat.Item
                title={t('grading_pages.show.status')}
                value={
                    <Badge
                        size="sm"
                        label={
                            assignment.submitted_at
                                ? t('grading_pages.show.submitted')
                                : t('grading_pages.show.not_submitted')
                        }
                        type={assignment.submitted_at ? 'success' : 'gray'}
                    />
                }
                icon={CheckCircleIcon}
            />
            {showGradedAt && (
                <Stat.Item
                    title={t('grading_pages.review.graded_at')}
                    value={
                        assignment.graded_at ? formatDate(assignment.graded_at, 'datetime') : '-'
                    }
                    icon={DocumentTextIcon}
                />
            )}
        </Stat.Group>
    );
}
