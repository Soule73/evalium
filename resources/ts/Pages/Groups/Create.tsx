import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';
import { Button, LevelSelect, Section } from '@/Components';
import { Checkbox, Input } from '@examena/ui';
import { useGroupForm } from '@/hooks';

interface Props {
    levels: Record<number, string>;
    available_students: Array<{ id: number, name: string, email: string }>;
}

export default function CreateGroup({ levels }: Props) {
    const { formData, setData, errors, isSubmitting, handleSubmit, handleCancel } = useGroupForm();

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
                            disabled={isSubmitting}
                            size="sm"
                        >
                            {trans('admin_pages.common.cancel')}
                        </Button>
                        <Button
                            type="submit"
                            color="primary"
                            variant="solid"
                            disabled={isSubmitting}
                            loading={isSubmitting}
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
                                checked={formData.is_active}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('is_active', e.target.checked)}
                            />
                            <LevelSelect
                                value={formData.level_id}
                                onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setData('level_id', e.target.value)}
                                levels={levels}
                                error={errors.level_id}
                                required
                            />

                            <Input
                                label={trans('admin_pages.groups.academic_year')}
                                type="text"
                                value={formData.academic_year}
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
                                value={formData.start_date}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('start_date', e.target.value)}
                                error={errors.start_date}
                                required
                            />

                            <Input
                                label={trans('admin_pages.groups.end_date')}
                                type="date"
                                value={formData.end_date}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('end_date', e.target.value)}
                                error={errors.end_date}
                                required
                            />

                            <Input
                                label={trans('admin_pages.groups.max_students')}
                                type="number"
                                value={formData.max_students}
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