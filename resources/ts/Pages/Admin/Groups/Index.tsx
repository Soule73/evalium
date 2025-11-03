import { router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import { formatDate } from '@/utils/formatters';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import Section from '@/Components/Section';
import StatCard from '@/Components/StatCard';
import { UserGroupIcon, AcademicCapIcon, UsersIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/react/24/outline';
import { DataTable } from '@/Components/DataTable';
import { route } from 'ziggy-js';
import { Group, PageProps } from '@/types';
import Badge from '@/Components/Badge';
import { useState } from 'react';
import ConfirmationModal from '@/Components/ConfirmationModal';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { hasPermission } from '@/utils/permissions';

interface Props extends PageProps {
    groups: PaginationType<Group>;
    filters: {
        search?: string;
        level_id?: string;
        is_active?: string;
    };
    levels: Record<number, string>;
}

export default function GroupIndex({ groups, levels }: Props) {
    const { auth } = usePage<PageProps>().props;

    // Vérifications des permissions
    const canCreateGroups = hasPermission(auth.permissions, 'create groups');
    const canViewGroups = hasPermission(auth.permissions, 'view groups');
    const canUpdateGroups = hasPermission(auth.permissions, 'update groups');
    const canToggleStatus = hasPermission(auth.permissions, 'toggle group status');

    const [confirmModal, setConfirmModal] = useState<{
        isOpen: boolean;
        type: 'activate' | 'deactivate' | null;
        ids: (number | string)[];
    }>({ isOpen: false, type: null, ids: [] });
    const [loading, setLoading] = useState(false);

    const handleCreateGroup = () => {
        router.visit(route('groups.create'));
    };

    const handleViewGroup = (groupId: number) => {
        router.visit(route('groups.show', { group: groupId }));
    };

    const handleEditGroup = (groupId: number) => {
        router.visit(route('groups.edit', { group: groupId }));
    };

    const handleBulkActivate = (ids: (number | string)[]) => {
        setConfirmModal({ isOpen: true, type: 'activate', ids });
    };

    const handleBulkDeactivate = (ids: (number | string)[]) => {
        setConfirmModal({ isOpen: true, type: 'deactivate', ids });
    };

    const handleConfirmAction = () => {
        if (!confirmModal.type) return;

        setLoading(true);
        const routeName = confirmModal.type === 'activate'
            ? 'groups.bulk-activate'
            : 'groups.bulk-deactivate';

        router.post(route(routeName), {
            ids: confirmModal.ids
        }, {
            onSuccess: () => {
                setConfirmModal({ isOpen: false, type: null, ids: [] });
                setLoading(false);
            },
            onError: () => {
                setLoading(false);
                setConfirmModal({ isOpen: false, type: null, ids: [] });
            }
        });
    };

    const handleCloseModal = () => {
        if (!loading) {
            setConfirmModal({ isOpen: false, type: null, ids: [] });
        }
    };

    const getStatusBadge = (isActive: boolean) => {
        return isActive ? (
            <Badge label="Actif" type="success" />
        ) : (
            <Badge label="Inactif" type="error" />
        );
    };

    const dataTableConfig: DataTableConfig<Group> = {
        columns: [
            {
                key: 'name',
                label: 'Groupe',
                render: (group) => (
                    <div>
                        <div className="text-sm font-medium text-gray-900">{group.display_name}</div>
                        <div className="text-sm text-gray-500">{group.description || 'Aucune description'}</div>
                    </div>
                )
            },
            {
                key: 'level',
                label: 'Niveau',
                render: (group) => (
                    <div className="flex items-center">
                        <AcademicCapIcon className="w-4 h-4 mr-2 text-gray-400" />
                        <span className="text-sm text-gray-900">
                            {group.level?.name || 'Non défini'}
                        </span>
                    </div>
                )
            },
            {
                key: 'students',
                label: 'Étudiants',
                render: (group) => (
                    <div className="flex items-center">
                        <UsersIcon className="w-4 h-4 mr-2 text-gray-400" />
                        <span className="text-sm text-gray-600">
                            {group.active_students_count || 0} / {group.max_students}
                        </span>
                    </div>
                )
            },
            {
                key: 'period',
                label: 'Période',
                render: (group) => (
                    <div className="text-sm text-gray-600">
                        <div>{formatDate(group.start_date)}</div>
                        <div className="text-xs text-gray-400">au {formatDate(group.end_date)}</div>
                    </div>
                )
            },
            {
                key: 'is_active',
                label: 'Statut',
                render: (group) => getStatusBadge(group.is_active)
            },
            {
                key: 'actions',
                label: 'Actions',
                render: (group) => (
                    <div className="flex space-x-2">
                        {canViewGroups && (
                            <Button
                                onClick={() => handleViewGroup(group.id)}
                                color="secondary"
                                size="sm"
                                variant="outline"
                            >
                                Voir
                            </Button>
                        )}
                        {canUpdateGroups && (
                            <Button
                                onClick={() => handleEditGroup(group.id)}
                                color="primary"
                                size="sm"
                                variant="outline"
                            >
                                Modifier
                            </Button>
                        )}
                    </div>
                )
            }
        ],
        searchPlaceholder: 'Rechercher par nom de groupe...',
        filters: [
            {
                key: 'level_id',
                type: 'select',
                label: 'Filtrer par niveau',
                options: [{ label: 'Tous les niveaux', value: '' }].concat(
                    Object.entries(levels).map(([id, name]) => ({
                        label: name,
                        value: id
                    }))
                )
            },
            {
                key: 'is_active',
                type: 'select',
                label: 'Filtrer par statut',
                options: [
                    { label: 'Tous les statuts', value: '' },
                    { label: 'Actifs', value: '1' },
                    { label: 'Inactifs', value: '0' }
                ]
            }
        ],
        emptyState: {
            title: 'Aucun groupe trouvé',
            subtitle: 'Commencez par créer votre premier groupe',
            icon: 'UserGroupIcon'
        },
        emptySearchState: {
            title: 'Aucun groupe trouvé',
            subtitle: 'Aucun groupe ne correspond à vos critères de recherche.',
            resetLabel: 'Réinitialiser les filtres'
        },
        perPageOptions: [10, 25, 50],
        enableSelection: canToggleStatus,
        selectionActions: canToggleStatus ? (selectedIds) => (
            <>
                <Button
                    size="sm"
                    onClick={() => handleBulkActivate(selectedIds)}
                    variant="outline"
                    color="success"
                >
                    Activer ({selectedIds.length})
                </Button>
                <Button
                    size="sm"
                    onClick={() => handleBulkDeactivate(selectedIds)}
                    variant="outline"
                    color="danger"
                >
                    Désactiver ({selectedIds.length})
                </Button>
            </>
        ) : undefined,
    };

    // Calcul des statistiques
    const totalGroups = groups.total;
    const activeGroups = groups.data.filter(group => group.is_active).length;
    const totalStudents = groups.data.reduce((sum, group) => sum + (group.active_students_count || 0), 0);
    const averageStudentsPerGroup = totalGroups > 0 ? Math.round(totalStudents / totalGroups) : 0;

    return (
        <AuthenticatedLayout title="Gestion des groupes"
            breadcrumb={breadcrumbs.groups()}
        >
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <StatCard
                    title="Total groupes"
                    value={totalGroups}
                    icon={UserGroupIcon}
                    color="blue"
                />
                <StatCard
                    title="Groupes actifs"
                    value={activeGroups}
                    icon={UserGroupIcon}
                    color="green"
                />
                <StatCard
                    title="Total étudiants"
                    value={totalStudents}
                    icon={UsersIcon}
                    color="purple"
                />
                <StatCard
                    title="Moyenne par groupe"
                    value={averageStudentsPerGroup}
                    icon={AcademicCapIcon}
                    color="yellow"
                />
            </div>

            <Section title="Liste des groupes"
                subtitle="Gérez les groupes de classes et leurs étudiants."
                actions={canCreateGroups && (
                    <Button
                        onClick={handleCreateGroup}
                        color="primary"
                        variant="solid"
                        size="sm"
                    >
                        Créer un groupe
                    </Button>
                )}
            >
                <DataTable
                    data={groups}
                    config={dataTableConfig}
                />
            </Section>

            <ConfirmationModal
                isOpen={confirmModal.isOpen}
                onClose={handleCloseModal}
                onConfirm={handleConfirmAction}
                title={confirmModal.type === 'activate' ? 'Activer les groupes' : 'Désactiver les groupes'}
                message={
                    confirmModal.type === 'activate'
                        ? `Voulez-vous vraiment activer ${confirmModal.ids.length} groupe(s) ?`
                        : `Voulez-vous vraiment désactiver ${confirmModal.ids.length} groupe(s) ?`
                }
                confirmText="Confirmer"
                cancelText="Annuler"
                type={confirmModal.type === 'activate' ? 'info' : 'warning'}
                icon={confirmModal.type === 'activate' ? CheckCircleIcon : XCircleIcon}
                loading={loading}
            />
        </AuthenticatedLayout>
    );
}