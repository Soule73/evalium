import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import Section from '@/Components/Section';
import StatCard from '@/Components/StatCard';
import { UserGroupIcon, UsersIcon, UserMinusIcon } from '@heroicons/react/24/outline';
import { route } from 'ziggy-js';
import { Group, User } from '@/types';
import Badge from '@/Components/Badge';
import { formatDate } from '@/utils/formatters';
import { DataTableConfig } from '@/types/datatable';
import { DataTable } from '@/Components/DataTable';
import ConfirmationModal from '@/Components/ConfirmationModal';
import { useState } from 'react';
import TextEntry from '@/Components/TextEntry';

interface Props {
    group: Group & {
        students: Array<User & {
            pivot: {
                enrolled_at: string;
                left_at?: string;
                is_active: boolean;
            }
        }>;
    };
}

export default function ShowGroup({ group }: Props) {
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [studentToRemove, setStudentToRemove] = useState<User | null>(null);
    const [selectedStudents, setSelectedStudents] = useState<(number | string)[]>([]);
    const [confirmBulkRemove, setConfirmBulkRemove] = useState(false);
    const [loading, setLoading] = useState(false);

    const handleEditGroup = () => {
        router.visit(route('admin.groups.edit', { group: group.id }));
    };

    const handleAssignStudents = () => {
        router.visit(route('admin.groups.assign-students', { group: group.id }));
    };

    const handleDeleteGroup = () => {
        router.delete(route('admin.groups.destroy', { group: group.id }), {
            onSuccess: () => {
                router.visit(route('admin.groups.index'));
            }
        });
    };

    const handleRemoveStudent = (student: User) => {
        setStudentToRemove(student);
    };

    const confirmRemoveStudent = () => {
        if (studentToRemove) {
            router.delete(route('admin.groups.remove-student', {
                group: group.id,
                student: studentToRemove.id
            }), {
                onSuccess: () => {
                    setStudentToRemove(null);
                }
            });
        }
    };

    const handleBulkRemove = (_ids: (number | string)[]) => {
        setConfirmBulkRemove(true);
    };

    const handleConfirmBulkRemove = () => {
        if (selectedStudents.length === 0) return;

        setLoading(true);
        router.post(route('admin.groups.bulk-remove-students', { group: group.id }), {
            student_ids: selectedStudents
        }, {
            onSuccess: () => {
                setSelectedStudents([]);
                setConfirmBulkRemove(false);
                setLoading(false);
            },
            onError: () => {
                setLoading(false);
                setConfirmBulkRemove(false);
            }
        });
    };

    const handleCloseBulkModal = () => {
        if (!loading) {
            setConfirmBulkRemove(false);
        }
    };


    const getStudentStatusBadge = (isActive: boolean) => {
        return isActive ? (
            <Badge label="Inscrit" type="success" />
        ) : (
            <Badge label="Quitté" type="gray" />
        );
    };

    const studentsTableConfig: DataTableConfig<User & {
        pivot: { enrolled_at: string; left_at?: string; is_active: boolean }
    }> = {
        columns: [
            {
                key: 'name',
                label: 'Étudiant',
                render: (student) => (
                    <div>
                        <div className="text-sm font-medium text-gray-900">{student.name}</div>
                        <div className="text-sm text-gray-500">{student.email}</div>
                    </div>
                )
            },
            {
                key: 'enrolled_at',
                label: 'Date d\'inscription',
                render: (student) => (
                    <span className="text-sm text-gray-600">
                        {formatDate(student.pivot.enrolled_at)}
                    </span>
                )
            },
            {
                key: 'left_at',
                label: 'Date de sortie',
                render: (student) => (
                    <span className="text-sm text-gray-600">
                        {student.pivot.left_at ? formatDate(student.pivot.left_at) : '-'}
                    </span>
                )
            },
            {
                key: 'status',
                label: 'Statut',
                render: (student) => getStudentStatusBadge(student.pivot.is_active)
            },
            {
                key: 'actions',
                label: 'Actions',
                render: (student) => (
                    student.pivot.is_active ? (
                        <Button
                            onClick={() => handleRemoveStudent(student)}
                            color="secondary"
                            size="sm"
                            variant="outline"
                        >
                            Retirer
                        </Button>
                    ) : null
                )
            }
        ],
        searchPlaceholder: 'Rechercher un étudiant...',
        filters: [
            {
                key: 'status',
                type: 'select',
                label: 'Filtrer par statut',
                options: [
                    { label: 'Tous les statuts', value: '' },
                    { label: 'Actifs uniquement', value: 'active' },
                    { label: 'Sortis uniquement', value: 'inactive' }
                ]
            }
        ],
        emptyState: {
            title: 'Aucun étudiant assigné',
            subtitle: 'Assignez des étudiants à ce groupe pour commencer',
            icon: <UserGroupIcon className="w-12 h-12 mx-auto text-gray-400" />
        },
        perPageOptions: [10, 25, 50],
        enableSelection: true,
        isSelectable: (student) => student.pivot.is_active,
        selectionActions: (selectedIds) => (
            <>
                <Button
                    onClick={() => handleBulkRemove(selectedIds)}
                    color="danger"
                    size="sm"
                >
                    <UserMinusIcon className="w-4 h-4 mr-2" />
                    Retirer ({selectedIds.length})
                </Button>
            </>
        ),
    };

    // Convertir les données pour la table
    const studentsData = {
        data: group.students || [],
        current_page: 1,
        last_page: 1,
        per_page: group.students?.length || 0,
        total: group.students?.length || 0,
        from: 1,
        to: group.students?.length || 0,
        first_page_url: '',
        last_page_url: '',
        links: [],
        next_page_url: null,
        path: '',
        prev_page_url: null
    };

    // Calculs statistiques
    const totalStudents = group.students?.length || 0;
    const activeStudents = group.students?.filter(student => student.pivot.is_active).length || 0;
    const inactiveStudents = group.students?.filter(student => !student.pivot.is_active).length || 0;

    return (
        <AuthenticatedLayout title={group.display_name}>
            <ConfirmationModal
                isOpen={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDeleteGroup}
                title="Supprimer le groupe"
                message={`Êtes-vous sûr de vouloir supprimer le groupe "${group.display_name}" ? Cette action est irréversible.`}
                confirmText="Supprimer"
                cancelText="Annuler"
                type="danger"
            />

            <ConfirmationModal
                isOpen={!!studentToRemove}
                onClose={() => setStudentToRemove(null)}
                onConfirm={confirmRemoveStudent}
                title="Retirer l'étudiant"
                message={`Êtes-vous sûr de vouloir retirer ${studentToRemove?.name} de ce groupe ?`}
                confirmText="Retirer"
                cancelText="Annuler"
                type="warning"
            />


            <Section
                title={group.display_name}
                subtitle={group.description || 'Détails du groupe'}
                collapsible
                actions={
                    <div className="flex space-x-2">
                        <Button
                            onClick={handleAssignStudents}
                            color="secondary"
                            variant="outline"
                            size="sm"
                        >
                            Assigner des étudiants
                        </Button>
                        <Button
                            onClick={handleEditGroup}
                            color="primary"
                            variant="outline"
                            size="sm"
                        >
                            Modifier
                        </Button>
                        <Button
                            onClick={() => setShowDeleteModal(true)}
                            color="secondary"
                            variant="outline"
                            size="sm"
                        >
                            Supprimer
                        </Button>
                    </div>
                }
            >
                {/* Informations générales */}
                <h3 className="text-lg font-medium text-gray-900 mb-4">Informations générales</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <TextEntry label='Niveau académique' value={group.level?.name || '-'} />
                    <TextEntry label='Année académique' value={group.academic_year ?? '-'} />
                    <TextEntry label='Période' value={`${formatDate(group.start_date)} - ${formatDate(group.end_date)}`} />
                    <TextEntry label='Capacité' value={`${group.max_students} étudiants max`} />
                </div>

                {/* Statistiques */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <StatCard
                        title="Total étudiants"
                        value={totalStudents}
                        icon={UserGroupIcon}
                        color="blue"
                    />
                    <StatCard
                        title="Étudiants actifs"
                        value={activeStudents}
                        icon={UsersIcon}
                        color="green"
                    />
                    <StatCard
                        title="Étudiants sortis"
                        value={inactiveStudents}
                        icon={UsersIcon}
                        color="red"
                    />
                    <StatCard
                        title="Places disponibles"
                        value={Math.max(0, group.max_students - activeStudents)}
                        icon={UsersIcon}
                        color="purple"
                    />
                </div>
            </Section>

            {/* Liste des étudiants */}
            <Section title="Étudiants du groupe">
                <DataTable
                    data={studentsData}
                    config={studentsTableConfig}
                    onSelectionChange={(selectedIds) => {
                        setSelectedStudents(selectedIds);
                    }}
                />
            </Section>

            <ConfirmationModal
                isOpen={confirmBulkRemove}
                onClose={handleCloseBulkModal}
                onConfirm={handleConfirmBulkRemove}
                title="Retirer les étudiants"
                message={`Voulez-vous vraiment retirer ${selectedStudents.length} étudiant(s) de ce groupe ?`}
                confirmText="Retirer"
                cancelText="Annuler"
                type="warning"
                icon={UserMinusIcon}
                loading={loading}
            />
        </AuthenticatedLayout>
    );
}