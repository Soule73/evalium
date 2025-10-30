import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import { formatDate, getRoleLabel } from '@/utils/formatters';
import Section from '@/Components/Section';
import TextEntry from '@/Components/TextEntry';
import Toggle from '@/Components/form/Toggle';
import { User } from '@/types';
import { useState } from 'react';
import EditUser from './Edit';
import { route } from 'ziggy-js';
import { router } from '@inertiajs/react';
import ConfirmationModal from '@/Components/ConfirmationModal';
import { ExclamationTriangleIcon } from '@heroicons/react/16/solid';
import { BreadcrumbItem } from '@/Components/Breadcrumb';


interface Props {
    user: User;
    children?: React.ReactNode;
    canDelete?: boolean;
    canToggleStatus?: boolean;
    breadcrumb?: BreadcrumbItem[] | undefined;
}

export default function ShowUser({ user, children, canDelete, canToggleStatus, breadcrumb }: Props) {
    const [isShowUpdateModal, setIsShowUpdateModal] = useState(false);

    const [isShowDeleteModal, setIsShowDeleteModal] = useState(false);
    const [deleteInProgress, setDeleteInProgress] = useState(false);

    const handleEdit = () => {
        setIsShowUpdateModal(true);
    };

    const handleBack = () => {
        router.visit(route('users.index'));
    };

    const handleDelete = () => {
        setIsShowDeleteModal(true);
    };

    const handleToggleStatus = () => {
        router.patch(route('users.toggle-status', { user: user.id }), {}, {
            preserveScroll: true
        });
    };

    const onConfirmDeleteUser = () => {
        if (user) {
            setDeleteInProgress(true);
            router.delete(route('users.destroy', { user: user.id }), {
                preserveScroll: true,
                onSuccess: () => {
                    setIsShowDeleteModal(false);
                    setDeleteInProgress(false);
                },
                onError: () => {
                    setIsShowDeleteModal(false);
                    setDeleteInProgress(false);
                }
            });
        }
    };


    const userRole = (user.roles?.length ?? 0) > 0 ? user.roles![0].name : null;

    return (
        <AuthenticatedLayout title={`Utilisateur : ${user.name}`}
            breadcrumb={breadcrumb}
        >
            <ConfirmationModal
                isOpen={isShowDeleteModal}
                isCloseableInside={true}
                type='danger'
                title="Confirmer la suppression"
                message={`Êtes-vous sûr de vouloir supprimer l'utilisateur "${user?.name}" ?`}
                icon={ExclamationTriangleIcon}
                confirmText="Supprimer"
                cancelText="Annuler"
                onConfirm={() => onConfirmDeleteUser()}
                onClose={() => setIsShowDeleteModal(false)}
                loading={deleteInProgress}
            >
                <p className='text-sm text-gray-500 mb-6'> Cette action est irréversible.</p>
            </ConfirmationModal>
            {user && (
                <EditUser
                    route={route('users.update', user.id)}
                    isOpen={isShowUpdateModal}
                    onClose={() => {
                        setIsShowUpdateModal(false);
                    }}
                    user={user}
                    userRole={userRole || null}
                />
            )}
            <Section title="Profil utilisateur" subtitle="Informations personnelles de l'utilisateur"
                actions={
                    <div className="flex space-x-3">
                        <Button
                            onClick={handleBack}
                            variant='outline'
                            size='sm'
                            color="secondary">
                            Retour
                        </Button>
                        <Button
                            onClick={handleEdit}
                            size='sm'
                            color="primary">
                            Modifier
                        </Button>
                        {canDelete && (
                            <Button
                                onClick={handleDelete}
                                size='sm'
                                color="danger">
                                Supprimer
                            </Button>
                        )}
                    </div>
                }
            >

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <TextEntry
                        label="Nom complet"
                        value={user.name}
                    />

                    <TextEntry
                        label="Adresse email"
                        value={user.email}
                    />

                    <TextEntry
                        label="Rôle"
                        value={userRole ? getRoleLabel(userRole) : '-'}
                    />

                    {canToggleStatus ? (
                        <div className="flex flex-col gap-2">
                            <label className="text-sm font-medium text-gray-700">
                                Statut du compte
                            </label>
                            <Toggle
                                checked={user.is_active}
                                onChange={handleToggleStatus}
                                activeLabel="Actif"
                                inactiveLabel="Inactif"
                                showLabel={true}
                            />
                        </div>
                    ) : (
                        <TextEntry
                            label="Statut du compte"
                            value={user.is_active ? 'Actif' : 'Inactif'}
                        />
                    )}

                    <TextEntry
                        label="Membre depuis"
                        value={formatDate(user.created_at)}
                    />

                    <TextEntry
                        label="Dernière modification"
                        value={formatDate(user.updated_at)}
                    />
                </div>

            </Section>
            {children}
        </AuthenticatedLayout >
    );
}