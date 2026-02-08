import { useForm } from '@inertiajs/react';
import { User } from '@/types';
import { getRoleLabel } from '@/utils';
import { trans } from '@/utils';
import { Button, Modal, Select } from '@/Components';
import { Input } from '@examena/ui';

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

export default function EditUser({ user, roles, userRole, isOpen, onClose, title, description, route }: Props) {
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
            }
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

    const searchPlaceholder = trans('components.select.search_placeholder');
    const noOptionFound = trans('components.select.no_option_found');


    return (
        <Modal isOpen={isOpen} size='2xl' onClose={onClose} isCloseableInside={false}>
            <div className="p-6 md:min-w-lg lg:min-w-xl w-full ">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">
                        {title || trans('admin_pages.users.edit_title')}
                    </h1>
                    <p className="text-gray-600 mt-1">
                        {description || trans('admin_pages.users.edit_subtitle', { name: user.name })}
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Input
                        id="name"
                        type="text"
                        className="mt-1 block w-full"
                        value={data.name}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('name', e.target.value)}
                        placeholder={trans('admin_pages.users.name_placeholder')}
                        required
                    />


                    <Input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('email', e.target.value)}
                        placeholder={trans('admin_pages.users.email_placeholder')}
                        required
                    />
                    {roles && <div>
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
                    </div>}

                    <div className="relative">
                        <div className="absolute inset-0 flex items-center">
                            <div className="w-full border-t border-gray-300" />
                        </div>
                        <div className="relative flex justify-center text-sm">
                            <span className="px-2 bg-white text-gray-500">
                                {trans('admin_pages.users.password_change')}
                            </span>
                        </div>
                    </div>

                    <Input
                        id="password"
                        type="password"
                        value={data.password}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('password', e.target.value)}
                        placeholder={trans('admin_pages.users.password_keep')}
                    />

                    <Input
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('password_confirmation', e.target.value)}
                        placeholder={trans('admin_pages.users.password_confirm_placeholder')}
                    />
                    <div className="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                        <Button
                            type="button"
                            color="secondary"
                            variant='outline'
                            size='sm'
                            onClick={handleCancel}
                        >
                            {trans('admin_pages.common.cancel')}
                        </Button>
                        <Button
                            type="submit"
                            color="primary"
                            size='sm'
                            loading={processing}
                            disabled={processing}
                        >
                            {processing ? (
                                trans('admin_pages.users.updating')
                            ) : (
                                trans('admin_pages.users.update_button')
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </Modal>
    );
}