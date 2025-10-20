import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import { formatDate, formatExamAssignmentStatus } from '@/utils/formatters';
import { Exam, ExamAssignment } from '@/types';
import Section from '@/Components/Section';
import StatCard from '@/Components/StatCard';
import { DataTable } from '@/Components/DataTable';
import Badge from '@/Components/Badge';
import {
    UserGroupIcon,
    CheckCircleIcon,
    ClockIcon,
    ChartBarIcon,
    ExclamationCircleIcon
} from '@heroicons/react/24/outline';
import { route } from 'ziggy-js';
import Modal from '@/Components/Modal';
import { useState } from 'react';
import MarkdownRenderer from '@/Components/form/MarkdownRenderer';
import { PaginationType } from '@/types/datatable';

interface Props {
    exam: Exam;
    assignments: PaginationType<ExamAssignment>;
    stats: {
        total_assigned: number;
        completed: number;
        in_progress: number;
        not_started: number;
        average_score: number | null;
    };
}

export default function ExamAssignments({ exam, assignments, stats }: Props) {
    const [confirmModal, setConfirmModal] = useState<ConfirmModalProps | null>(null);
    const handleRemoveAssignment = (assignment: ExamAssignment) => {
        setConfirmModal({
            isOpen: true,
            onClose: () => setConfirmModal(null),
            onConfirm: () => {
                router.delete(`/teacher/exams/${exam.id}/assignments/${assignment.student_id}`);
                setConfirmModal(null);
            },
            isSubmitting: false,
        });
    };

    // const getScoreDisplay = (assignment: ExamAssignment) => {
    //     if (assignment.score !== null && assignment.score !== undefined) {
    //         // Calculer le pourcentage avec le total de points de l'examen
    //         const totalPoints = exam.questions?.reduce((sum, q) => sum + (q.points || 0), 0) || 0;
    //         const percentage = totalPoints > 0 ? Math.round((assignment.score / totalPoints) * 100) : 0;
    //         return `${assignment.score}/${totalPoints} (${percentage}%)`;
    //     }
    //     return 'N/A';
    // };

    const columns = [
        {
            key: 'student',
            label: 'Étudiant',
            render: (assignment: ExamAssignment) => (
                <div>
                    <div className="text-sm font-medium text-gray-900">
                        {assignment.student?.name || 'Nom non disponible'}
                    </div>
                    <div className="text-sm text-gray-500">
                        {assignment.student?.email || 'Email non disponible'}
                    </div>
                </div>
            ),
        },
        {
            key: 'status',
            label: 'Statut',
            render: (assignment: ExamAssignment) => {
                const status = formatExamAssignmentStatus(assignment.status);
                return (
                    <Badge
                        type={status.color as 'success' | 'warning' | 'info' | 'gray'}
                        label={status.label}
                    />
                );
            },
        },
        {
            key: 'assigned_at',
            label: 'Assigné le',
            render: (assignment: ExamAssignment) => (
                <div className="text-sm text-gray-500">
                    {formatDate(assignment.assigned_at, 'datetime')}
                </div>
            ),
        },
        {
            key: 'started_at',
            label: 'Commencé le',
            render: (assignment: ExamAssignment) =>
            (
                <div className="text-sm text-gray-500">
                    {assignment.started_at ? formatDate(assignment.started_at, 'datetime') : '-'}
                </div>
            ),
        },
        {
            key: 'submitted_at',
            label: 'Terminé le',
            render: (assignment: ExamAssignment) =>
            (
                <div className="text-sm text-gray-500">
                    {assignment.submitted_at ? formatDate(assignment.submitted_at, 'datetime') : '-'}
                </div>
            ),
        },
        {
            key: 'score',
            label: 'Note',
            render: (assignment: ExamAssignment) => assignment?.score ?? '-',
        },
        {
            key: 'actions',
            label: 'Actions',
            render: (assignment: ExamAssignment) => (
                <div className="flex space-x-2">
                    {(assignment.status === 'submitted' || assignment.status === 'graded' || assignment.status === 'pending_review') ? (
                        <Button
                            onClick={() => router.visit(route('teacher.exams.results', { exam: exam.id, student: assignment.student_id }))}
                            color="success"
                            size="sm"
                            variant="outline"
                            className=' text-xs '
                        >
                            Voir résultat
                        </Button>
                    ) :
                        <Button
                            onClick={() => handleRemoveAssignment(assignment)}
                            color="danger"
                            className='text-xs '
                            variant="outline"
                            size="sm"
                        >
                            Retirer
                        </Button>}
                </div>
            ),
        },
    ];

    const dataTableConfig = {
        columns,
        filters: [
            {
                key: 'status',
                label: 'Statut',
                type: 'select' as const,
                options: [
                    { value: '', label: 'Tous les statuts' },
                    { value: 'assigned', label: 'Non commencé' },
                    { value: 'started', label: 'En cours' },
                    { value: 'submitted', label: 'Soumis' },
                    { value: 'graded', label: 'Noté' },
                ],
            },
        ],
        searchPlaceholder: 'Rechercher par nom ou email...',
        emptyState: {
            title: 'Aucune assignation trouvée',
            subtitle: 'Aucun étudiant n\'est assigné à cet examen.',
            actions: (
                <Button
                    onClick={() => router.visit(route('teacher.exams.assign', exam.id))}
                    color="primary"
                >
                    Assigner des étudiants
                </Button>
            ),
        },
    };

    return (
        <AuthenticatedLayout title={`Assignations : ${exam.title}`}>
            <ConfirmModal {...(confirmModal || { isOpen: false, onClose: () => { }, onConfirm: () => { }, isSubmitting: false })} />

            <Section
                title="Assignations de l'examen"
                subtitle={
                    <div>
                        <div className="text-xl text-gray-700">{exam.title}</div>
                        {exam.description && (
                            <MarkdownRenderer>{exam.description}</MarkdownRenderer>
                        )}
                    </div>
                }
                actions={
                    <Button
                        size='sm'
                        variant='outline'

                        onClick={() => router.visit(route('teacher.exams.assign', exam.id))}
                        color="secondary"
                    >
                        Ajouter des étudiants
                    </Button>
                }
            >
                {/* Statistiques */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <StatCard
                        title="Total assigné"
                        value={stats.total_assigned}
                        color="blue"
                        icon={UserGroupIcon}
                    />
                    <StatCard
                        title="Terminé"
                        value={stats.completed}
                        color="green"
                        icon={CheckCircleIcon}
                    />
                    <StatCard
                        title="En cours"
                        value={stats.in_progress}
                        color="yellow"
                        icon={ClockIcon}
                    />
                    <StatCard
                        title="Note moyen"
                        value={stats.average_score !== null ? `${Math.round(stats.average_score)}%` : 'N/A'}
                        color="purple"
                        icon={ChartBarIcon}
                    />
                </div>

                {/* Tableau des assignations */}
                <DataTable
                    data={assignments}
                    config={dataTableConfig}
                />
            </Section>
        </AuthenticatedLayout>
    );
}


interface ConfirmModalProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirm: () => void;
    isSubmitting: boolean;
}

function ConfirmModal({ isOpen, onClose, onConfirm, isSubmitting }: ConfirmModalProps) {
    return (
        <Modal isOpen={isOpen} onClose={onClose}
        >
            <div className=' min-h-72 flex flex-col items-center justify-between p-6'>
                <ExclamationCircleIcon className="h-12 w-12 text-red-500 mx-auto mb-4" />
                <h3 className="text-lg font-bold mb-4">Rétirer cette assignation</h3>
                <p className="text-gray-600 mb-6 text-center ">
                    Êtes-vous sûr de vouloir retirer cette assignation ?
                </p>
                <p className="text-sm text-gray-500 text-center mb-6">
                    Cette action ne peut pas être annulée.
                </p>
                <div className="flex justify-end w-full space-x-4">
                    <Button
                        size="md"
                        color="secondary"
                        variant="outline"
                        onClick={onClose}
                    >
                        Annuler
                    </Button>
                    <Button
                        size="md"
                        color="primary"
                        onClick={onConfirm}
                        disabled={isSubmitting}
                    >
                        Retirer
                    </Button>
                </div>
            </div>
        </Modal>
    );
}