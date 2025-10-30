import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import Section from '@/Components/Section';
import { DataTable } from '@/Components/DataTable';
import { route } from 'ziggy-js';
import { ShieldCheckIcon } from '@heroicons/react/24/outline';
import Badge from '@/Components/Badge';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { useState } from 'react';
import ConfirmationModal from '@/Components/ConfirmationModal';

interface Permission {
    id: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
    guard_name: string;
    permissions_count: number;
    permissions: Permission[];
    created_at: string;
    updated_at: string;
}

interface Props {
    roles: Role[];
    allPermissions: Permission[];
}

export default function RoleIndex({ roles }: Props) {
    const [deleteModal, setDeleteModal] = useState<{ isOpen: boolean; roleId: number | null; roleName: string }>({
        isOpen: false,
        roleId: null,
        roleName: ''
    });

    const handleCreate = () => {
        router.visit(route('roles.create'));
    };

    const handleEdit = (roleId: number) => {
        router.visit(route('roles.edit', { role: roleId }));
    };

    const handleDelete = (roleId: number) => {
        if (!deleteModal.roleId) return;
        router.delete(route('roles.destroy', { role: roleId }), {
            onFinish: () => setDeleteModal({ isOpen: false, roleId: null, roleName: '' })
        });
    };

    const isSystemRole = (roleName: string) => {
        return ['super_admin', 'admin', 'teacher', 'student'].includes(roleName);
    };

    const getRoleLabel = (name: string) => {
        const labels: Record<string, string> = {
            'super_admin': 'Super Administrateur',
            'admin': 'Administrateur',
            'teacher': 'Enseignant',
            'student': 'Étudiant',
        };
        return labels[name] || name;
    };

    const rolesData: PaginationType<Role> = {
        data: roles,
        current_page: 1,
        last_page: 1,
        per_page: roles.length,
        total: roles.length,
        from: 1,
        to: roles.length,
        first_page_url: '',
        last_page_url: '',
        next_page_url: null,
        prev_page_url: null,
        path: '',
        links: [],
    };

    const dataTableConfig: DataTableConfig<Role> = {
        columns: [
            {
                key: 'name',
                label: 'Nom du rôle',
                render: (role) => (
                    <div className="flex items-center gap-2">
                        <ShieldCheckIcon className="w-5 h-5 text-blue-600" />
                        <div>
                            <div className="text-sm font-medium text-gray-900">{getRoleLabel(role.name)}</div>
                            <div className="text-xs text-gray-500">{role.name}</div>
                        </div>
                        {isSystemRole(role.name) && (
                            <Badge label="Système" type="info" />
                        )}
                    </div>
                ),
                sortable: true,
            },
            {
                key: 'permissions',
                label: 'Permissions',
                render: (role) => (
                    <div>
                        <div className="text-sm font-medium text-gray-900">{role.permissions_count} permissions</div>
                        <div className="text-xs text-gray-500 truncate max-w-md">
                            {role.permissions.slice(0, 3).map(p => p.name).join(', ')}
                            {role.permissions.length > 3 && ` +${role.permissions.length - 3} autres`}
                        </div>
                    </div>
                ),
            },
            {
                key: 'actions',
                label: 'Actions',
                render: (role) => (
                    <div className="flex gap-2">
                        <Button
                            onClick={() => handleEdit(role.id)}
                            size="sm"
                            color="primary"
                            variant="outline"
                        >
                            {isSystemRole(role.name) ? 'Voir' : 'Modifier'}
                        </Button>
                        {!isSystemRole(role.name) && (
                            <Button
                                onClick={() => setDeleteModal({
                                    isOpen: true,
                                    roleId: role.id,
                                    roleName: getRoleLabel(role.name)
                                })}
                                size="sm"
                                color="danger"
                            >
                                Supprimer
                            </Button>
                        )}
                    </div>
                ),
            },
        ],
    };

    return (
        <AuthenticatedLayout title="Gestion des rôles & permissions"
            breadcrumb={breadcrumbs.adminRoles()}
        >
            <Section
                title="Rôles & Permissions"
                subtitle="Gérer les rôles et leurs permissions"
                actions={
                    <Button onClick={handleCreate} color="primary" size='sm'>
                        Nouveau rôle
                    </Button>
                }
            >
                <DataTable
                    config={dataTableConfig}
                    data={rolesData}
                />
            </Section>

            <ConfirmationModal
                isOpen={deleteModal.isOpen}
                onClose={() => setDeleteModal({ isOpen: false, roleId: null, roleName: '' })}
                onConfirm={() => deleteModal.roleId && handleDelete(deleteModal.roleId)}
                title="Supprimer le rôle"
                message={`Êtes-vous sûr de vouloir supprimer le rôle "${deleteModal.roleName}" ?`}
                confirmText="Supprimer"
                cancelText="Annuler"
                type="danger"
            />
        </AuthenticatedLayout>
    );
}
