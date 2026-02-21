import { type FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import { type FormDataConvertible } from '@inertiajs/core';
import { type SubjectFormData, type Level, type Subject } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button, Input, Section, Select } from '@evalium/ui';
import { route } from 'ziggy-js';

interface SubjectFormProps {
    title?: string;
    subtitle?: string;
    subject?: Subject;
    levels: Level[];
    onCancel: () => void;
}

/**
 * Reusable form component for creating and editing subjects
 */
export function SubjectForm({ title, subtitle, subject, levels, onCancel }: SubjectFormProps) {
    const isEditMode = !!subject;

    const [formData, setFormData] = useState<SubjectFormData>({
        level_id: subject?.level_id || 0,
        name: subject?.name || '',
        code: subject?.code || '',
        description: subject?.description || '',
    });

    const [errors, setErrors] = useState<Partial<Record<keyof SubjectFormData, string>>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const { t } = useTranslations();

    const handleChange = (field: keyof SubjectFormData, value: string | number) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
        setErrors((prev) => ({ ...prev, [field]: undefined }));
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        const submitOptions = {
            onError: (errors: Record<string, string>) => {
                setErrors(errors as Partial<Record<keyof SubjectFormData, string>>);
                setIsSubmitting(false);
            },
            onSuccess: () => {
                setIsSubmitting(false);
            },
        };

        if (isEditMode) {
            router.put(
                route('admin.subjects.update', subject.id),
                formData as unknown as unknown as Record<string, FormDataConvertible>,
                submitOptions,
            );
        } else {
            router.post(
                route('admin.subjects.store'),
                formData as unknown as unknown as Record<string, FormDataConvertible>,
                submitOptions,
            );
        }
    };

    const levelOptions = [
        { value: 0, label: t('admin_pages.subjects.select_level') },
        ...levels.map((level) => ({
            value: level.id,
            label: level.name,
        })),
    ];

    const submitButtonText = isEditMode
        ? isSubmitting
            ? t('admin_pages.subjects.updating')
            : t('admin_pages.subjects.update_button')
        : isSubmitting
          ? t('admin_pages.subjects.creating')
          : t('admin_pages.subjects.create_button');

    return (
        <form onSubmit={handleSubmit}>
            <Section
                title={title}
                subtitle={subtitle}
                actions={
                    <div className="flex justify-end space-x-3">
                        <Button
                            type="button"
                            variant="outline"
                            color="secondary"
                            onClick={onCancel}
                            disabled={isSubmitting}
                        >
                            {t('commons/ui.cancel')}
                        </Button>
                        <Button
                            type="submit"
                            variant="solid"
                            color="primary"
                            disabled={isSubmitting}
                        >
                            {submitButtonText}
                        </Button>
                    </div>
                }
            >
                <div className="space-y-6">
                    <div className="grid grid-cols-1 gap-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <Input
                                label={t('admin_pages.subjects.name')}
                                name="name"
                                value={formData.name}
                                onChange={(e) => handleChange('name', e.target.value)}
                                error={errors.name}
                                required
                                placeholder={t('admin_pages.subjects.name_placeholder')}
                            />

                            <Input
                                label={t('admin_pages.subjects.code')}
                                name="code"
                                value={formData.code}
                                onChange={(e) => handleChange('code', e.target.value)}
                                error={errors.code}
                                required
                                placeholder={t('admin_pages.subjects.code_placeholder')}
                            />
                        </div>

                        <Select
                            label={t('admin_pages.subjects.level')}
                            name="level_id"
                            value={formData.level_id}
                            onChange={(value) => handleChange('level_id', Number(value))}
                            error={errors.level_id}
                            required
                            options={levelOptions}
                        />

                        <Input
                            label={t('admin_pages.subjects.description')}
                            name="description"
                            value={formData.description || ''}
                            onChange={(e) => handleChange('description', e.target.value)}
                            error={errors.description}
                            placeholder={t('admin_pages.subjects.description_placeholder')}
                            helperText={t('admin_pages.subjects.description_helper')}
                        />
                    </div>
                </div>
            </Section>
        </form>
    );
}

export default SubjectForm;
