import { type User } from '@/types';
import { usePage } from '@inertiajs/react';
import { useState, useMemo } from 'react';

const useProfile = ({ user }: { user: User }) => {
    const [isShowUpdateModal, setIsShowUpdateModal] = useState(false);
    const { locale } = usePage<{ locale: string }>().props;

    const handleEdit = () => {
        setIsShowUpdateModal(true);
    };

    const userRole = useMemo(
        () => ((user.roles?.length ?? 0) > 0 ? user.roles![0].name : null),
        [user.roles],
    );

    return {
        isShowUpdateModal,
        setIsShowUpdateModal,
        locale,
        handleEdit,
        userRole,
    };
};

export default useProfile;
