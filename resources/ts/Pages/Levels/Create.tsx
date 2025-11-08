import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Button, Input, Section, Textarea, Toggle } from '@/Components';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';
import { useLevelForm } from '@/hooks';

export default function CreateLevel() {
    const { formData, errors, isSubmitting, handleFieldChange, handleSubmit, handleCancel } = useLevelForm();

    return (
        <AuthenticatedLayout breadcrumb={breadcrumbs.levelCreate()}>
            <Section
                title={trans('admin_pages.levels.create_title')}
                subtitle={trans('admin_pages.levels.create_subtitle')}

                actions={
                    <div className="flex justify-end gap-3">
                        <Button
                            type="button"
                            onClick={handleCancel}
                            color="secondary"
                            variant="outline"
                            size="sm"
                            disabled={isSubmitting}
                        >
                            {trans('admin_pages.common.cancel')}
                        </Button>
                        <Button
                            type="submit"
                            color="primary"
                            disabled={isSubmitting}
                            size="sm"
                        >
                            {isSubmitting ? trans('admin_pages.levels.creating') : trans('admin_pages.levels.create_button')}
                        </Button>
                    </div>
                }
            >
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Input
                            label={trans('admin_pages.levels.name_label')}
                            type="text"
                            value={formData.name}
                            onChange={(e) => handleFieldChange('name', e.target.value)}
                            error={errors.name}
                            required
                            placeholder={trans('admin_pages.levels.name_placeholder')}
                        />

                        <Input
                            label={trans('admin_pages.levels.code')}
                            type="text"
                            value={formData.code}
                            onChange={(e) => handleFieldChange('code', e.target.value)}
                            error={errors.code}
                            required
                            placeholder={trans('admin_pages.levels.code_placeholder')}
                        />
                    </div>

                    <div className="flex flex-col gap-2">
                        <Textarea
                            label={trans('admin_pages.levels.description')}
                            value={formData.description}
                            onChange={(e) => handleFieldChange('description', e.target.value)}
                            error={errors.description}
                            placeholder={trans('admin_pages.levels.description_placeholder')}
                            rows={3}
                        />
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Input
                            label={trans('admin_pages.levels.order_label')}
                            type="number"
                            value={formData.order}
                            onChange={(e) => handleFieldChange('order', parseInt(e.target.value) || 0)}
                            error={errors.order}
                            required
                            min={0}
                        />

                        <div className="flex flex-col gap-2">
                            <label className="text-sm font-medium text-gray-700">
                                {trans('admin_pages.levels.status_label')}
                            </label>
                            <Toggle
                                checked={formData.is_active}
                                onChange={() => handleFieldChange('is_active', !formData.is_active)}
                                activeLabel={trans('admin_pages.common.active')}
                                inactiveLabel={trans('admin_pages.common.inactive')}
                                showLabel={true}
                            />
                        </div>
                    </div>
                </form>
            </Section>
        </AuthenticatedLayout>
    );
}
