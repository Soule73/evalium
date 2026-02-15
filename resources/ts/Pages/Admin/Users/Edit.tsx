import { useMemo } from 'react';
import { useForm } from '@inertiajs/react';
import { type User } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useFormatters } from '@/hooks/shared/useFormatters';
import { Button, Modal, Select } from '@/Components';
import { Input } from '@evalium/ui';

interface Props {
    user: User;
    route: string;
    title?: string;
    description?: string;
    roles?: string[];
    userRole: string | null;
    isOpen: boolean;
    onClose: () => void;
}

export default function EditUser({
    user,
    roles,
    userRole,
    isOpen,
    onClose,
    title,
    description,
    route,
}: Props) {
    const { t } = useTranslations();
    const { getRoleLabel } = useFormatters();

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

    const translations = useMemo(
        () => ({
            searchPlaceholder: t('components.select.search_placeholder'),
            noOptionFound: t('components.select.no_option_found'),
            editTitle: t('admin_pages.users.edit_title'),
            role: t('admin_pages.users.role'),
            selectRole: t('admin_pages.users.select_role'),
            namePlaceholder: t('admin_pages.users.name_placeholder'),
            emailPlaceholder: t('admin_pages.users.email_placeholder'),
            passwordChange: t('admin_pages.users.password_change'),
            passwordKeep: t('admin_pages.users.password_keep'),
            passwordConfirmPlaceholder: t('admin_pages.users.password_confirm_placeholder'),
            cancel: t('admin_pages.common.cancel'),
            updating: t('admin_pages.users.updating'),
            updateButton: t('admin_pages.users.update_button'),
        }),
        [t],
    );

    const editSubtitle = useMemo(
        () => t('admin_pages.users.edit_subtitle', { name: user.name }),
        [t, user.name],
    );

    return (
        <Modal isOpen={isOpen} size="2xl" onClose={onClose} isCloseableInside={false}>
            <div className="p-6 md:min-w-lg lg:min-w-xl w-full ">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">
                        {title || translations.editTitle}
                    </h1>
                    <p className="text-gray-600 mt-1">{description || editSubtitle}</p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Input
                        id="name"
                        type="text"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setData('name', e.target.value)
                        }
                        placeholder={translations.namePlaceholder}
                        required
                    />

                    <Input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setData('email', e.target.value)
                        }
                        placeholder={translations.emailPlaceholder}
                        required
                    />
                    {roles && (
                        <div>
                            <Select
                                label={translations.role}
                                noOptionFound={translations.noOptionFound}
                                searchPlaceholder={translations.searchPlaceholder}
                                options={roles.map((role) => ({
                                    value: role,
                                    label: getRoleLabel(role),
                                }))}
                                value={data.role}
                                onChange={(value) => setData('role', String(value))}
                                error={errors.role}
                                searchable={false}
                                placeholder={translations.selectRole}
                            />
                        </div>
                    )}

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
                        type="password"
                        value={data.password}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setData('password', e.target.value)
                        }
                        placeholder={translations.passwordKeep}
                    />

                    <Input
                        id="password_confirmation"
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
