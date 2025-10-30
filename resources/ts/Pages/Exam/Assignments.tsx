import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import { Exam, ExamAssignment, Group } from '@/types';
import Section from '@/Components/Section';
import { DataTable } from '@/Components/DataTable';
import { route } from 'ziggy-js';
import { useState } from 'react';
import { PaginationType } from '@/types/datatable';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { getExamAssignmentColumns, examAssignmentFilters, getGroupTableConfig, ExamStatsCards, ExamHeader } from '@/Components/exam';
import { groupsToPaginationType } from '@/utils';
import ConfirmationModal from '@/Components/ConfirmationModal';

interface Props {
    exam: Exam;
    assignments: PaginationType<ExamAssignment>;
    assignedGroups?: Group[];
    stats: {
        total_assigned: number;
        completed: number;
        in_progress: number;
        not_started: number;
        average_score: number | null;
    };
}

interface ConfirmModalProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirm: () => void;
    isSubmitting: boolean;
}


export default function ExamAssignments({ exam, assignments, stats, assignedGroups }: Props) {
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

    const columns = getExamAssignmentColumns({
        exam,
        onRemove: handleRemoveAssignment,
        showActions: true
    });

    const dataTableConfig = {
        columns,
        filters: examAssignmentFilters,
        searchPlaceholder: 'Rechercher par nom ou email...',
        emptyState: {
            title: 'Aucune assignation trouvée',
            subtitle: 'Aucun étudiant n\'est assigné à cet examen.',
            actions: (
                <Button
                    onClick={() => router.visit(route('exams.assign', exam.id))}
                    color="primary"
                >
                    Assigner des étudiants
                </Button>
            ),
        },
    };

    const groupsTableConfig = getGroupTableConfig({
        exam,
        showActions: true,
        showDetailsButton: true
    });

    return (
        <AuthenticatedLayout title={`Assignations : ${exam.title}`}
            breadcrumb={breadcrumbs.teacherExamAssignments(exam.title, exam.id)}
        >
            {/* <ConfirmModal {...(confirmModal || { isOpen: false, onClose: () => { }, onConfirm: () => { }, isSubmitting: false })} /> */}

            <ConfirmationModal
                isOpen={confirmModal?.isOpen || false}
                onClose={confirmModal?.onClose || (() => { })}
                onConfirm={confirmModal?.onConfirm || (() => { })}
                title='Êtes-vous sûr de vouloir retirer cette assignation ?'
                message='Cette action ne peut pas être annulée.'
                type="warning"
                confirmText="Retirer"
                cancelText='Annuler'
            >

            </ConfirmationModal>

            <Section
                title="Assignations de l'examen"
                subtitle={<ExamHeader exam={exam} showDescription={true} />}
                actions={
                    <Button
                        size='sm'
                        variant='outline'

                        onClick={() => router.visit(route('exams.assign', exam.id))}
                        color="secondary"
                    >
                        Ajouter des étudiants
                    </Button>
                }
            >
                {/* Statistiques */}
                <ExamStatsCards stats={stats} className="mb-6" />

                {/* Groupes assignés */}
                {assignedGroups && assignedGroups.length > 0 && (
                    <div className="mb-6">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">
                            Groupes assignés ({assignedGroups.length})
                        </h3>
                        <DataTable
                            data={groupsToPaginationType(assignedGroups)}
                            config={groupsTableConfig}
                        />
                    </div>
                )}

                {/* Tableau des assignations */}
                <div>
                    <h3 className="text-lg font-medium text-gray-900 mb-4">
                        Assignations individuelles
                    </h3>
                    <DataTable
                        data={assignments}
                        config={dataTableConfig}
                    />
                </div>
            </Section>
        </AuthenticatedLayout>
    );
}