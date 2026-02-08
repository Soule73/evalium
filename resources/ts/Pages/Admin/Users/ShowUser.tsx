import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { formatDate, getRoleLabel } from '@/utils';
import { User } from '@/types';
import { useState } from 'react';
import EditUser from './Edit';
import { route } from 'ziggy-js';
import { router } from '@inertiajs/react';
import { ExclamationTriangleIcon } from '@heroicons/react/16/solid';
import { trans } from '@/utils';
import { ConfirmationModal, Section, Button, TextEntry } from '@/Components';
import { Toggle } from '@examena/ui';
import { BreadcrumbItem } from '@/Components/layout/Breadcrumb';


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
        router.visit(route('admin.users.index'));
    };

    const handleDelete = () => {
        setIsShowDeleteModal(true);
    };

    const handleToggleStatus = () => {
        router.patch(route('admin.users.toggle-status', { user: user.id }), {}, {
            preserveScroll: true
        });
    };

    const onConfirmDeleteUser = () => {
        if (user) {
            setDeleteInProgress(true);
            router.delete(route('admin.users.destroy', { user: user.id }), {
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
        <AuthenticatedLayout title={trans('admin_pages.users.user_title', { name: user.name })}
            breadcrumb={breadcrumb}
        >
            <ConfirmationModal
                isOpen={isShowDeleteModal}
                isCloseableInside={true}
                type='danger'
                title={trans('admin_pages.users.delete_confirm_title')}
                message={trans('admin_pages.users.delete_confirm_message', { name: user?.name })}
                icon={ExclamationTriangleIcon}
                confirmText={trans('admin_pages.common.delete')}
                cancelText={trans('admin_pages.common.cancel')}
                onConfirm={() => onConfirmDeleteUser()}
                onClose={() => setIsShowDeleteModal(false)}
                loading={deleteInProgress}
            >
                <p className='text-sm text-gray-500 mb-6'> {trans('admin_pages.users.delete_irreversible')}</p>
            </ConfirmationModal>
            {user && (
                <EditUser
                    route={route('admin.users.update', user.id)}
                    isOpen={isShowUpdateModal}
                    onClose={() => {
                        setIsShowUpdateModal(false);
                    }}
                    user={user}
                    userRole={userRole || null}
                />
            )}
            <Section title={trans('admin_pages.users.user_profile')} subtitle={trans('admin_pages.users.user_profile_subtitle')}
                actions={
                    <div className="flex space-x-3">
                        <Button
                            onClick={handleBack}
                            variant='outline'
                            size='sm'
                            color="secondary">
                            {trans('admin_pages.users.back')}
                        </Button>
                        <Button
                            onClick={handleEdit}
                            size='sm'
                            color="primary">
                            {trans('admin_pages.users.modify')}
                        </Button>
                        {canDelete && (
                            <Button
                                onClick={handleDelete}
                                size='sm'
                                color="danger">
                                {trans('admin_pages.common.delete')}
                            </Button>
                        )}
                    </div>
                }
            >

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <TextEntry
                        label={trans('admin_pages.users.full_name')}
                        value={user.name}
                    />

                    <TextEntry
                        label={trans('admin_pages.users.email_address')}
                        value={user.email}
                    />

                    <TextEntry
                        label={trans('admin_pages.users.role')}
                        value={userRole ? getRoleLabel(userRole) : '-'}
                    />

                    {canToggleStatus ? (
                        <div className="flex flex-col gap-2">
                            <label className="text-sm font-medium text-gray-700">
                                {trans('admin_pages.users.account_status')}
                            </label>
                            <Toggle
                                checked={user.is_active}
                                onChange={handleToggleStatus}
                                activeLabel={trans('admin_pages.users.active_status')}
                                inactiveLabel={trans('admin_pages.users.inactive_status')}
                                showLabel={true}
                            />
                        </div>
                    ) : (
                        <TextEntry
                            label={trans('admin_pages.users.account_status')}
                            value={user.is_active ? trans('admin_pages.users.active_status') : trans('admin_pages.users.inactive_status')}
                        />
                    )}

                    <TextEntry
                        label={trans('admin_pages.users.member_since')}
                        value={formatDate(user.created_at)}
                    />

                    <TextEntry
                        label={trans('admin_pages.users.last_modified')}
                        value={formatDate(user.updated_at)}
                    />
                </div>

            </Section>
            {children}
        </AuthenticatedLayout >
    );
}