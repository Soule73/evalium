import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, Exam, ExamAssignment, User } from '@/types';
import { formatDate, formatDuration, formatDeadlineWarning } from '@/utils/formatters';
import { Button } from '@/Components';
import { route } from 'ziggy-js';
import { ClockIcon, DocumentTextIcon, QuestionMarkCircleIcon } from '@heroicons/react/24/outline';
import AlertEntry from '@/Components/AlertEntry';
import Section from '@/Components/Section';
import { useState } from 'react';
import TextEntry from '@/Components/TextEntry';
import Modal from '@/Components/Modal';
import StatCard from '@/Components/StatCard';
import { ExamHeader } from '@/Components/exam';

interface StudentExamShowProps extends PageProps {
    exam: Exam;
    assignment?: ExamAssignment;
    canTake: boolean;
    questionsCount?: number;
    creator: User;
}

export default function StudentExamShow({ exam, assignment, canTake, questionsCount, creator }: StudentExamShowProps) {

    const deadlineWarning = exam.end_time
        ? formatDeadlineWarning(
            exam.end_time
        )
        : null;

    const [isModalOpen, setIsModalOpen] = useState(false);

    return (
        <AuthenticatedLayout title={exam.title}>
            <Modal size='xl' isOpen={isModalOpen} onClose={() => setIsModalOpen(false)}>
                <div className=' flex flex-col justify-between'>
                    <div className='mx-auto my-4 flex flex-col items-center'>

                        <QuestionMarkCircleIcon className="w-12 h-12 mb-3 text-yellow-500 mx-auto" />
                        <h2 className="text-lg font-semibold mb-2">Commencer l'examen</h2>
                        <p>Êtes-vous prêt à commencer l'examen ?</p>
                    </div>
                    {AlertMessage}
                    <div className="mt-4 flex justify-end space-x-2">
                        <Button size='sm' variant='outline' color="primary" onClick={() => {
                            setIsModalOpen(false);
                            router.visit(route('student.exams.take', exam.id));
                        }}>
                            Oui, commencer l'examen
                        </Button>
                    </div>
                </div>
            </Modal>

            <Section title="Détails de l'examen"
                actions={
                    <div className="flex items-center space-x-4">
                        <Button
                            color="secondary"
                            variant="outline"
                            size="sm"
                            className=' w-max'
                            onClick={() => router.visit(route('student.exams.index'))}
                        >
                            Retour aux examens
                        </Button>

                        {canTake && (
                            <Button
                                color="primary"
                                size="sm"
                                onClick={() => setIsModalOpen(true)}
                            >
                                {
                                    assignment?.started_at
                                        ? "Continuer l'examen"
                                        : "Commencer l'examen"
                                }
                            </Button>
                        )
                        }
                    </div >
                }
            >
                <div className="flex items-start justify-between mb-6">
                    <div className=' space-y-3 '>
                        <ExamHeader exam={exam} showDescription={true} showMetadata={false} />

                        <TextEntry label="Professeur(e)/Créateur(trice)" value={creator?.name} />
                    </div>
                    {deadlineWarning && (
                        <div className={`px-4 py-2 rounded-lg ${deadlineWarning.urgency === 'high'
                            ? 'bg-red-100 border border-red-200'
                            : deadlineWarning.urgency === 'medium'
                                ? 'bg-yellow-100 border border-yellow-200'
                                : 'bg-green-100 border border-green-200'
                            }`}>
                            <p className={`text-sm font-medium ${deadlineWarning.urgency === 'high'
                                ? 'text-red-800'
                                : deadlineWarning.urgency === 'medium'
                                    ? 'text-yellow-800'
                                    : 'text-green-800'
                                }`}>
                                {deadlineWarning.text}
                            </p>
                        </div>
                    )}
                </div>

                <div className="grid gap-y-2 grid-cols-1 lg:grid-cols-3 mb-8">
                    <StatCard
                        title="Durée"
                        value={formatDuration(exam.duration)}
                        icon={ClockIcon}
                        color="blue"
                        className=' lg:rounded-r-none! '
                    />
                    <StatCard
                        title="Questions"
                        value={questionsCount || 0}
                        icon={DocumentTextIcon}
                        color="green"
                        className=' lg:rounded-none! lg:border-x-0! '
                    />

                    <StatCard
                        title="Statut"
                        value={
                            assignment?.submitted_at
                                ? 'Terminé' :
                                assignment?.started_at ? 'En cours' :
                                    'Non commencé'}
                        icon={QuestionMarkCircleIcon}
                        color="purple"
                        className=' lg:rounded-l-none!  '
                    />
                </div>

                {
                    (exam.start_time || exam.end_time) && (
                        <div className="mb-8">
                            <h2 className="text-lg font-semibold text-gray-900 mb-3">Dates importantes</h2>
                            <div className="bg-gray-50 rounded-lg p-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {exam.start_time && (
                                        <TextEntry label="Début" value={formatDate(exam.start_time)} />

                                    )}
                                    {exam.end_time && (
                                        <TextEntry label="Fin" value={formatDate(exam.end_time)} />

                                    )}
                                </div>
                            </div>
                        </div>
                    )
                }
                {AlertMessage}

            </Section >
        </AuthenticatedLayout >
    );
}


/**
 * Un composant d'alerte affichant des instructions importantes pour les étudiants avant de commencer l'examen.
 *
 * NOTE : Cette alerte utilise le composant `AlertEntry` de type avertissement et une liste de consignes clés pour l'examen.
 * Elle informe les étudiants sur la connexion internet, le mode plein écran, la politique anti-triche,
 * la sauvegarde automatique des réponses, et l'obligation de terminer l'examen dans le temps imparti.
 */
const AlertMessage = (
    <AlertEntry type="warning" title='IMPORTANTS' >


        <ul className="list-disc list-inside space-y-1 text-sm">
            <li>Assurez-vous d'avoir une connexion internet stable</li>
            <li>L'examen se déroulera en mode plein écran pour des raisons de sécurité</li>
            <li>Les tentatives de triche seront détectées et sanctionnées</li>
            <li>Vos réponses sont sauvegardées automatiquement</li>
            <li>Une fois commencé, vous devez terminer l'examen dans le temps imparti</li>
        </ul>
    </AlertEntry>
);

