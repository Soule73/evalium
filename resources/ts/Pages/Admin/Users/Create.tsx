import { useMemo } from 'react';
import { useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useFormatters } from '@/hooks/shared/useFormatters';
import { Button, Modal, Select } from '@/Components';
import { Input } from '@evalium/ui';

interface Props {
    roles: string[];
    isOpen: boolean;
    onClose: () => void;
}

export default function CreateUser({ roles, isOpen, onClose }: Props) {
    const { t } = useTranslations();
    const { getRoleLabel } = useFormatters();

    const { data, setData, post, processing, errors } = useForm<{
        name: string;
        email: string;
        role: string;
    }>({
        name: '',
        email: '',
        role: 'student',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.users.store'), {
            onSuccess: () => {
                onClose();
            }
        });
    };

    const handleCancel = () => {
        onClose();
        setData({
            name: '',
            email: '',
            role: 'student',
        });
    };

    const translations = useMemo(() => ({
        searchPlaceholder: t('components.select.search_placeholder'),
        noOptionFound: t('components.select.no_option_found'),
        createTitle: t('admin_pages.users.create_title'),
        createSubtitle: t('admin_pages.users.create_subtitle'),
        nameLabel: t('admin_pages.users.name_label'),
        namePlaceholder: t('admin_pages.users.name_placeholder'),
        emailLabel: t('admin_pages.users.email_label'),
        emailPlaceholder: t('admin_pages.users.email_placeholder'),
        role: t('admin_pages.users.role'),
        selectRole: t('admin_pages.users.select_role'),
        passwordInfo: t('admin_pages.users.password_info'),
        cancel: t('admin_pages.common.cancel'),
        loading: t('admin_pages.common.loading'),
        createButton: t('admin_pages.users.create_button'),
    }), [t]);


    return (
        <Modal isOpen={isOpen} size='2xl' onClose={onClose} isCloseableInside={false}>
            <div className="p-6">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">
                        {translations.createTitle}
                    </h1>
                    <p className="text-gray-600 mt-1">
                        {translations.createSubtitle}
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Input
                        label={translations.nameLabel}
                        type="text"
                        value={data.name}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('name', e.target.value)}
                        placeholder={translations.namePlaceholder}
                        required
                        error={errors.name}
                    />

                    <Input
                        label={translations.emailLabel}
                        type="email"
                        value={data.email}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('email', e.target.value)}
                        placeholder={translations.emailPlaceholder}
                        required
                        error={errors.email}
                    />

                    <Select

                        label={translations.role}
                        noOptionFound={translations.noOptionFound}
                        searchPlaceholder={translations.searchPlaceholder}
                        options={roles.map(role => ({
                            value: role,
                            label: getRoleLabel(role)
                        }))}
                        value={data.role}
                        onChange={(value) => setData('role', String(value))}
                        error={errors.role}
                        searchable={false}
                        placeholder={translations.selectRole}
                    />

                    <div className="bg-blue-50 border-l-4 border-blue-400 p-4">
                        <div className="flex">
                            <div className="shrink-0">
                                <svg className="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="ml-3">
                                <p className="text-sm text-blue-700">
                                    {translations.passwordInfo}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                        <Button
                            type="button"
                            color="secondary"
                            variant='outline'
                            onClick={handleCancel}
                        >
                            {translations.cancel}
                        </Button>
                        <Button
                            type="submit"
                            color="primary"
                            loading={processing}
                            disabled={processing}
                        >
                            {processing ? (
                                translations.loading
                            ) : (
                                translations.createButton
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </Modal>
    );
}