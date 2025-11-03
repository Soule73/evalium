import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Section from '@/Components/Section';
import AlertEntry from '@/Components/AlertEntry';
import Badge from '@/Components/Badge';
import Modal from '@/Components/Modal';
import Textarea from '@/Components/Textarea';
import { Exam, ExamAssignment, Answer, User, Question, PageProps, Group } from '@/types';
import ExamInfoSection from '@/Components/exam/ExamInfoSection';
import QuestionRenderer from '@/Components/exam/QuestionRenderer';
import { requiresManualGrading, getCorrectionStatus } from '@/utils/examUtils';
import { route } from 'ziggy-js';
import { router, usePage } from '@inertiajs/react';
import { Button } from '@/Components';
import TextEntry from '@/Components/TextEntry';
import useExamStudentReview from '@/hooks/exam/useExamStudentReview';
import { hasPermission } from '@/utils/permissions';
import { breadcrumbs } from '@/utils/breadcrumbs';

interface Props {
    exam: Exam;
    group: Group;
    student: User;
    assignment: ExamAssignment;
    userAnswers: Record<number, Answer>;
}

const ExamStudentReview: React.FC<Props> = ({ exam, student, assignment, userAnswers, group }) => {
    const { auth } = usePage<PageProps>().props;
    const canGradeExams = hasPermission(auth.permissions, 'grade exams');

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
                            Note pour cette question (max: {maxScore} points)
                        </label>
                        {isAutoGraded && (
                            <p className="text-xs text-blue-600 mt-1">
                                Note calculé automatiquement - modifiable si nécessaire
                            </p>
                        )}
                        {!isAutoGraded && (
                            <p className="text-xs text-orange-600 mt-1">
                                Correction manuelle requise
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
                        Commentaire pour cette question:
                    </label>
                    <Textarea
                        value={feedbacks[question.id] || ''}
                        onChange={(e) => handleFeedbackChange(question.id, e.target.value)}
                        placeholder="Ajoutez vos commentaires pour cette réponse..."
                        className="w-full"
                        rows={3}
                    />
                </div>
            </div>
        );
    }; return (
        <AuthenticatedLayout title={`Correction - ${student.name} - ${exam.title}`}
            breadcrumb={breadcrumbs.examGroupReview(exam.id, group.id, student.id, exam.title, group.display_name, student.name)}

        >
            <Section
                title={`Correction de l'examen de ${student.name}`}
                subtitle={
                    <div className='flex items-center space-x-4'>
                        <span className={`px-3 py-1 rounded-full text-sm font-medium ${assignmentStatus.color}`}>
                            {assignmentStatus.label}
                        </span>
                        <Badge label="Mode correction" type="warning" />
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
                                {isSubmitting ? 'Sauvegarde...' : 'Sauvegarder les notes'}
                            </Button>
                        )}
                        <Button
                            color="secondary"
                            variant="outline"
                            size="sm"
                            onClick={() => router.visit(route('exams.groups', exam.id))}
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
                    score={calculatedTotalScore}
                    totalPoints={totalPoints}
                    percentage={percentage}
                    isReviewMode={true}
                />

                <AlertEntry title="Note" type="info">
                    <p className="text-sm">
                        Les QCM sont
                        <strong> automatiquement corrigées</strong> mais vous pouvez ajuster les notes
                        si nécessaire.
                    </p>
                </AlertEntry>
            </Section>

            <Section title="Questions et correction">
                <QuestionRenderer
                    questions={exam.questions || []}
                    getQuestionResult={getQuestionResult}
                    scores={scores}
                    isTeacherView={true}
                    renderScoreInput={renderScoreInput}
                />

                {/* Résumé de la correction */}
                <div className="mt-8 p-6 bg-blue-50 rounded-lg">
                    <h3 className="text-lg font-medium text-blue-900 mb-4">Résumé de la correction</h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <TextEntry
                            labelClass=' !text-blue-900'
                            valueClass='!text-blue-600'
                            label="Note total"
                            value={`${calculatedTotalScore} / ${totalPoints}`} />
                        <TextEntry
                            labelClass=' !text-blue-900'
                            valueClass='!text-blue-600'
                            label="Pourcentage"
                            value={`${percentage}%`} />
                        <TextEntry
                            labelClass=' !text-blue-900'
                            valueClass='!text-blue-600'
                            label="Statut"
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
                            Confirmer la sauvegarde des notes
                        </h3>
                        <p className="text-sm text-gray-600">
                            Vous êtes sur le point de sauvegarder les notes pour l'examen de <strong>{student.name}</strong>.
                        </p>
                    </div>

                    <div className="bg-gray-50 p-4 rounded-lg">
                        <div className="text-sm text-gray-700">
                            <strong>Note total :</strong> {calculatedTotalScore} / {totalPoints} ({percentage}%)
                        </div>
                    </div>

                    <Textarea
                        label="Notes du professeur (optionnel)"
                        placeholder="Ajoutez vos commentaires ou observations sur cette copie..."
                        value={teacherNotes}
                        onChange={(e) => setTeacherNotes(e.target.value)}
                        rows={4}
                        helperText="Ces notes seront visibles par l'étudiant avec ses résultats."
                    />

                    <div className="flex justify-end space-x-3">
                        <Button
                            color="secondary"
                            variant="outline"
                            size="sm"
                            onClick={handleCancelSubmit}
                            disabled={isSubmitting}
                        >
                            Annuler
                        </Button>
                        <Button
                            color="primary"
                            size="sm"
                            onClick={handleConfirmSubmit}
                            disabled={isSubmitting}
                        >
                            {isSubmitting ? 'Sauvegarde...' : 'Confirmer et sauvegarder'}
                        </Button>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
};

export default ExamStudentReview;