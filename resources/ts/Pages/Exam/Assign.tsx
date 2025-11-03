import { useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import Section from '@/Components/Section';
import { Exam, Group } from '@/types';
import { route } from 'ziggy-js';
import { UserGroupIcon, UserPlusIcon } from '@heroicons/react/24/outline';
import { DataTable } from '@/Components/DataTable';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import ConfirmationModal from '@/Components/ConfirmationModal';
import MarkdownRenderer from '@/Components/form/MarkdownRenderer';
import { breadcrumbs } from '@/utils/breadcrumbs';

interface Props {
    exam: Exam;
    assignedGroups: Group[];
    availableGroups: Group[];
}

export default function Assign({ exam, assignedGroups, availableGroups }: Props) {
    const [isProcessing, setIsProcessing] = useState(false);
    const [showConfirmModal, setShowConfirmModal] = useState(false);
    const [showRemoveGroupModal, setShowRemoveGroupModal] = useState<{ isOpen: boolean; group: Group | null }>({
        isOpen: false,
        group: null
    });
    const [pendingAssignment, setPendingAssignment] = useState<{
        ids: (string | number)[];
        count: number;
    } | null>(null);

    const handleAssignGroups = (selectedIds: (string | number)[]) => {
        const selectedGroups = availableGroups.filter(g => selectedIds.includes(g.id));
        const totalStudents = selectedGroups.reduce((sum, group) => sum + (group.active_students_count || 0), 0);

        setPendingAssignment({
            ids: selectedIds,
            count: totalStudents
        });
        setShowConfirmModal(true);
    };

    const confirmAssignment = () => {
        if (!pendingAssignment) return;

        setIsProcessing(true);

        router.post(
            route('exams.assign.groups', exam.id),
            { group_ids: pendingAssignment.ids },
            {
                onFinish: () => {
                    setIsProcessing(false);
                    setShowConfirmModal(false);
                    setPendingAssignment(null);
                }
            }
        );
    };

    const cancelAssignment = () => {
        setShowConfirmModal(false);
        setPendingAssignment(null);
    };

    const handleRemoveGroup = () => {
        if (!showRemoveGroupModal.group) return;

        router.delete(
            route('exams.groups.remove', { exam: exam.id, group: showRemoveGroupModal.group.id }),
            {
                onFinish: () => setShowRemoveGroupModal({ isOpen: false, group: null })
            }
        );
    };

    // Transformer availableGroups en format PaginationType
    const groupsData: PaginationType<Group> = {
        data: availableGroups,
        current_page: 1,
        per_page: availableGroups.length,
        total: availableGroups.length,
        last_page: 1,
        from: 1,
        to: availableGroups.length,
        first_page_url: '',
        last_page_url: '',
        next_page_url: null,
        prev_page_url: null,
        path: '',
        links: []
    };

    const groupsTableConfig: DataTableConfig<Group> = {
        columns: [
            {
                key: 'display_name',
                label: 'Groupe',
                render: (group) => (
                    <div className="flex items-center space-x-3">
                        <UserGroupIcon className="h-5 w-5 text-gray-400 shrink-0" />
                        <div>
                            <div className="font-medium text-gray-900">{group.display_name}</div>
                            <div className="text-sm text-gray-500">
                                {group.active_students_count} étudiant(s) actif(s)
                            </div>
                        </div>
                    </div>
                )
            }
        ],
        searchPlaceholder: 'Rechercher un groupe...',
        emptyState: {
            title: 'Tous les groupes assignés',
            subtitle: 'Tous les groupes actifs ont déjà accès à cet examen',
            icon: 'UserGroupIcon'
        },
        emptySearchState: {
            title: 'Aucun groupe trouvé',
            subtitle: 'Aucun groupe ne correspond à vos critères de recherche.',
            resetLabel: 'Réinitialiser la recherche'
        },
        perPageOptions: [10, 25, 50],
        enableSelection: true,
        selectionActions: (selectedIds) => (
            <Button
                onClick={() => handleAssignGroups(selectedIds)}
                color="primary"
                size="sm"
                loading={isProcessing}
                disabled={isProcessing || selectedIds.length === 0}
            >
                <UserPlusIcon className="w-4 h-4 mr-2" />
                Assigner à {selectedIds.length} groupe{selectedIds.length > 1 ? 's' : ''}
            </Button>
        ),
    };

    return (
        <AuthenticatedLayout title={`Assigner l'examen: ${exam.title}`}
            breadcrumb={breadcrumbs.examAssign(exam.title, exam.id)}
        >

            <Section
                title="Informations sur l'examen"
                subtitle="Détails de l'examen à assigner"
                actions={<Button
                    type="button"
                    onClick={() => router.visit(route('exams.show', exam.id))}
                    color="secondary"
                    variant="outline"
                >
                    Annuler
                </Button>}
            >
                <div className="space-y-2">
                    <h2 className="text-xl font-semibold text-gray-900">{exam.title}</h2>
                    {exam.description && (
                        <MarkdownRenderer>{exam.description}</MarkdownRenderer>
                    )}
                    <p className="text-sm text-gray-500">
                        Durée: {exam.duration} minutes
                    </p>
                </div>
            </Section>

            {/* Groupes déjà assignés */}
            {assignedGroups.length > 0 && (
                <Section
                    title="Groupes assignés"
                    subtitle={`${assignedGroups.length} groupe(s) ont accès à cet examen`}
                    collapsible
                    defaultOpen={false}
                >
                    <DataTable
                        data={{
                            data: assignedGroups,
                            current_page: 1,
                            per_page: assignedGroups.length,
                            total: assignedGroups.length,
                            last_page: 1,
                            from: 1,
                            to: assignedGroups.length,
                            first_page_url: '',
                            last_page_url: '',
                            next_page_url: null,
                            prev_page_url: null,
                            path: '',
                            links: []
                        }}
                        config={{
                            columns: [
                                {
                                    key: 'display_name',
                                    label: 'Groupe',
                                    render: (group) => (
                                        <div className="flex items-center space-x-3">
                                            <UserGroupIcon className="h-5 w-5 text-green-600 shrink-0" />
                                            <div>
                                                <div className="font-medium text-gray-900">{group.display_name}</div>
                                                <div className="text-sm text-gray-500">
                                                    {group.active_students_count} étudiant(s) actif(s)
                                                </div>
                                            </div>
                                        </div>
                                    )
                                },
                                {
                                    key: 'actions',
                                    label: 'Actions',
                                    render: (group) => (
                                        <div className="flex space-x-2">
                                            <Button
                                                type="button"
                                                color="primary"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => router.visit(route('exams.group.show', { exam: exam.id, group: group.id }))}
                                            >
                                                Voir détails
                                            </Button>
                                            <Button
                                                type="button"
                                                color="danger"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => setShowRemoveGroupModal({ isOpen: true, group })}
                                            >
                                                Retirer
                                            </Button>
                                        </div>
                                    )
                                }
                            ],
                            searchPlaceholder: 'Rechercher un groupe...',
                            emptyState: {
                                title: 'Aucun groupe assigné',
                                subtitle: 'Aucun groupe n\'a encore accès à cet examen',
                                icon: 'UserGroupIcon'
                            },
                            perPageOptions: [10, 25, 50],
                        }}
                    />
                </Section>
            )}

            {/* Sélectionner les groupes à assigner */}
            <Section
                title="Assigner l'examen à des groupes"
                subtitle="Sélectionnez les groupes auxquels vous souhaitez donner accès à cet examen"
            >
                <DataTable
                    data={groupsData}
                    config={groupsTableConfig}
                />
            </Section>

            {/* Modal de confirmation */}
            <ConfirmationModal
                isOpen={showConfirmModal}
                onClose={cancelAssignment}
                onConfirm={confirmAssignment}
                title="Confirmer l'assignation"
                message={`Vous êtes sur le point d'assigner cet examen à ${pendingAssignment?.ids.length} groupe(s), ce qui donnera accès à environ ${pendingAssignment?.count} étudiant(s).`}
                confirmText="Confirmer l'assignation"
                cancelText="Annuler"
                type="info"
                icon={UserGroupIcon}
                loading={isProcessing}
            >
                <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 w-full">
                    <p className="text-sm text-blue-800">
                        <strong>Examen :</strong> {exam.title}
                    </p>
                    {exam.description && (
                        <p className="text-sm text-blue-700 mt-1">{exam.description}</p>
                    )}
                    <p className="text-sm text-blue-700 mt-1">
                        <strong>Durée :</strong> {exam.duration} minutes
                    </p>
                </div>
            </ConfirmationModal>

            <ConfirmationModal
                isOpen={showRemoveGroupModal.isOpen}
                onClose={() => setShowRemoveGroupModal({ isOpen: false, group: null })}
                onConfirm={handleRemoveGroup}
                title="Retirer le groupe"
                message={`Êtes-vous sûr de vouloir retirer l'examen "${exam.title}" du groupe "${showRemoveGroupModal.group?.display_name}" ?`}
                confirmText="Retirer"
                cancelText="Annuler"
                type="danger"
            />
        </AuthenticatedLayout>
    );
}