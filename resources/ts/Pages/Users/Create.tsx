import { useForm } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { getRoleLabel, trans } from '@/utils';
import { Button, Modal, Select } from '@/Components';
import { Input } from '@examena/ui';
import { Group } from '@/types';

interface Props {
    roles: string[];
    groups: Group[];
    isOpen: boolean;
    onClose: () => void;
}

export default function CreateUser({ roles, groups, isOpen, onClose }: Props) {
    const { data, setData, post, processing, errors } = useForm<{
        name: string;
        email: string;
        role: string;
        group_id: number | null;
    }>({
        name: '',
        email: '',
        role: 'student',
        group_id: null,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('users.store'), {
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
            group_id: null,
        });
    };

    // Filtrer les groupes actifs uniquement
    const activeGroups = groups.filter(group => group.is_active);

    const searchPlaceholder = trans('components.select.search_placeholder');
    const noOptionFound = trans('components.select.no_option_found');


    return (
        <Modal isOpen={isOpen} size='2xl' onClose={onClose} isCloseableInside={false}>
            <div className="p-6">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">
                        {trans('admin_pages.users.create_title')}
                    </h1>
                    <p className="text-gray-600 mt-1">
                        {trans('admin_pages.users.create_subtitle')}
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Input
                        label={trans('admin_pages.users.name_label')}
                        type="text"
                        value={data.name}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('name', e.target.value)}
                        placeholder={trans('admin_pages.users.name_placeholder')}
                        required
                        error={errors.name}
                    />

                    <Input
                        label={trans('admin_pages.users.email_label')}
                        type="email"
                        value={data.email}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('email', e.target.value)}
                        placeholder={trans('admin_pages.users.email_placeholder')}
                        required
                        error={errors.email}
                    />

                    <Select

                        label={trans('admin_pages.users.role')}
                        noOptionFound={noOptionFound}
                        searchPlaceholder={searchPlaceholder}
                        options={roles.map(role => ({
                            value: role,
                            label: getRoleLabel(role)
                        }))}
                        value={data.role}
                        onChange={(value) => setData('role', String(value))}
                        error={errors.role}
                        searchable={false}
                        placeholder={trans('admin_pages.users.select_role')}
                    />

                    {data.role === 'student' && (
                        <Select
                            label={trans('admin_pages.users.group')}
                            noOptionFound={noOptionFound}
                            searchPlaceholder={searchPlaceholder}
                            options={activeGroups.map(group => ({
                                value: group.id,
                                label: `${group.display_name} (${group.academic_year})`
                            }))}
                            value={data.group_id ?? ''}
                            onChange={(value) => setData('group_id', Number(value))}
                            error={errors.group_id}
                            searchable={true}
                            placeholder={trans('admin_pages.users.select_group')}
                        />
                    )}

                    <div className="bg-blue-50 border-l-4 border-blue-400 p-4">
                        <div className="flex">
                            <div className="shrink-0">
                                <svg className="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="ml-3">
                                <p className="text-sm text-blue-700">
                                    {trans('admin_pages.users.password_info')}
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
                            {trans('admin_pages.common.cancel')}
                        </Button>
                        <Button
                            type="submit"
                            color="primary"
                            loading={processing}
                            disabled={processing}
                        >
                            {processing ? (
                                trans('admin_pages.common.loading')
                            ) : (
                                trans('admin_pages.users.create_button')
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </Modal>
    );
}