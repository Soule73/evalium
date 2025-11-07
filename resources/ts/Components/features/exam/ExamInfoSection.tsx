import React from 'react';
import { formatDate, formatDuration, getAssignmentBadgeLabel, securityViolationLabel } from '@/utils';
import { Exam, ExamAssignment, User } from '@/types';
import { formatExamScore } from '@/utils';
import { trans } from '@/utils';
import { TextEntry, AlertEntry } from '@/Components/ui';

interface ExamInfoSectionProps {
    exam: Exam;
    student?: User;
    assignment: ExamAssignment;
    creator?: User;
    score?: number;
    totalPoints: number;
    percentage: number;
    isPendingReview?: boolean;
    isReviewMode?: boolean;
    isStudentView?: boolean;
}

/**
 * Renders a section displaying detailed information about an exam, including exam metadata,
 * student and creator details, submission status, score, percentage, and other relevant data.
 * 
 * The component adapts its display based on the provided props, such as review mode, student view,
 * and pending review status.
 * 
 * @component
 * @param {Object} props - The props for ExamInfoSection.
 * @param {Exam} props.exam - The exam object containing title, description, duration, and questions.
 * @param {Student} [props.student] - The student object, if applicable.
 * @param {Assignment} [props.assignment] - The assignment object containing submission and score info.
 * @param {User} [props.creator] - The creator (professor) of the exam.
 * @param {number} [props.score] - The calculated score for the exam.
 * @param {number} [props.totalPoints] - The total possible points for the exam.
 * @param {number} [props.percentage] - The percentage score achieved.
 * @param {boolean} [props.isPendingReview=false] - Whether the exam is pending manual review.
 * @param {boolean} [props.isReviewMode=false] - Whether the component is in review mode.
 * @param {boolean} [props.isStudentView=false] - Whether the component is being viewed by a student.
 * 
 * @returns {JSX.Element} The rendered exam information section.
 */
const ExamInfoSection: React.FC<ExamInfoSectionProps> = ({
    exam,
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
                    <TextEntry label={exam.title} value={exam.description ?? ''} />
                ) : (
                    <>
                        <TextEntry label={trans('components.exam_info_section.exam_label')} value={exam.title} />
                        <TextEntry label={trans('components.exam_info_section.description_label')} value={exam.description ?? ''} />
                    </>
                )}
                {creator && (
                    <TextEntry label={trans('components.exam_info_section.teacher_label')} value={creator.name} />
                )}
            </div>

            <div className={`grid grid-cols-1 gap-4 ${student ? 'md:grid-cols-4' : 'md:grid-cols-2'}`}>
                {student && (
                    <>
                        <TextEntry label={trans('components.exam_info_section.student_label')} value={student.name} />
                        <TextEntry label={trans('components.exam_info_section.email_label')} value={student.email} />
                    </>
                )}
                <TextEntry
                    label={trans('components.exam_info_section.submitted_on')}
                    value={assignment?.submitted_at ? formatDate(assignment.submitted_at, 'datetime') : '-'}
                />
                <TextEntry label={trans('components.exam_info_section.duration_label')} value={formatDuration(exam?.duration)} />
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-3 border-t border-gray-300 pt-4 pb-4 mt-4">
                <TextEntry
                    label={
                        isReviewMode
                            ? trans('components.exam_info_section.score_assigned')
                            : isPendingReview
                                ? trans('components.exam_info_section.score_pending')
                                : isStudentView
                                    ? trans('components.exam_info_section.score_label')
                                    : trans('components.exam_info_section.score_final')
                    }
                    value={
                        isReviewMode
                            ? `${score || 0} / ${totalPoints} points`
                            : formatExamScore(assignment.score, totalPoints, isPendingReview, assignment.auto_score)
                    }
                />
                <TextEntry
                    label={trans('components.exam_info_section.percentage_label')}
                    value={`${percentage}%`}
                />
                <TextEntry
                    label={isReviewMode ? trans('components.exam_info_section.questions_label') : trans('components.exam_info_section.status_label')}
                    value={
                        isReviewMode
                            ? trans('components.exam_info_section.questions_count', { count: exam.questions?.length || 0 })
                            : isStudentView
                                ? (isPendingReview ? trans('components.exam_info_section.pending_correction') : trans('components.exam_info_section.finished'))
                                : getAssignmentBadgeLabel(assignment.status)
                    }
                />

                {assignment.forced_submission && (
                    <AlertEntry title={trans('components.exam_info_section.automatic_submission')} type="error" className="md:col-span-3">
                        <p className="text-sm">
                            {trans('components.exam_info_section.automatic_submission_message')}
                        </p>
                        <p className="text-sm">
                            {trans('components.exam_info_section.violation_detected_label', { violation: securityViolationLabel(assignment.security_violation) })}
                        </p>
                    </AlertEntry>
                )}

            </div>
        </>
    );
};

export default ExamInfoSection;