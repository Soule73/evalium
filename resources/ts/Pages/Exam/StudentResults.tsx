import React from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Exam, ExamAssignment, Answer, User, PageProps, Group } from '@/types';
import useExamResults from '@/hooks/exam/useExamResults';
import useExamScoring from '@/hooks/exam/useExamScoring';
import { route } from 'ziggy-js';
import { router, usePage } from '@inertiajs/react';
import { Badge, Button, ExamInfoSection, QuestionRenderer, Section } from '@/Components';
import { hasPermission } from '@/utils/permissions';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { trans } from '@/utils/translations';

interface Props {
    exam: Exam;
    group: Group;
    student: User;
    assignment: ExamAssignment;
    userAnswers: Record<number, Answer>;
    creator: User;
}

const ExamStudentResults: React.FC<Props> = ({ exam, group, student, assignment, userAnswers, creator }) => {
    const { auth } = usePage<PageProps>().props;
    const { isPendingReview, assignmentStatus, examIsActive } = useExamResults({ exam, assignment, userAnswers });
    const { totalPoints, finalPercentage, getQuestionResult } = useExamScoring({ exam, assignment, userAnswers });

    const canGradeExams = hasPermission(auth.permissions, 'correct exams');


    return (
        <AuthenticatedLayout title={trans('exam_pages.student_results.title', { student: student.name, exam: exam.title })}
            breadcrumb={breadcrumbs.examGroupSubmission(exam.id, group.id, exam.title, group.display_name, student.name)}
        >
            <Section
                title={trans('exam_pages.student_results.copy_title', { student: student.name })}
                subtitle={
                    <div className='flex items-center space-x-4'>
                        <span className={`px-3 py-1 rounded-full text-sm font-medium ${assignmentStatus.color}`}>
                            {assignmentStatus.label}
                        </span>
                        <div>
                            {examIsActive ? (
                                <Badge label={trans('exam_pages.student_results.exam_active')} type="success" />
                            ) : (
                                <Badge label={trans('exam_pages.student_results.exam_disabled')} type="gray" />
                            )}
                        </div>
                    </div>
                }
                actions={
                    <div className="flex space-x-2">
                        {canGradeExams && (
                            <Button
                                color="primary"
                                size="sm"
                                onClick={() => router.visit(route('exams.review', { exam: exam.id, group: group.id, student: student.id }))}
                            >
                                {isPendingReview ? trans('exam_pages.student_results.correct_exam') : trans('exam_pages.student_results.edit_correction')}
                            </Button>
                        )}
                        <Button
                            color="secondary"
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit(route('exams.groups', exam.id))}
                        >
                            {trans('exam_pages.student_results.back_to_assignments')}
                        </Button>
                    </div>
                }
            >
                <ExamInfoSection
                    exam={exam}
                    student={student}
                    assignment={assignment}
                    creator={creator}
                    totalPoints={totalPoints}
                    percentage={finalPercentage}
                    isPendingReview={isPendingReview}
                />
            </Section>

            <Section title={trans('exam_pages.student_results.answers_detail')}>
                <QuestionRenderer
                    questions={exam.questions || []}
                    getQuestionResult={getQuestionResult}
                    isTeacherView={true}
                    showCorrectAnswers={true}
                    assignment={assignment}
                />

                {/* Notes du professeur */}
                {assignment.teacher_notes && (
                    <div className="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <h3 className="text-lg font-medium text-green-800 mb-2">
                            {trans('exam_pages.student_results.teacher_notes')}
                        </h3>
                        <p className="text-green-700 whitespace-pre-wrap">{assignment.teacher_notes}</p>
                    </div>
                )}
            </Section>
        </AuthenticatedLayout>
    );
};

export default ExamStudentResults;