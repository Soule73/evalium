import { useMemo } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type User } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import UserBaseInfo from '@/Components/features/users/UserBaseInfo';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';

interface Props {
    user: User;
    children?: React.ReactNode;
    canDelete?: boolean;
    canToggleStatus?: boolean;
    backRoute?: string;
}

export default function ShowUser({
    user,
    canDelete,
    canToggleStatus,
    backRoute = 'admin.users.index',
}: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const userTitle = useMemo(
        () => t('admin_pages.users.user_title', { name: user.name }),
        [t, user.name],
    );

    return (
        <AuthenticatedLayout title={userTitle} breadcrumb={breadcrumbs.admin.adminShow({ name: user.name })}>
            <UserBaseInfo
                user={user}
                canDelete={canDelete}
                canToggleStatus={canToggleStatus}
                backRoute={backRoute}
            />
        </AuthenticatedLayout>
    );
}
