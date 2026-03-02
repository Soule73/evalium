import { type FormEvent, useEffect, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { type FormDataConvertible } from '@inertiajs/core';
import { type ClassModel, type Level } from '@evalium/utils/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button, Input, Modal, Select } from '@evalium/ui';
import { route } from 'ziggy-js';

interface ClassFormData {
    level_id: number;
    name: string;
    max_students: number;
}

interface ClassFormModalProps {
    isOpen: boolean;
    onClose: () => void;
    classItem?: ClassModel | null;
    levels: Level[];
}

/**
 * Modal form component for creating and editing classes.
 */
export function ClassFormModal({ isOpen, onClose, classItem, levels }: ClassFormModalProps) {
    const isEditMode = !!classItem;
    const { t } = useTranslations();

    const [formData, setFormData] = useState<ClassFormData>({
        level_id: 0,
        name: '',
        max_students: 30,
    });

    const [errors, setErrors] = useState<Partial<Record<keyof ClassFormData, string>>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    useEffect(() => {
        if (isOpen) {
            setFormData({
                level_id: classItem?.level_id || 0,
                name: classItem?.name || '',
                max_students: classItem?.max_students || 30,
            });
            setErrors({});
            setIsSubmitting(false);
        }
    }, [isOpen, classItem]);

    const handleChange = (field: keyof ClassFormData, value: string | number) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
        setErrors((prev) => ({ ...prev, [field]: undefined }));
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        const submitOptions = {
            preserveScroll: true,
            onError: (errors: Record<string, string>) => {
                setErrors(errors as Partial<Record<keyof ClassFormData, string>>);
                setIsSubmitting(false);
            },
            onSuccess: () => {
                setIsSubmitting(false);
                onClose();
            },
        };

        if (isEditMode && classItem) {
            router.put(
                route('admin.classes.update', classItem.id),
                formData as unknown as Record<string, FormDataConvertible>,
                submitOptions,
            );
        } else {
            router.post(
                route('admin.classes.store'),
                formData as unknown as Record<string, FormDataConvertible>,
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

    const modalTitle = isEditMode
        ? t('admin_pages.classes.edit_title')
        : t('admin_pages.classes.create_title');

    const submitButtonText = isEditMode
        ? isSubmitting
            ? t('admin_pages.classes.updating')
            : t('admin_pages.classes.update_button')
        : isSubmitting
          ? t('admin_pages.classes.creating')
          : t('admin_pages.classes.create_button');

    return (
        <Modal isOpen={isOpen} onClose={onClose} title={modalTitle} size="xl">
            <form onSubmit={handleSubmit} className="space-y-6">
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
                    onChange={(e) => handleChange('max_students', parseInt(e.target.value) || 0)}
                    error={errors.max_students}
                    required
                    min={1}
                    helperText={t('admin_pages.classes.max_students_helper')}
                />

                <div className="flex justify-end gap-3 pt-4 border-t border-gray-200">
                    <Button
                        type="button"
                        variant="outline"
                        color="secondary"
                        onClick={onClose}
                        disabled={isSubmitting}
                    >
                        {t('commons/ui.cancel')}
                    </Button>
                    <Button type="submit" variant="solid" color="primary" disabled={isSubmitting}>
                        {submitButtonText}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
