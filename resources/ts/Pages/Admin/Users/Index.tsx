import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import { formatDate, getRoleColor, getRoleLabel } from '@/utils/formatters';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import Section from '@/Components/Section';
import StatCard from '@/Components/StatCard';
import { UserGroupIcon, TrashIcon, ArrowPathIcon } from '@heroicons/react/24/outline';
import { DataTable } from '@/Components/DataTable';
import { route } from 'ziggy-js';
import { useState } from 'react';
import CreateUser from './Create';
import { User } from '@/types';
import Toggle from '@/Components/form/Toggle';
import { breadcrumbs } from '@/utils/breadcrumbs';
import ConfirmationModal from '@/Components/ConfirmationModal';

interface Group {
    id: number;
    display_name: string;
    academic_year: string;
    is_active: boolean;
}

interface Props {
    users: PaginationType<User>;
    roles: string[];
    groups: Group[];
    canManageAdmins: boolean;
    canDeleteUsers: boolean;
}

export default function UserIndex({ users, roles, groups, canDeleteUsers }: Props) {

    const [isShowCreateModal, setIsShowCreateModal] = useState(false);
    const [deleteModal, setDeleteModal] = useState<{ isOpen: boolean; userId: number | null; userName: string }>({
        isOpen: false,
        userId: null,
        userName: ''
    });
    const [forceDeleteModal, setForceDeleteModal] = useState<{ isOpen: boolean; userId: number | null; userName: string }>({
        isOpen: false,
        userId: null,
        userName: ''
    });

    const handleCreateUser = () => {
        setIsShowCreateModal(true);
    };

    const handleViewUser = (userId: number, role: string) => {
        if (role === 'student') {
            router.visit(route('users.show.student', { user: userId }));
        } else if (role === 'teacher') {
            router.visit(route('users.show.teacher', { user: userId }));
        }

    };

    const canViewUser = (role: string) => {
        return ['student', 'teacher'].includes(role);
    }

    const handleToggleStatus = (userId: number) => {
        router.patch(route('users.toggle-status', { user: userId }), {}, {
            preserveScroll: true,
        });
    };

    const handleDeleteUser = (userId: number) => {
        if (!deleteModal.userId) return;
        router.delete(route('users.destroy', { user: userId }), {
            onFinish: () => setDeleteModal({ isOpen: false, userId: null, userName: '' })
        });
    };

    const handleRestoreUser = (userId: number) => {
        router.post(route('users.restore', { id: userId }), {}, {
            preserveScroll: true,
        });
    };

    const handleForceDeleteUser = (userId: number) => {
        if (!forceDeleteModal.userId) return;
        router.delete(route('users.force-delete', { id: userId }), {
            onFinish: () => setForceDeleteModal({ isOpen: false, userId: null, userName: '' })
        });
    };


    const dataTableConfig: DataTableConfig<User> = {
        columns: [
            {
                key: 'name',
                label: 'Utilisateur',
                render: (user) => (
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="text-sm font-medium text-gray-900">{user.name}</span>
                            {user.deleted_at && (
                                <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                    Supprimé
                                </span>
                            )}
                        </div>
                        <div className="text-sm text-gray-500">{user.email}</div>
                    </div>
                )
            },
            {
                key: 'role',
                label: 'Rôle',
                render: (user) => (
                    (user?.roles?.length ?? 0) > 0 ? (
                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getRoleColor(user.roles?.[0]?.name ?? '')}`}>
                            {getRoleLabel(user.roles?.[0]?.name ?? '')}
                        </span>
                    ) : null
                )
            },
            {
                key: 'status',
                label: 'Statut',
                render: (user) => (
                    <div className="flex items-center gap-2">
                        {user.deleted_at ? (
                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Supprimé
                            </span>
                        ) : (
                            <Toggle
                                checked={user.is_active}
                                onChange={() => handleToggleStatus(user.id)}
                                size="md"
                                color="green"
                                showLabel={true}
                                activeLabel="Actif"
                                inactiveLabel="Inactif"
                            />
                        )}
                    </div>
                )
            },
            {
                key: 'created_at',
                label: 'Date de création',
                render: (user) => (
                    <span className="text-sm text-gray-500">{formatDate(user.created_at)}</span>
                )
            },
            {
                key: 'actions',
                label: 'Actions',
                render: (user) => (
                    <div className="flex items-center gap-2">
                        {user.deleted_at ? (
                            <>
                                <Button
                                    onClick={() => handleRestoreUser(user.id)}
                                    color="success"
                                    size="sm"
                                    variant='outline'
                                    title="Restaurer l'utilisateur"
                                >
                                    <ArrowPathIcon className="h-4 w-4" />
                                </Button>
                                {canDeleteUsers && (
                                    <Button
                                        onClick={() => setForceDeleteModal({
                                            isOpen: true,
                                            userId: user.id,
                                            userName: user.name
                                        })}
                                        color="danger"
                                        size="sm"
                                        variant='outline'
                                        title="Supprimer définitivement"
                                    >
                                        <TrashIcon className="h-4 w-4" />
                                    </Button>
                                )}
                            </>
                        ) : (
                            <>
                                {canViewUser(user.roles?.length && user.roles[0] ? user.roles[0].name : ''

                                ) && (<Button
                                    onClick={() => handleViewUser(user.id, user.roles?.length && user.roles[0] ? user.roles[0].name : '')}
                                    color="secondary"
                                    size="sm"
                                    variant='outline'
                                >
                                    Voir
                                </Button>)}
                                {canDeleteUsers && !canViewUser(user.roles?.length && user.roles[0] ? user.roles[0].name : '') && (
                                    <Button
                                        onClick={() => setDeleteModal({
                                            isOpen: true,
                                            userId: user.id,
                                            userName: user.name
                                        })}
                                        color="danger"
                                        size="sm"
                                        variant='outline'
                                        title="Supprimer l'utilisateur"
                                    >
                                        <TrashIcon className="h-4 w-4" />
                                    </Button>
                                )}
                            </>
                        )}
                    </div>
                )
            }
        ],
        searchPlaceholder: 'Rechercher par nom ou email...',
        filters: [
            {
                key: 'role',
                type: 'select',
                label: 'Filtrer par rôle',
                options: [{ label: 'Tous les rôles', value: '' }].concat(roles.map(role => ({ label: getRoleLabel(role), value: role })))
            },
            {
                key: 'status',
                type: 'select',
                label: 'Statut',
                options: [
                    { label: 'Tous', value: '' },
                    { label: 'Actifs', value: 'active' },
                    { label: 'Inactifs', value: 'inactive' }
                ]
            },
            {
                key: 'include_deleted',
                type: 'select',
                label: 'Afficher',
                options: [
                    { label: 'Actifs uniquement', value: '' },
                    { label: 'Inclure supprimés', value: '1' }
                ]
            }
        ],
        emptyState: {
            title: 'Aucun utilisateur trouvé',
            subtitle: 'Essayez de modifier vos critères de recherche',
            icon: 'UserIcon'
        },
        emptySearchState: {
            title: 'Aucun utilisateur trouvé',
            subtitle: 'Aucun utilisateur ne correspond à vos critères de recherche ou de filtre.',
            resetLabel: 'Réinitialiser les filtres'
        },
        perPageOptions: [10, 25, 50]
    };

    return (
        <AuthenticatedLayout title="Gestion des utilisateurs"
            breadcrumb={breadcrumbs.adminUsers()}
        >

            <CreateUser
                roles={roles}
                groups={groups}
                isOpen={isShowCreateModal}
                onClose={() => setIsShowCreateModal(false)}
            />
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <StatCard
                    title="Total utilisateurs"
                    value={users.total}
                    icon={UserGroupIcon}
                    color="blue"
                />
                <StatCard
                    title="Étudiants"
                    value={users.data.filter(user => user.roles?.some(role => role.name === 'student')).length}
                    icon={UserGroupIcon}
                    color="green"
                />
                <StatCard
                    title="Enseignants"
                    value={users.data.filter(user => user.roles?.some(role => role.name === 'teacher')).length}
                    icon={UserGroupIcon}
                    color="purple"
                />

                <StatCard
                    title="Administrateurs"
                    value={users.data.filter(user => user.roles?.some(role => role.name === 'admin')).length}
                    icon={UserGroupIcon}
                    color="red"
                />
            </div>
            <Section title="Liste des utilisateurs"
                actions={
                    <Button onClick={handleCreateUser} color="secondary" variant='outline' size='sm'>
                        Créer un utilisateur
                    </Button>
                }
            >
                <DataTable
                    data={users}
                    config={dataTableConfig}
                />
            </Section>

            <ConfirmationModal
                isOpen={deleteModal.isOpen}
                onClose={() => setDeleteModal({ isOpen: false, userId: null, userName: '' })}
                onConfirm={() => deleteModal.userId && handleDeleteUser(deleteModal.userId)}
                title="Supprimer l'utilisateur"
                message={`Êtes-vous sûr de vouloir supprimer l'utilisateur "${deleteModal.userName}" ?`}
                confirmText="Supprimer"
                cancelText="Annuler"
                type="danger"
            />

            <ConfirmationModal
                isOpen={forceDeleteModal.isOpen}
                onClose={() => setForceDeleteModal({ isOpen: false, userId: null, userName: '' })}
                onConfirm={() => forceDeleteModal.userId && handleForceDeleteUser(forceDeleteModal.userId)}
                title="Suppression définitive"
                message={`Êtes-vous sûr de vouloir supprimer DÉFINITIVEMENT l'utilisateur "${forceDeleteModal.userName}" ? Cette action est irréversible.`}
                confirmText="Supprimer définitivement"
                cancelText="Annuler"
                type="danger"
            />
        </AuthenticatedLayout>
    );
}