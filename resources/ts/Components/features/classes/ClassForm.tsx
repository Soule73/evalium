import { type FormEvent, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { type FormDataConvertible } from '@inertiajs/core';
import { type ClassModel, type Level } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button, Input, Section, Select } from '@evalium/ui';
import { route } from 'ziggy-js';

interface ClassFormProps {
    title?: string;
    subtitle?: string;
    classItem?: ClassModel;
    levels: Level[];
    onCancel: () => void;
}

/**
 * Reusable form component for creating and editing classes
 */
export function ClassForm({ title, subtitle, classItem, levels, onCancel }: ClassFormProps) {
    const isEditMode = !!classItem;

    const [formData, setFormData] = useState({
        level_id: classItem?.level_id || 0,
        name: classItem?.name || '',
        max_students: classItem?.max_students || 30,
    });

    const [errors, setErrors] = useState<Partial<Record<keyof typeof formData, string>>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const { t } = useTranslations();

    const handleChange = (field: keyof typeof formData, value: string | number) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
        setErrors((prev) => ({ ...prev, [field]: undefined }));
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        const submitOptions = {
            onError: (errors: Record<string, string>) => {
                setErrors(errors as Partial<Record<keyof typeof formData, string>>);
                setIsSubmitting(false);
            },
            onSuccess: () => {
                setIsSubmitting(false);
            },
        };

        if (isEditMode) {
            router.put(
                route('admin.classes.update', classItem.id),
                formData as unknown as unknown as Record<string, FormDataConvertible>,
                submitOptions,
            );
        } else {
            router.post(
                route('admin.classes.store'),
                formData as unknown as unknown as Record<string, FormDataConvertible>,
                submitOptions,
            );
        }
    };

    const selectLevelLabel = t('admin_pages.classes.select_level');

    const levelOptions = useMemo(
        () => [
            { value: 0, label: selectLevelLabel },
            ...levels.map((level) => ({
                value: level.id,
                label: level.name,
            })),
        ],
        [levels, selectLevelLabel],
    );

    const submitButtonText = isEditMode
        ? isSubmitting
            ? t('admin_pages.classes.updating')
            : t('admin_pages.classes.update_button')
        : isSubmitting
          ? t('admin_pages.classes.creating')
          : t('admin_pages.classes.create_button');

    return (
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
                        form="class-form"
                    >
                        {submitButtonText}
                    </Button>
                </div>
            }
        >
            <form id="class-form" onSubmit={handleSubmit} className="space-y-6">
                <div className="grid grid-cols-1 gap-6">
                    <Input
                        label={t('admin_pages.classes.name')}
                        name="name"
                        value={formData.name}
                        onChange={(e) => handleChange('name', e.target.value)}
                        error={errors.name}
                        required
                        placeholder={t('admin_pages.classes.name_placeholder')}
                        helperText={t('admin_pages.classes.name_helper')}
                    />

                    <Select
                        label={t('admin_pages.classes.level')}
                        name="level_id"
                        value={formData.level_id}
                        onChange={(value) => handleChange('level_id', Number(value))}
                        error={errors.level_id}
                        required
                        options={levelOptions}
                    />

                    <Input
                        label={t('admin_pages.classes.max_students')}
                        name="max_students"
                        type="number"
                        value={formData.max_students?.toString() || ''}
                        onChange={(e) =>
                            handleChange('max_students', parseInt(e.target.value) || 0)
                        }
                        error={errors.max_students}
                        required
                        min={1}
                        helperText={t('admin_pages.classes.max_students_helper')}
                    />
                </div>
            </form>
        </Section>
    );
}
