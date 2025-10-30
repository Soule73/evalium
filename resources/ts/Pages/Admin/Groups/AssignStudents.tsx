import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import Section from '@/Components/Section';
import { route } from 'ziggy-js';
import { Group, User } from '@/types';
import { useState } from 'react';
import { UserPlusIcon } from '@heroicons/react/24/outline';
import { DataTable } from '@/Components/DataTable';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import ConfirmationModal from '@/Components/ConfirmationModal';
import { breadcrumbs } from '@/utils/breadcrumbs';

interface Props {
    group: Group & { active_students_count?: number };
    availableStudents: User[];
}

export default function AssignStudents({ group, availableStudents }: Props) {
    const [selectedStudents, setSelectedStudents] = useState<(number | string)[]>([]);
    const [confirmModal, setConfirmModal] = useState(false);
    const [loading, setLoading] = useState(false);

    const handleCancel = () => {
        router.visit(route('groups.show', { group: group.id }));
    };

    const handleAssignStudents = (_ids: (number | string)[]) => {
        setConfirmModal(true);
    };

    const handleConfirmAssign = () => {
        if (selectedStudents.length === 0) return;

        setLoading(true);
        router.post(route('groups.store-students', { group: group.id }), {
            student_ids: selectedStudents
        }, {
            onSuccess: () => {
                setSelectedStudents([]);
                setConfirmModal(false);
                setLoading(false);
                router.visit(route('groups.show', { group: group.id }));
            },
            onError: () => {
                setLoading(false);
                setConfirmModal(false);
            }
        });
    };

    const handleCloseModal = () => {
        if (!loading) {
            setConfirmModal(false);
        }
    };

    const availableSlots = group.max_students - (group.active_students_count || 0);
    const maxSelectable = Math.min(availableSlots, availableStudents.length);

    // Transformer availableStudents en format PaginationType pour DataTable
    const studentsData: PaginationType<User> = {
        data: availableStudents,
        current_page: 1,
        per_page: availableStudents.length,
        total: availableStudents.length,
        last_page: 1,
        from: 1,
        to: availableStudents.length,
        first_page_url: '',
        last_page_url: '',
        next_page_url: null,
        prev_page_url: null,
        path: '',
        links: []
    };

    const dataTableConfig: DataTableConfig<User> = {
        columns: [
            {
                key: 'name',
                label: 'Nom',
                render: (student) => (
                    <div>
                        <div className="text-sm font-medium text-gray-900">{student.name}</div>
                        <div className="text-sm text-gray-500">{student.email}</div>
                    </div>
                )
            },
            {
                key: 'email',
                label: 'Email',
                render: (student) => (
                    <span className="text-sm text-gray-600">{student.email}</span>
                )
            }
        ],
        searchPlaceholder: 'Rechercher par nom ou email...',
        emptyState: {
            title: 'Aucun étudiant disponible',
            subtitle: 'Tous les étudiants sont déjà assignés à des groupes',
            icon: <UserPlusIcon className="w-12 h-12 mx-auto text-gray-400" />
        },
        emptySearchState: {
            title: 'Aucun étudiant trouvé',
            subtitle: 'Aucun étudiant ne correspond à vos critères de recherche.',
            resetLabel: 'Réinitialiser la recherche'
        },
        perPageOptions: [10, 25, 50, 100],
        enableSelection: true,
        maxSelectable: maxSelectable,
        selectionActions: (selectedIds) => (
            <>
                <Button
                    onClick={() => handleAssignStudents(selectedIds)}
                    color="primary"
                    size="sm"
                >
                    <UserPlusIcon className="w-4 h-4 mr-2" />
                    Assigner ({selectedIds.length})
                </Button>
            </>
        ),
    };

    return (
        <AuthenticatedLayout title={`Assigner des étudiants - ${group.display_name}`}
            breadcrumb={breadcrumbs.adminGroupAssignStudents(group.display_name, group.id)}
        >
            <Section
                title={`Assigner des étudiants au groupe "${group.display_name}"`}
                subtitle={`Places disponibles: ${availableSlots} / ${group.max_students}`}
                actions={
                    <Button
                        onClick={handleCancel}
                        color="secondary"
                        variant="outline"
                        size="sm"
                    >
                        Retour
                    </Button>
                }
            >
                {availableSlots <= 0 ? (
                    <div className="bg-amber-50 border border-amber-200 rounded-lg p-6 text-center">
                        <div className="text-amber-800 font-medium mb-2">
                            Le groupe est complet
                        </div>
                        <p className="text-amber-700 text-sm">
                            Aucune place disponible. Veuillez augmenter la capacité maximale du groupe ou retirer des étudiants.
                        </p>
                    </div>
                ) : (
                    <>
                        {maxSelectable < availableStudents.length && (
                            <div className="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <p className="text-sm text-blue-800">
                                    <strong>Note :</strong> Vous pouvez sélectionner jusqu'à {maxSelectable} étudiant(s)
                                    (places disponibles dans le groupe).
                                </p>
                            </div>
                        )}

                        <DataTable
                            data={studentsData}
                            config={dataTableConfig}
                            onSelectionChange={(selectedIds) => {
                                setSelectedStudents(selectedIds);
                            }}
                        />
                    </>
                )}
            </Section>

            <ConfirmationModal
                isOpen={confirmModal}
                onClose={handleCloseModal}
                onConfirm={handleConfirmAssign}
                title="Assigner les étudiants"
                message={`Voulez-vous vraiment assigner ${selectedStudents.length} étudiant(s) au groupe "${group.display_name}" ?`}
                confirmText="Assigner"
                cancelText="Annuler"
                type="info"
                icon={UserPlusIcon}
                loading={loading}
            />
        </AuthenticatedLayout>
    );
}