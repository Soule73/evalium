import { useMemo, useState } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { formatDate, getRoleLabel } from '@/utils';
import { type User } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import EditUser from './Edit';
import { route } from 'ziggy-js';
import { router } from '@inertiajs/react';
import { ExclamationTriangleIcon } from '@heroicons/react/16/solid';
import { ConfirmationModal, Section, Button, TextEntry } from '@/Components';
import { Toggle } from '@examena/ui';
import { type BreadcrumbItem } from '@/Components/layout/Breadcrumb';


interface Props {
    user: User;
    children?: React.ReactNode;
    canDelete?: boolean;
    canToggleStatus?: boolean;
    breadcrumb?: BreadcrumbItem[] | undefined;
}

export default function ShowUser({ user, children, canDelete, canToggleStatus, breadcrumb }: Props) {
    const { t } = useTranslations();

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

    const translations = useMemo(() => ({
        deleteConfirmTitle: t('admin_pages.users.delete_confirm_title'),
        delete: t('admin_pages.common.delete'),
        cancel: t('admin_pages.common.cancel'),
        deleteIrreversible: t('admin_pages.users.delete_irreversible'),
        userProfile: t('admin_pages.users.user_profile'),
        userProfileSubtitle: t('admin_pages.users.user_profile_subtitle'),
        back: t('admin_pages.users.back'),
        modify: t('admin_pages.users.modify'),
        fullName: t('admin_pages.users.full_name'),
        emailAddress: t('admin_pages.users.email_address'),
        role: t('admin_pages.users.role'),
        accountStatus: t('admin_pages.users.account_status'),
        activeStatus: t('admin_pages.users.active_status'),
        inactiveStatus: t('admin_pages.users.inactive_status'),
        memberSince: t('admin_pages.users.member_since'),
        lastModified: t('admin_pages.users.last_modified'),
    }), [t]);

    const userTitle = useMemo(() => t('admin_pages.users.user_title', { name: user.name }), [t, user.name]);
    const deleteConfirmMessage = useMemo(() => t('admin_pages.users.delete_confirm_message', { name: user?.name }), [t, user?.name]);

    return (
        <AuthenticatedLayout title={userTitle}
            breadcrumb={breadcrumb}
        >
            <ConfirmationModal
                isOpen={isShowDeleteModal}
                isCloseableInside={true}
                type='danger'
                title={translations.deleteConfirmTitle}
                message={deleteConfirmMessage}
                icon={ExclamationTriangleIcon}
                confirmText={translations.delete}
                cancelText={translations.cancel}
                onConfirm={() => onConfirmDeleteUser()}
                onClose={() => setIsShowDeleteModal(false)}
                loading={deleteInProgress}
            >
                <p className='text-sm text-gray-500 mb-6'> {translations.deleteIrreversible}</p>
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
            <Section title={translations.userProfile} subtitle={translations.userProfileSubtitle}
                actions={
                    <div className="flex space-x-3">
                        <Button
                            onClick={handleBack}
                            variant='outline'
                            size='sm'
                            color="secondary">
                            {translations.back}
                        </Button>
                        <Button
                            onClick={handleEdit}
                            size='sm'
                            color="primary">
                            {translations.modify}
                        </Button>
                        {canDelete && (
                            <Button
                                onClick={handleDelete}
                                size='sm'
                                color="danger">
                                {translations.delete}
                            </Button>
                        )}
                    </div>
                }
            >

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <TextEntry
                        label={translations.fullName}
                        value={user.name}
                    />

                    <TextEntry
                        label={translations.emailAddress}
                        value={user.email}
                    />

                    <TextEntry
                        label={translations.role}
                        value={userRole ? getRoleLabel(userRole) : '-'}
                    />

                    {canToggleStatus ? (
                        <div className="flex flex-col gap-2">
                            <label className="text-sm font-medium text-gray-700">
                                {translations.accountStatus}
                            </label>
                            <Toggle
                                checked={user.is_active}
                                onChange={handleToggleStatus}
                                activeLabel={translations.activeStatus}
                                inactiveLabel={translations.inactiveStatus}
                                showLabel={true}
                            />
                        </div>
                    ) : (
                        <TextEntry
                            label={translations.accountStatus}
                            value={user.is_active ? translations.activeStatus : translations.inactiveStatus}
                        />
                    )}

                    <TextEntry
                        label={translations.memberSince}
                        value={formatDate(user.created_at)}
                    />

                    <TextEntry
                        label={translations.lastModified}
                        value={formatDate(user.updated_at)}
                    />
                </div>

            </Section>
            {children}
        </AuthenticatedLayout >
    );
}