import { router } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import Section from '@/Components/Section';
import Input from '@/Components/form/Input';
import LevelSelect from '@/Components/form/LevelSelect';
import { route } from 'ziggy-js';
import Checkbox from '@/Components/form/Checkbox';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { trans } from '@/utils/translations';

interface Props {
    levels: Record<number, string>;
    available_students: Array<{ id: number, name: string, email: string }>;
}

interface GroupFormData {
    level_id: string;
    start_date: string;
    end_date: string;
    max_students: string;
    academic_year: string;
    is_active: boolean;
}

export default function CreateGroup({ levels }: Props) {
    const { data, setData, post, processing, errors } = useForm<GroupFormData>({
        level_id: '',
        start_date: '',
        end_date: '',
        max_students: '30',
        academic_year: new Date().getFullYear() + '-' + (new Date().getFullYear() + 1),
        is_active: true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('groups.store'), {
            onSuccess: () => {
                router.visit(route('groups.index'));
            }
        });
    };

    const handleCancel = () => {
        router.visit(route('groups.index'));
    };



    return (
        <AuthenticatedLayout title={trans('admin_pages.groups.create')}
            breadcrumb={breadcrumbs.groupCreate()}
        >
            <Section
                title={trans('admin_pages.groups.create_title')}
                subtitle={trans('admin_pages.groups.create_subtitle')}
                actions={
                    <div className="flex justify-end space-x-4">
                        <Button
                            type="button"
                            onClick={handleCancel}
                            color="secondary"
                            variant="outline"
                            disabled={processing}
                            size="sm"
                        >
                            {trans('admin_pages.common.cancel')}
                        </Button>
                        <Button
                            type="submit"
                            color="primary"
                            variant="solid"
                            disabled={processing}
                            loading={processing}
                            size="sm"
                        >
                            {trans('admin_pages.groups.create_button')}
                        </Button>
                    </div>
                }
            >
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div className="space-y-6">
                            <Checkbox
                                id="is_active"
                                label={trans('admin_pages.groups.group_active')}
                                checked={data.is_active}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('is_active', e.target.checked)}
                            />
                            <LevelSelect
                                value={data.level_id}
                                onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setData('level_id', e.target.value)}
                                levels={levels}
                                error={errors.level_id}
                                required
                            />

                            <Input
                                label={trans('admin_pages.groups.academic_year')}
                                type="text"
                                value={data.academic_year}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('academic_year', e.target.value)}
                                error={errors.academic_year}
                                placeholder={trans('admin_pages.groups.academic_year_placeholder')}
                                required
                            />
                        </div>

                        <div className="space-y-6">
                            <Input
                                label={trans('admin_pages.groups.start_date')}
                                type="date"
                                value={data.start_date}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('start_date', e.target.value)}
                                error={errors.start_date}
                                required
                            />

                            <Input
                                label={trans('admin_pages.groups.end_date')}
                                type="date"
                                value={data.end_date}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('end_date', e.target.value)}
                                error={errors.end_date}
                                required
                            />

                            <Input
                                label={trans('admin_pages.groups.max_students')}
                                type="number"
                                value={data.max_students}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('max_students', e.target.value)}
                                error={errors.max_students}
                                min="1"
                                max="100"
                                required
                            />
                        </div>
                    </div>
                </form>
            </Section>
        </AuthenticatedLayout>
    );
}