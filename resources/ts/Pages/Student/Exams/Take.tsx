import { Head } from '@inertiajs/react';
import { Button } from '@/Components';
import TextEntry from '@/Components/TextEntry';
import { Answer, Exam, ExamAssignment, Question } from '@/types';
import { ExclamationCircleIcon, QuestionMarkCircleIcon } from '@heroicons/react/24/outline';
import TakeQuestion from '@/Components/exam/TakeQuestion';
import useTakeExam from '@/hooks/exam/useTakeExam';
import AlertSecurityViolation, { CanNotTakeExam } from '@/Components/exam/AlertSecurityViolation';
import AlertEntry from '@/Components/AlertEntry';
import Section from '@/Components/Section';
import { formatTime } from '@/utils';
import ConfirmationModal from '@/Components/ConfirmationModal';
import FullscreenModal from '@/Components/exam/FullscreenModal';

interface TakeExamProps {
    exam: Exam;
    assignment: ExamAssignment;
    questions: Question[];
    userAnswers: Answer[];
}

export default function Take({ exam, assignment, questions = [], userAnswers = [] }: TakeExamProps) {

    const {
        answers,
        isSubmitting,
        showConfirmModal,
        setShowConfirmModal,
        timeLeft,
        security,
        processing,
        handleAnswerChange,
        handleSubmit,
        examTerminated,
        terminationReason,
        showFullscreenModal,
        enterFullscreen,
        examCanStart
    } = useTakeExam({ exam, questions, userAnswers });

    if (examTerminated) {
        return (
            <AlertSecurityViolation
                exam={exam}
                reason={terminationReason || "Violation de sécurité détectée"}
            />
        );
    }

    if (assignment.submitted_at) {
        return (
            <CanNotTakeExam
                title="Examen Terminé"
                message="Vous avez déjà terminé cet examen."
                icon={<ExclamationCircleIcon className="h-12 w-12 text-yellow-500 mx-auto mb-4" />}
            />
        );
    }

    if (
        !questions || questions.length === 0
    ) {
        return (
            <CanNotTakeExam
                title="Aucune question disponible"
                subtitle='Cet examen ne contient aucune question.'
                message="Veuillez contacter votre enseignant pour plus d'informations."
                icon={<ExclamationCircleIcon className="h-12 w-12 text-yellow-500 mx-auto mb-4" />}
            />
        );
    }


    return (
        <div className="bg-gray-50 min-h-screen">
            <Head title={`Examen - ${exam.title}`} />

            <div className="bg-white py-4 border-b border-gray-200 fixed w-full z-1 top-0">
                <div className="container mx-auto flex justify-between items-center">
                    <TextEntry
                        className=' text-start'
                        label={exam.title}
                        value={exam.description ? (exam.description.length > 100 ? exam.description.substring(0, 100) + '...' : exam.description) : ''}
                    />

                    <TextEntry
                        className=' text-center'
                        label="Temps restant"
                        value={formatTime(timeLeft)}
                    />

                    {!security.isFullscreen && <TextEntry
                        className=' text-center'
                        label={"Mode plein écran requis"}
                        value=""
                    />}
                    <Button
                        size="sm"
                        color="primary"

                        onClick={() => setShowConfirmModal(true)}
                        disabled={isSubmitting || processing}
                        loading={isSubmitting || processing}

                    >
                        {isSubmitting || processing ? 'Soumission...' : "Terminer l'examen"}
                    </Button>
                </div>
            </div>

            <div className="pt-20 max-w-6xl mx-auto">
                <div className="container mx-auto px-4 py-8">
                    <Section title="Instructions importantes" collapsible>
                        <AlertEntry type="warning" title="IMPORTANT">
                            <p>
                                Toute violation des règles de sécurité (changement d'onglet,
                                sortie du mode plein écran) terminera
                                <strong> IMMÉDIATEMENT</strong> votre examen.
                            </p>
                            <p>
                                Vos réponses seront automatiquement sauvegardées.
                                Aucun avertissement ne sera donné.
                            </p>
                        </AlertEntry>
                    </Section>
                    {examCanStart && questions.length > 0 && (
                        questions.map((currentQ) => (
                            <TakeQuestion
                                key={currentQ.id}
                                question={currentQ}
                                answers={answers}
                                onAnswerChange={handleAnswerChange}
                            />
                        ))
                    )}

                    {!examCanStart && (
                        <Section title="Activation du mode plein écran requise" collapsible={false}>
                            <AlertEntry type="info" title="ATTENTION">
                                <p>
                                    Pour commencer cet examen, vous devez d'abord activer le mode plein écran.
                                    Les questions ne s'afficheront qu'après l'activation du mode plein écran.
                                </p>
                            </AlertEntry>
                        </Section>
                    )}
                </div>
            </div>

            <ConfirmationModal
                title="Confirmer la soumission"
                message="Êtes-vous sûr de vouloir terminer cet examen ? Cette action est irréversible."
                icon={QuestionMarkCircleIcon}
                type='info'
                isOpen={showConfirmModal}
                onClose={() => setShowConfirmModal(false)}
                onConfirm={handleSubmit}
                loading={isSubmitting || processing}
            >
                <p className="text-gray-600 mb-6 text-center ">
                    Assurez-vous d'avoir répondu à toutes les questions avant de confirmer.
                </p>
            </ConfirmationModal>

            <FullscreenModal
                isOpen={
                    showFullscreenModal
                }
                onEnterFullscreen={enterFullscreen}
            />
        </div>
    );
}


