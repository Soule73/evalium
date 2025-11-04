import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Section from '@/Components/Section';
import Badge from '@/Components/Badge';
import { Exam, ExamAssignment, Answer, User, Level, Group } from '@/types';
import useExamResults from '@/hooks/exam/useExamResults';
import useExamScoring from '@/hooks/exam/useExamScoring';
import ExamInfoSection from '@/Components/exam/ExamInfoSection';
import QuestionRenderer from '@/Components/exam/QuestionRenderer';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { route } from 'ziggy-js';
import { router } from '@inertiajs/react';
import { Button } from '@/Components';

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
            title={`Résultats - ${exam.title}`}
            breadcrumb={group ? breadcrumbs.studentExamShow(group.level.name, group.id, exam.title) : breadcrumbs.studentExams()}
        >
            <Section
                title="Résultats de l'examen"
                subtitle={
                    <div className='flex items-center space-x-4'>
                        <span className={`px-3 py-1 rounded-full text-sm font-medium ${assignmentStatus.color}`}>
                            {assignmentStatus.label}
                        </span>
                        <div>
                            {examIsActive ? (
                                <Badge label="Examen actif" type="success" />
                            ) : (
                                <Badge label="Examen désactivé" type="error" />
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
                        Retour aux examens
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

            <Section title="Détail des réponses">
                {assignment.teacher_notes && (
                    <div className="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <h3 className="text-lg font-medium text-green-800 mb-2">
                            Commentaires du professeur
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