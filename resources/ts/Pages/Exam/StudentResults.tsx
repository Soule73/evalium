import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Section from '@/Components/Section';
import Badge from '@/Components/Badge';
import { Exam, ExamAssignment, Answer, User } from '@/types';
import useExamResults from '@/hooks/exam/useExamResults';
import useExamScoring from '@/hooks/exam/useExamScoring';
import ExamInfoSection from '@/Components/exam/ExamInfoSection';
import QuestionRenderer from '@/Components/exam/QuestionRenderer';
import { route } from 'ziggy-js';
import { router } from '@inertiajs/react';
import { Button } from '@/Components';

interface Props {
    exam: Exam;
    student: User;
    assignment: ExamAssignment;
    userAnswers: Record<number, Answer>;
    creator: User;
}

const ExamStudentResults: React.FC<Props> = ({ exam, student, assignment, userAnswers, creator }) => {
    const { isPendingReview, assignmentStatus, examIsActive } = useExamResults({ exam, assignment, userAnswers });
    const { totalPoints, finalPercentage, getQuestionResult } = useExamScoring({ exam, assignment, userAnswers });


    return (
        <AuthenticatedLayout title={`Résultats - ${student.name} - ${exam.title}`}>
            <Section
                title={`Résultats de ${student.name}`}
                subtitle={
                    <div className='flex items-center space-x-4'>
                        <span className={`px-3 py-1 rounded-full text-sm font-medium ${assignmentStatus.color}`}>
                            {assignmentStatus.label}
                        </span>
                        <div>
                            {examIsActive ? (
                                <Badge label="Examen actif" type="success" />
                            ) : (
                                <Badge label="Examen désactivé" type="gray" />
                            )}
                        </div>
                    </div>
                }
                actions={
                    <div className="flex space-x-2">
                        {/* {isPendingReview && ( */}
                        <Button
                            color="primary"
                            size="sm"
                            onClick={() => router.visit(route('exams.review', { exam: exam.id, student: student.id }))}
                        >
                            {isPendingReview ? "Corriger l'examen" : "Modifier la correction"}
                        </Button>
                        {/* )} */}
                        <Button
                            color="secondary"
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit(route('exams.assignments', exam.id))}
                        >
                            Retour aux assignations
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

            <Section title="Détail des réponses">
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
                            Notes
                        </h3>
                        <p className="text-green-700 whitespace-pre-wrap">{assignment.teacher_notes}</p>
                    </div>
                )}
            </Section>
        </AuthenticatedLayout>
    );
};

export default ExamStudentResults;