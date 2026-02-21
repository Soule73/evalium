import { ConfirmationModal, Section, Button, TextEntry, Toggle } from '@/Components/ui';
import { useTranslations } from '@/hooks';
import { useFormatters } from '@/hooks/shared/useFormatters';
import { type User } from '@/types';
import { formatDate } from '@/utils';
import { ExclamationTriangleIcon } from '@heroicons/react/24/outline';
import { router } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { route } from 'ziggy-js';
import EditUserModal from './EditUserModal';

interface UserBaseInfoProps {
    user: User;
    children?: React.ReactNode;
    canDelete?: boolean;
    canToggleStatus?: boolean;
    backRoute?: string;
}

export default function UserBaseInfo({
    user,
    children,
    canDelete,
    canToggleStatus,
    backRoute = 'admin.users.index',
}: UserBaseInfoProps) {
    const { t } = useTranslations();
    const { getRoleLabel } = useFormatters();

    const [isShowUpdateModal, setIsShowUpdateModal] = useState(false);

    const [isShowDeleteModal, setIsShowDeleteModal] = useState(false);
    const [deleteInProgress, setDeleteInProgress] = useState(false);

    const handleEdit = () => {
        setIsShowUpdateModal(true);
    };

    const handleBack = () => {
        router.visit(route(backRoute));
    };

    const handleDelete = () => {
        setIsShowDeleteModal(true);
    };

    const handleToggleStatus = () => {
        router.patch(
            route('admin.users.toggle-status', { user: user.id }),
            {},
            {
                preserveScroll: true,
            },
        );
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
                },
            });
        }
    };

    const userRole = (user.roles?.length ?? 0) > 0 ? user.roles![0].name : null;

    const profileTitleKey = useMemo(() => {
        switch (userRole) {
            case 'teacher':
                return 'admin_pages.users.teacher_profile';
            case 'student':
                return 'admin_pages.users.student_profile';
            case 'admin':
            case 'super_admin':
                return 'admin_pages.users.admin_profile';
            default:
                return 'admin_pages.users.user_profile';
        }
    }, [userRole]);

    const profileSubtitleKey = useMemo(() => {
        switch (userRole) {
            case 'teacher':
                return 'admin_pages.users.teacher_profile_subtitle';
            case 'student':
                return 'admin_pages.users.student_profile_subtitle';
            case 'admin':
            case 'super_admin':
                return 'admin_pages.users.admin_profile_subtitle';
            default:
                return 'admin_pages.users.user_profile_subtitle';
        }
    }, [userRole]);

    const translations = useMemo(
        () => ({
            deleteConfirmTitle: t('admin_pages.users.delete_confirm_title'),
            delete: t('admin_pages.common.delete'),
            cancel: t('admin_pages.common.cancel'),
            deleteIrreversible: t('admin_pages.users.delete_irreversible'),
            profileTitle: t(profileTitleKey),
            profileSubtitle: t(profileSubtitleKey),
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
        }),
        [t, profileTitleKey, profileSubtitleKey],
    );

    const deleteConfirmMessage = useMemo(
        () => t('admin_pages.users.delete_confirm_message', { name: user?.name }),
        [t, user?.name],
    );

    return (
        <>
            <ConfirmationModal
                isOpen={isShowDeleteModal}
                isCloseableInside={true}
                type="danger"
                title={translations.deleteConfirmTitle}
                message={deleteConfirmMessage}
                icon={ExclamationTriangleIcon}
                confirmText={translations.delete}
                cancelText={translations.cancel}
                onConfirm={() => onConfirmDeleteUser()}
                onClose={() => setIsShowDeleteModal(false)}
                loading={deleteInProgress}
            >
                <p className="text-sm text-gray-500 mb-6"> {translations.deleteIrreversible}</p>
            </ConfirmationModal>
            {user && (
                <EditUserModal
                    route={route('admin.users.update', user.id)}
                    isOpen={isShowUpdateModal}
                    onClose={() => {
                        setIsShowUpdateModal(false);
                    }}
                    user={user}
                    userRole={userRole || null}
                />
            )}
            <Section
                title={translations.profileTitle}
                subtitle={translations.profileSubtitle}
                actions={
                    <div className="flex space-x-3">
                        <Button onClick={handleBack} variant="outline" size="sm" color="secondary">
                            {translations.back}
                        </Button>
                        <Button onClick={handleEdit} size="sm" color="primary">
                            {translations.modify}
                        </Button>
                        {canDelete && (
                            <Button onClick={handleDelete} size="sm" color="danger">
                                {translations.delete}
                            </Button>
                        )}
                    </div>
                }
            >
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <TextEntry label={translations.fullName} value={user.name} />

                    <TextEntry label={translations.emailAddress} value={user.email} />

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
                            value={
                                user.is_active
                                    ? translations.activeStatus
                                    : translations.inactiveStatus
                            }
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
        </>
    );
}
