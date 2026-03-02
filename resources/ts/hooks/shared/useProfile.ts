import { type User } from '@evalium/utils/types';
import { usePage } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { getUserPrimaryRole } from '@evalium/utils/helpers/permissions';

const useProfile = ({ user }: { user: User }) => {
    const [isShowUpdateModal, setIsShowUpdateModal] = useState(false);
    const { locale } = usePage<{ locale: string }>().props;

    const handleEdit = () => {
        setIsShowUpdateModal(true);
    };

    const userRole = useMemo(() => getUserPrimaryRole(user), [user]);

    return {
        isShowUpdateModal,
        setIsShowUpdateModal,
        locale,
        handleEdit,
        userRole,
    };
};

export default useProfile;
