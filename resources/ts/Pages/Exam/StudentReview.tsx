import React from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Exam, ExamAssignment, Answer, User, Question, PageProps, Group } from '@/types';
import { requiresManualGrading, getCorrectionStatus } from '@/utils';
import { route } from 'ziggy-js';
import { router, usePage } from '@inertiajs/react';
import { AlertEntry, Badge, Button, ExamInfoSection, Modal, QuestionRenderer, Section, Textarea, TextEntry } from '@/Components';
import { useExamStudentReview } from '@/hooks';
import { hasPermission } from '@/utils';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';

interface Props {
    exam: Exam;
    group: Group;
    student: User;
    assignment: ExamAssignment;
    userAnswers: Record<number, Answer>;
}

const ExamStudentReview: React.FC<Props> = ({ exam, student, assignment, userAnswers, group }) => {
    const { auth } = usePage<PageProps>().props;
    const canGradeExams = hasPermission(auth.permissions, 'correct exams');

    const {
        assignmentStatus,
        totalPoints,
        getQuestionResult,
        scores,
        calculatedTotalScore,
        percentage,
        handleScoreChange,
        isSubmitting,
        showConfirmModal,
        teacherNotes,
        setTeacherNotes,
        feedbacks,
        handleFeedbackChange,
        handleSubmit,
        handleConfirmSubmit,
        handleCancelSubmit
    } = useExamStudentReview({ exam, group, assignment, userAnswers, student });

    const translations = {
        title: trans('exam_pages.student_review.title', { student: student.name, exam: exam.title }),
        correctionTitle: trans('exam_pages.student_review.correction_title', { student: student.name }),
        correctionMode: trans('exam_pages.student_review.correction_mode'),
        saving: trans('exam_pages.student_review.saving'),
        saveGrades: trans('exam_pages.student_review.save_grades'),
        backToAssignments: trans('exam_pages.student_review.back_to_assignments'),
        autoCorrectedInfo: trans('exam_pages.student_review.auto_corrected_info'),
        questionsCorrection: trans('exam_pages.student_review.questions_correction'),
        correctionSummary: trans('exam_pages.student_review.correction_summary'),
        totalScore: trans('exam_pages.student_review.total_score'),
        percentage: trans('exam_pages.student_review.percentage'),
        status: trans('exam_pages.student_review.status'),
        confirmSaveTitle: trans('exam_pages.student_review.confirm_save_title'),
        confirmSaveMessage: trans('exam_pages.student_review.confirm_save_message', { student: student.name }),
        teacherNotesLabel: trans('exam_pages.student_review.teacher_notes_label'),
        teacherNotesPlaceholder: trans('exam_pages.student_review.teacher_notes_placeholder'),
        teacherNotesHelp: trans('exam_pages.student_review.teacher_notes_help'),
        cancel: trans('exam_pages.student_review.cancel'),
        confirmSave: trans('exam_pages.student_review.confirm_save'),
        questionScoreLabel: (max: number) => trans('exam_pages.student_review.question_score_label', { max }),
        autoGradedInfo: trans('exam_pages.student_review.auto_graded_info'),
        manualGradingRequired: trans('exam_pages.student_review.manual_grading_required'),
        questionCommentLabel: trans('exam_pages.student_review.question_comment_label'),
        questionCommentPlaceholder: trans('exam_pages.student_review.question_comment_placeholder'),
    };

    const renderScoreInput = (question: Question) => {
        const questionScore = scores[question.id] || 0;
        const maxScore = question.points || 0;
        const isAutoGraded = !requiresManualGrading(question);

        return (
            <div className="mt-4 p-4 bg-gray-50 rounded-lg space-y-4">
                <div className="flex items-center justify-between">
                    <div>
                        <label className="text-sm font-medium text-gray-700">
                            {translations.questionScoreLabel(maxScore)}
                        </label>
                        {isAutoGraded && (
                            <p className="text-xs text-blue-600 mt-1">
                                {translations.autoGradedInfo}
                            </p>
                        )}
                        {!isAutoGraded && (
                            <p className="text-xs text-orange-600 mt-1">
                                {translations.manualGradingRequired}
                            </p>
                        )}
                    </div>
                    <div className="flex items-center space-x-2">
                        <input
                            type="number"
                            min="0"
                            max={maxScore}
                            step="0.5"
                            value={questionScore}
                            onChange={(e) => handleScoreChange(question.id, parseFloat(e.target.value) || 0, maxScore)}
                            className="w-20 px-2 py-1 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
                        />
                        <span className="text-sm text-gray-500">/ {maxScore}</span>
                    </div>
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                        {translations.questionCommentLabel}
                    </label>
                    <Textarea
                        value={feedbacks[question.id] || ''}
                        onChange={(e) => handleFeedbackChange(question.id, e.target.value)}
                        placeholder={translations.questionCommentPlaceholder}
                        className="w-full"
                        rows={3}
                    />
                </div>
            </div>
        );
    };

    return (
        <AuthenticatedLayout title={translations.title}
            breadcrumb={breadcrumbs.examGroupReview(exam.id, group.id, student.id, exam.title, group.display_name, student.name)}

        >
            <Section
                title={translations.correctionTitle}
                subtitle={
                    <div className='flex items-center space-x-4'>
                        <span className={`px-3 py-1 rounded-full text-sm font-medium ${assignmentStatus.color}`}>
                            {assignmentStatus.label}
                        </span>
                        <Badge label={translations.correctionMode} type="warning" />
                    </div>
                }
                actions={
                    <div className="flex space-x-2">
                        {canGradeExams && (
                            <Button
                                color="primary"
                                size="sm"
                                onClick={handleSubmit}
                                disabled={isSubmitting}
                            >
                                {isSubmitting ? translations.saving : translations.saveGrades}
                            </Button>
                        )}
                        <Button
                            color="secondary"
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit(route('exams.groups', exam.id))}
                        >
                            {translations.backToAssignments}
                        </Button>
                    </div>
                }
            >
                <ExamInfoSection
                    exam={exam}
                    student={student}
                    assignment={assignment}
                    score={calculatedTotalScore}
                    totalPoints={totalPoints}
                    percentage={percentage}
                    isReviewMode={true}
                />

                <AlertEntry title="Note" type="info">
                    <p className="text-sm">
                        {translations.autoCorrectedInfo}
                    </p>
                </AlertEntry>
            </Section>

            <Section title={translations.questionsCorrection}>
                <QuestionRenderer
                    questions={exam.questions || []}
                    getQuestionResult={getQuestionResult}
                    scores={scores}
                    isTeacherView={true}
                    renderScoreInput={renderScoreInput}
                />

                <div className="mt-8 p-6 bg-blue-50 rounded-lg">
                    <h3 className="text-lg font-medium text-blue-900 mb-4">{translations.correctionSummary}</h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <TextEntry
                            labelClass=' !text-blue-900'
                            valueClass='!text-blue-600'
                            label={translations.totalScore}
                            value={`${calculatedTotalScore} / ${totalPoints}`} />
                        <TextEntry
                            labelClass=' !text-blue-900'
                            valueClass='!text-blue-600'
                            label={translations.percentage}
                            value={`${percentage}%`} />
                        <TextEntry
                            labelClass=' !text-blue-900'
                            valueClass='!text-blue-600'
                            label={translations.status}
                            value={getCorrectionStatus(calculatedTotalScore)} />
                    </div>
                </div>
            </Section>

            <Modal
                isOpen={showConfirmModal}
                onClose={handleCancelSubmit}
                size="lg"
                isCloseableInside={false}
            >
                <div className="space-y-6">
                    <div>
                        <h3 className="text-lg font-medium text-gray-900 mb-2">
                            {translations.confirmSaveTitle}
                        </h3>
                        <p className="text-sm text-gray-600">
                            {translations.confirmSaveMessage}
                        </p>
                    </div>

                    <div className="bg-gray-50 p-4 rounded-lg">
                        <div className="text-sm text-gray-700">
                            <strong>{translations.totalScore} :</strong> {calculatedTotalScore} / {totalPoints} ({percentage}%)
                        </div>
                    </div>

                    <Textarea
                        label={translations.teacherNotesLabel}
                        placeholder={translations.teacherNotesPlaceholder}
                        value={teacherNotes}
                        onChange={(e) => setTeacherNotes(e.target.value)}
                        rows={4}
                        helperText={translations.teacherNotesHelp}
                    />

                    <div className="flex justify-end space-x-3">
                        <Button
                            color="secondary"
                            variant="outline"
                            size="sm"
                            onClick={handleCancelSubmit}
                            disabled={isSubmitting}
                        >
                            {translations.cancel}
                        </Button>
                        <Button
                            color="primary"
                            size="sm"
                            onClick={handleConfirmSubmit}
                            disabled={isSubmitting}
                        >
                            {isSubmitting ? translations.saving : translations.confirmSave}
                        </Button>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
};

export default ExamStudentReview;