import React from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Exam, ExamAssignment, Answer, User, Question, PageProps, Group } from '@/types';
import { requiresManualGrading, getCorrectionStatus } from '@/utils/examUtils';
import { route } from 'ziggy-js';
import { router, usePage } from '@inertiajs/react';
import { AlertEntry, Badge, Button, ExamInfoSection, Modal, QuestionRenderer, Section, Textarea, TextEntry } from '@/Components';
import { useExamStudentReview } from '@/hooks';
import { hasPermission } from '@/utils/permissions';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { trans } from '@/utils/translations';

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

    const renderScoreInput = (question: Question) => {
        const questionScore = scores[question.id] || 0;
        const maxScore = question.points || 0;
        const isAutoGraded = !requiresManualGrading(question);

        return (
            <div className="mt-4 p-4 bg-gray-50 rounded-lg space-y-4">
                <div className="flex items-center justify-between">
                    <div>
                        <label className="text-sm font-medium text-gray-700">
                            {trans('exam_pages.student_review.question_score_label', { max: maxScore })}
                        </label>
                        {isAutoGraded && (
                            <p className="text-xs text-blue-600 mt-1">
                                {trans('exam_pages.student_review.auto_graded_info')}
                            </p>
                        )}
                        {!isAutoGraded && (
                            <p className="text-xs text-orange-600 mt-1">
                                {trans('exam_pages.student_review.manual_grading_required')}
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
                        {trans('exam_pages.student_review.question_comment_label')}
                    </label>
                    <Textarea
                        value={feedbacks[question.id] || ''}
                        onChange={(e) => handleFeedbackChange(question.id, e.target.value)}
                        placeholder={trans('exam_pages.student_review.question_comment_placeholder')}
                        className="w-full"
                        rows={3}
                    />
                </div>
            </div>
        );
    }; return (
        <AuthenticatedLayout title={trans('exam_pages.student_review.title', { student: student.name, exam: exam.title })}
            breadcrumb={breadcrumbs.examGroupReview(exam.id, group.id, student.id, exam.title, group.display_name, student.name)}

        >
            <Section
                title={trans('exam_pages.student_review.correction_title', { student: student.name })}
                subtitle={
                    <div className='flex items-center space-x-4'>
                        <span className={`px-3 py-1 rounded-full text-sm font-medium ${assignmentStatus.color}`}>
                            {assignmentStatus.label}
                        </span>
                        <Badge label={trans('exam_pages.student_review.correction_mode')} type="warning" />
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
                                {isSubmitting ? trans('exam_pages.student_review.saving') : trans('exam_pages.student_review.save_grades')}
                            </Button>
                        )}
                        <Button
                            color="secondary"
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit(route('exams.groups', exam.id))}
                        >
                            {trans('exam_pages.student_review.back_to_assignments')}
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
                        {trans('exam_pages.student_review.auto_corrected_info')}
                    </p>
                </AlertEntry>
            </Section>

            <Section title={trans('exam_pages.student_review.questions_correction')}>
                <QuestionRenderer
                    questions={exam.questions || []}
                    getQuestionResult={getQuestionResult}
                    scores={scores}
                    isTeacherView={true}
                    renderScoreInput={renderScoreInput}
                />

                {/* Résumé de la correction */}
                <div className="mt-8 p-6 bg-blue-50 rounded-lg">
                    <h3 className="text-lg font-medium text-blue-900 mb-4">{trans('exam_pages.student_review.correction_summary')}</h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <TextEntry
                            labelClass=' !text-blue-900'
                            valueClass='!text-blue-600'
                            label={trans('exam_pages.student_review.total_score')}
                            value={`${calculatedTotalScore} / ${totalPoints}`} />
                        <TextEntry
                            labelClass=' !text-blue-900'
                            valueClass='!text-blue-600'
                            label={trans('exam_pages.student_review.percentage')}
                            value={`${percentage}%`} />
                        <TextEntry
                            labelClass=' !text-blue-900'
                            valueClass='!text-blue-600'
                            label={trans('exam_pages.student_review.status')}
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
                            {trans('exam_pages.student_review.confirm_save_title')}
                        </h3>
                        <p className="text-sm text-gray-600">
                            {trans('exam_pages.student_review.confirm_save_message', { student: student.name })}
                        </p>
                    </div>

                    <div className="bg-gray-50 p-4 rounded-lg">
                        <div className="text-sm text-gray-700">
                            <strong>{trans('exam_pages.student_review.total_score')} :</strong> {calculatedTotalScore} / {totalPoints} ({percentage}%)
                        </div>
                    </div>

                    <Textarea
                        label={trans('exam_pages.student_review.teacher_notes_label')}
                        placeholder={trans('exam_pages.student_review.teacher_notes_placeholder')}
                        value={teacherNotes}
                        onChange={(e) => setTeacherNotes(e.target.value)}
                        rows={4}
                        helperText={trans('exam_pages.student_review.teacher_notes_help')}
                    />

                    <div className="flex justify-end space-x-3">
                        <Button
                            color="secondary"
                            variant="outline"
                            size="sm"
                            onClick={handleCancelSubmit}
                            disabled={isSubmitting}
                        >
                            {trans('exam_pages.student_review.cancel')}
                        </Button>
                        <Button
                            color="primary"
                            size="sm"
                            onClick={handleConfirmSubmit}
                            disabled={isSubmitting}
                        >
                            {isSubmitting ? trans('exam_pages.student_review.saving') : trans('exam_pages.student_review.confirm_save')}
                        </Button>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
};

export default ExamStudentReview;