import React, { useMemo } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Exam, ExamAssignment, Answer, User, Level, Group } from '@/types';
import { useExamResults, useExamScoring } from '@/hooks';
import { breadcrumbs } from '@/utils';
import { route } from 'ziggy-js';
import { router } from '@inertiajs/react';
import { Badge, Button, ExamInfoSection, QuestionRenderer, Section } from '@/Components';
import { trans } from '@/utils';

interface Props {
    exam: Exam;
    assignment: ExamAssignment;
    userAnswers: Record<number, Answer>;
    creator: User;
    group?: Group & { level: Level };
}

const ExamResults: React.FC<Props> = ({ exam, assignment, userAnswers, creator, group }) => {
    const { isPendingReview, assignmentStatus, showCorrectAnswers, examIsActive, totalPoints, getQuestionResult } = useExamResults({ exam, assignment, userAnswers });
    const { finalPercentage } = useExamScoring({
        exam,
        assignment,
        userAnswers,
        totalPoints,
        getQuestionResult
    });

    const translations = useMemo(() => ({
        title: trans('student_pages.results.title', { exam: exam.title }),
        sectionTitle: trans('student_pages.results.section_title'),
        examActive: trans('student_pages.results.exam_active'),
        examDisabled: trans('student_pages.results.exam_disabled'),
        backToExams: trans('student_pages.results.back_to_exams'),
        answersDetail: trans('student_pages.results.answers_detail'),
        teacherComments: trans('student_pages.results.teacher_comments'),
    }), [exam.title]);


    return (
        <AuthenticatedLayout
            title={translations.title}
            breadcrumb={group ? breadcrumbs.studentExamShow(group.level.name, group.id, exam.title) : breadcrumbs.studentExams()}
        >
            <Section
                title={translations.sectionTitle}
                subtitle={
                    <div className='flex items-center space-x-4'>
                        <span className={`px-3 py-1 rounded-full text-sm font-medium ${assignmentStatus.color}`}>
                            {assignmentStatus.label}
                        </span>
                        <div>
                            {examIsActive ? (
                                <Badge label={translations.examActive} type="success" />
                            ) : (
                                <Badge label={translations.examDisabled} type="error" />
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
                        {translations.backToExams}
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

            <Section title={translations.answersDetail}>
                {assignment.teacher_notes && (
                    <div className="mt-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                        <h3 className="text-lg font-medium text-gray-800 mb-2">
                            {translations.teacherComments}
                        </h3>
                        <p className="text-gray-700 whitespace-pre-wrap">{assignment.teacher_notes}</p>
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