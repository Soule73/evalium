import React from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Exam, ExamAssignment, Answer, User, Level, Group } from '@/types';
import useExamResults from '@/hooks/exam/useExamResults';
import useExamScoring from '@/hooks/exam/useExamScoring';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { route } from 'ziggy-js';
import { router } from '@inertiajs/react';
import { Badge, Button, ExamInfoSection, QuestionRenderer, Section } from '@/Components';
import { trans } from '@/utils/translations';

interface Props {
    exam: Exam;
    assignment: ExamAssignment;
    userAnswers: Record<number, Answer>;
    creator: User;
    group?: Group & { level: Level };
}

const ExamResults: React.FC<Props> = ({ exam, assignment, userAnswers, creator, group }) => {
    const { isPendingReview, assignmentStatus, showCorrectAnswers, examIsActive } = useExamResults({ exam, assignment, userAnswers });
    const { totalPoints, finalPercentage, getQuestionResult } = useExamScoring({ exam, assignment, userAnswers });


    return (
        <AuthenticatedLayout
            title={trans('student_pages.results.title', { exam: exam.title })}
            breadcrumb={group ? breadcrumbs.studentExamShow(group.level.name, group.id, exam.title) : breadcrumbs.studentExams()}
        >
            <Section
                title={trans('student_pages.results.section_title')}
                subtitle={
                    <div className='flex items-center space-x-4'>
                        <span className={`px-3 py-1 rounded-full text-sm font-medium ${assignmentStatus.color}`}>
                            {assignmentStatus.label}
                        </span>
                        <div>
                            {examIsActive ? (
                                <Badge label={trans('student_pages.results.exam_active')} type="success" />
                            ) : (
                                <Badge label={trans('student_pages.results.exam_disabled')} type="error" />
                            )}
                        </div>
                    </div>
                }
                actions={
                    <Button
                        color="secondary"
                        variant="outline"
                        size="sm"
                        className='w-max'
                        onClick={() => router.visit(route('student.exams.index'))}
                    >
                        {trans('student_pages.results.back_to_exams')}
                    </Button>
                }
            >
                <ExamInfoSection
                    exam={exam}
                    assignment={assignment}
                    creator={creator}
                    totalPoints={totalPoints}
                    percentage={finalPercentage}
                    isPendingReview={isPendingReview}
                    isStudentView={true}
                />
            </Section>

            <Section title={trans('student_pages.results.answers_detail')}>
                {assignment.teacher_notes && (
                    <div className="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <h3 className="text-lg font-medium text-green-800 mb-2">
                            {trans('student_pages.results.teacher_comments')}
                        </h3>
                        <p className="text-green-700 whitespace-pre-wrap">{assignment.teacher_notes}</p>
                    </div>
                )}
                <QuestionRenderer
                    questions={exam.questions || []}
                    getQuestionResult={getQuestionResult}
                    isTeacherView={false}
                    showCorrectAnswers={showCorrectAnswers}
                    assignment={assignment}
                />
            </Section>
        </AuthenticatedLayout>
    );
};



export default ExamResults;