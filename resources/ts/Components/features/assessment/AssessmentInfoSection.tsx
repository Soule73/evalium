import React from 'react';
import { formatAssessmentScore, formatDate, formatDuration, getAssignmentBadgeLabel, securityViolationLabel } from '@/utils';
import { type Assessment, type AssessmentAssignment, type User } from '@/types';
import { trans } from '@/utils';
import { TextEntry, AlertEntry } from '@examena/ui';

interface AssessmentInfoSectionProps {
    assessment: Assessment;
    student?: User;
    assignment: AssessmentAssignment;
    creator?: User;
    score?: number;
    totalPoints: number;
    percentage: number;
    isPendingReview?: boolean;
    isReviewMode?: boolean;
    isStudentView?: boolean;
}

/**
 * Renders a section displaying detailed information about an assessment, including assessment metadata,
 * student and creator details, submission status, score, percentage, and other relevant data.
 * 
 * The component adapts its display based on the provided props, such as review mode, student view,
 * and pending review status.
 * 
 * @component
 * @param {Object} props - The props for AssessmentInfoSection.
 * @param {Assessment} props.assessment - The assessment object containing title, description, duration, and questions.
 * @param {Student} [props.student] - The student object, if applicable.
 * @param {Assignment} [props.assignment] - The assignment object containing submission and score info.
 * @param {User} [props.creator] - The creator (professor) of the assessment.
 * @param {number} [props.score] - The calculated score for the assessment.
 * @param {number} [props.totalPoints] - The total possible points for the assessment.
 * @param {number} [props.percentage] - The percentage score achieved.
 * @param {boolean} [props.isPendingReview=false] - Whether the assessment is pending manual review.
 * @param {boolean} [props.isReviewMode=false] - Whether the component is in review mode.
 * @param {boolean} [props.isStudentView=false] - Whether the component is being viewed by a student.
 * 
 * @returns {JSX.Element} The rendered assessment information section.
 */
const AssessmentInfoSection: React.FC<AssessmentInfoSectionProps> = ({
    assessment,
    student,
    assignment,
    creator,
    score,
    totalPoints,
    percentage,
    isPendingReview = false,
    isReviewMode = false,
    isStudentView = false
}) => {
    return (
        <>
            <div className='space-y-3'>

                {isStudentView ? (
                    <TextEntry label={assessment.title} value={assessment.description ?? ''} />
                ) : (
                    <>
                        <TextEntry label={trans('components.assessment_info_section.assessment_label')} value={assessment.title} />
                        <TextEntry label={trans('components.assessment_info_section.description_label')} value={assessment.description ?? ''} />
                    </>
                )}
                {creator && (
                    <TextEntry label={trans('components.assessment_info_section.teacher_label')} value={creator.name} />
                )}
            </div>

            <div className={`grid grid-cols-1 gap-4 ${student ? 'md:grid-cols-4' : 'md:grid-cols-2'}`}>
                {student && (
                    <>
                        <TextEntry label={trans('components.assessment_info_section.student_label')} value={student.name} />
                        <TextEntry label={trans('components.assessment_info_section.email_label')} value={student.email} />
                    </>
                )}
                <TextEntry
                    label={trans('components.assessment_info_section.submitted_on')}
                    value={assignment?.submitted_at ? formatDate(assignment.submitted_at, 'datetime') : '-'}
                />
                <TextEntry label={trans('components.assessment_info_section.duration_label')} value={formatDuration(assessment?.duration_minutes ?? 0)} />
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-3 border-t border-gray-300 pt-4 pb-4 mt-4">
                <TextEntry
                    label={
                        isReviewMode
                            ? trans('components.assessment_info_section.score_assigned')
                            : isPendingReview
                                ? trans('components.assessment_info_section.score_pending')
                                : isStudentView
                                    ? trans('components.assessment_info_section.score_label')
                                    : trans('components.assessment_info_section.score_final')
                    }
                    value={
                        isReviewMode
                            ? `${score || 0} / ${totalPoints} points`
                            : formatAssessmentScore(assignment.score, totalPoints, isPendingReview, assignment.auto_score)
                    }
                />
                <TextEntry
                    label={trans('components.assessment_info_section.percentage_label')}
                    value={`${percentage}%`}
                />
                <TextEntry
                    label={isReviewMode ? trans('components.assessment_info_section.questions_label') : trans('components.assessment_info_section.status_label')}
                    value={
                        isReviewMode
                            ? trans('components.assessment_info_section.questions_count', { count: assessment.questions?.length || 0 })
                            : isStudentView
                                ? (isPendingReview ? trans('components.assessment_info_section.pending_correction') : trans('components.assessment_info_section.finished'))
                                : getAssignmentBadgeLabel(assignment.status)
                    }
                />

                {assignment.forced_submission && (
                    <AlertEntry title={trans('components.assessment_info_section.automatic_submission')} type="error" className="md:col-span-3">
                        <p className="text-sm">
                            {trans('components.assessment_info_section.automatic_submission_message')}
                        </p>
                        <p className="text-sm">
                            {trans('components.assessment_info_section.violation_detected_label', { violation: securityViolationLabel(assignment.security_violation) })}
                        </p>
                    </AlertEntry>
                )}

            </div>
        </>
    );
};

export { AssessmentInfoSection };