import { useMemo } from 'react';
import { useForm } from '@inertiajs/react';
import { type User } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button, Modal } from '@/Components';
import { Input } from '@evalium/ui';
import UserFormFields from './UserFormFields';

interface EditUserProps {
    user: User;
    route: string;
    title?: string;
    description?: string;
    roles?: string[];
    userRole: string | null;
    isOpen: boolean;
    onClose: () => void;
}

export default function EditUserModal({
    user,
    roles,
    userRole,
    isOpen,
    onClose,
    title,
    description,
    route,
}: EditUserProps) {
    const { t } = useTranslations();

    const { data, setData, put, processing, errors } = useForm({
        id: user.id,
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
        role: userRole || 'student',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        put(route, {
            onSuccess: () => {
                onClose();
            },
        });
    };

    const handleCancel = () => {
        onClose();
        setData({
            id: user.id,
            name: user.name,
            email: user.email,
            password: '',
            password_confirmation: '',
            role: userRole || 'student',
        });
    };

    const editTitleKey = useMemo(() => {
        if (title) {
            return null;
        }
        switch (userRole) {
            case 'teacher':
                return 'admin_pages.users.edit_teacher_title';
            case 'student':
                return 'admin_pages.users.edit_student_title';
            case 'admin':
            case 'super_admin':
                return 'admin_pages.users.edit_admin_title';
            default:
                return 'admin_pages.users.edit_title';
        }
    }, [title, userRole]);

    const translations = useMemo(
        () => ({
            editTitle:
                title || (editTitleKey ? t(editTitleKey) : t('admin_pages.users.edit_title')),
            passwordChange: t('admin_pages.users.password_change'),
            passwordKeep: t('admin_pages.users.password_keep'),
            passwordConfirmPlaceholder: t('admin_pages.users.password_confirm_placeholder'),
            cancel: t('commons/ui.cancel'),
            updating: t('admin_pages.users.updating'),
            updateButton: t('admin_pages.users.update_button'),
        }),
        [t, title, editTitleKey],
    );

    const editSubtitle = useMemo(
        () => description || t('admin_pages.users.edit_subtitle', { name: user.name }),
        [t, description, user.name],
    );

    return (
        <Modal isOpen={isOpen} size="2xl" onClose={onClose} isCloseableInside={false}>
            <div className="p-6 md:min-w-lg lg:min-w-xl w-full ">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">{translations.editTitle}</h1>
                    <p className="text-gray-600 mt-1">{editSubtitle}</p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <UserFormFields
                        data={{ name: data.name, email: data.email, role: data.role }}
                        errors={errors}
                        onChange={(field, value) => setData(field, value)}
                        roles={roles}
                        hideRoleSelect={!roles}
                    />

                    <div className="relative">
                        <div className="absolute inset-0 flex items-center">
                            <div className="w-full border-t border-gray-300" />
                        </div>
                        <div className="relative flex justify-center text-sm">
                            <span className="px-2 bg-white text-gray-500">
                                {translations.passwordChange}
                            </span>
                        </div>
                    </div>

                    <Input
                        id="password"
                        label={translations.passwordKeep}
                        type="password"
                        value={data.password}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setData('password', e.target.value)
                        }
                        placeholder={translations.passwordKeep}
                    />

                    <Input
                        id="password_confirmation"
                        label={translations.passwordConfirmPlaceholder}
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setData('password_confirmation', e.target.value)
                        }
                        placeholder={translations.passwordConfirmPlaceholder}
                    />
                    <div className="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                        <Button
                            type="button"
                            color="secondary"
                            variant="outline"
                            size="sm"
                            onClick={handleCancel}
                        >
                            {translations.cancel}
                        </Button>
                        <Button
                            type="submit"
                            color="primary"
                            size="sm"
                            loading={processing}
                            disabled={processing}
                        >
                            {processing ? translations.updating : translations.updateButton}
                        </Button>
                    </div>
                </form>
            </div>
        </Modal>
    );
}
