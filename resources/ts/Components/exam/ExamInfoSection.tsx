import React from 'react';
import { formatDate, formatDuration, getAssignmentBadgeLabel, securityViolationLabel } from '@/utils/formatters';
import TextEntry from '@/Components/TextEntry';
import { Exam, ExamAssignment, User } from '@/types';
import { formatExamScore } from '@/utils/examUtils';
import AlertEntry from '../AlertEntry';

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
                        <TextEntry label="Examen" value={exam.title} />
                        <TextEntry label="Description" value={exam.description ?? ''} />
                    </>
                )}
                {creator && (
                    <TextEntry label="Professeur(e)/Créateur(trice)" value={creator.name} />
                )}
            </div>

            <div className={`grid grid-cols-1 gap-4 ${student ? 'md:grid-cols-4' : 'md:grid-cols-2'}`}>
                {student && (
                    <>
                        <TextEntry label="Étudiant" value={student.name} />
                        <TextEntry label="Email" value={student.email} />
                    </>
                )}
                <TextEntry
                    label="Soumis le"
                    value={assignment?.submitted_at ? formatDate(assignment.submitted_at, 'datetime') : '-'}
                />
                <TextEntry label="Durée" value={formatDuration(exam?.duration)} />
            </div>

            <div className="grid grid-cols-1 gap-4 md:grid-cols-3 border-t border-gray-300 pt-4 pb-4 mt-4">
                <TextEntry
                    label={
                        isReviewMode
                            ? "Note attribuée"
                            : isPendingReview
                                ? "Note (en attente)"
                                : isStudentView
                                    ? "Note"
                                    : "Note finale"
                    }
                    value={
                        isReviewMode
                            ? `${score || 0} / ${totalPoints} points`
                            : formatExamScore(assignment.score, totalPoints, isPendingReview, assignment.auto_score)
                    }
                />
                <TextEntry
                    label="Pourcentage"
                    value={`${percentage}%`}
                />
                <TextEntry
                    label={isReviewMode ? "Questions" : "Statut"}
                    value={
                        isReviewMode
                            ? `${exam.questions?.length || 0} questions`
                            : isStudentView
                                ? (isPendingReview ? "En attente de correction" : "Terminé")
                                : getAssignmentBadgeLabel(assignment.status)
                    }
                />

                {assignment.forced_submission && (
                    <AlertEntry title="Soumission Automatique" type="error" className="md:col-span-3">
                        <p className="text-sm">
                            Cet examen a été soumis automatiquement
                        </p>
                        <p className="text-sm">
                            Violation détectée : {securityViolationLabel(assignment.security_violation)}
                        </p>
                    </AlertEntry>
                )}

            </div>
        </>
    );
};

export default ExamInfoSection;