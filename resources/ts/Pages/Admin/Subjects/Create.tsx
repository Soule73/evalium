import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { SubjectFormData, Level } from '@/types';
import { breadcrumbs, trans } from '@/utils';
import { Button, Section, Input, Select } from '@/Components';
import { route } from 'ziggy-js';

interface Props {
    levels: Level[];
}

export default function SubjectCreate({ levels }: Props) {
    const [formData, setFormData] = useState<SubjectFormData>({
        level_id: 0,
        name: '',
        code: '',
        description: '',
    });

    const [errors, setErrors] = useState<Partial<Record<keyof SubjectFormData, string>>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleChange = (field: keyof SubjectFormData, value: string) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
        setErrors((prev) => ({ ...prev, [field]: undefined }));
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        router.post(route('admin.subjects.store'), formData as any, {
            onError: (errors) => {
                setErrors(errors as any);
                setIsSubmitting(false);
            },
            onSuccess: () => {
                setIsSubmitting(false);
            },
        });
    };

    const handleCancel = () => {
        router.visit(route('admin.subjects.index'));
    };

    return (
        <AuthenticatedLayout
            title={trans('admin_pages.subjects.create_title')}
            breadcrumb={breadcrumbs.admin.createSubject()}
        >
            <Section
                title={trans('admin_pages.subjects.create_title')}
                subtitle={trans('admin_pages.subjects.create_subtitle')}
            >
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 gap-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <Input
                                label={trans('admin_pages.subjects.name')}
                                name="name"
                                value={formData.name}
                                onChange={(e) => handleChange('name', e.target.value)}
                                error={errors.name}
                                required
                                placeholder={trans('admin_pages.subjects.name_placeholder')}
                            />

                            <Input
                                label={trans('admin_pages.subjects.code')}
                                name="code"
                                value={formData.code}
                                onChange={(e) => handleChange('code', e.target.value)}
                                error={errors.code}
                                required
                                placeholder={trans('admin_pages.subjects.code_placeholder')}
                            />
                        </div>

                        <Select
                            label={trans('admin_pages.subjects.level')}
                            name="level_id"
                            value={formData.level_id}
                            onChange={(value) => handleChange('level_id', value.toString())}
                            error={errors.level_id}
                            required
                            options={[
                                { value: 0, label: trans('admin_pages.subjects.select_level') },
                                ...levels.map((level) => ({
                                    value: level.id,
                                    label: level.name
                                }))
                            ]}
                        />

                        <Input
                            label={trans('admin_pages.subjects.description')}
                            name="description"
                            value={formData.description || ''}
                            onChange={(e) => handleChange('description', e.target.value)}
                            error={errors.description}
                            placeholder={trans('admin_pages.subjects.description_placeholder')}
                            helperText={trans('admin_pages.subjects.description_helper')}
                        />
                    </div>

                    <div className="flex justify-end space-x-3 pt-6 border-t">
                        <Button type="button" variant="outline" color="secondary" onClick={handleCancel} disabled={isSubmitting}>
                            {trans('admin_pages.common.cancel')}
                        </Button>
                        <Button type="submit" variant="solid" color="primary" disabled={isSubmitting}>
                            {isSubmitting ? trans('admin_pages.subjects.creating') : trans('admin_pages.subjects.create_button')}
                        </Button>
                    </div>
                </form>
            </Section>
        </AuthenticatedLayout>
    );
}
