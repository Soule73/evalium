import { type FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import { type Level } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button, Input, Toggle } from '@evalium/ui';
import { Section, Textarea } from '@/Components';
import { route } from 'ziggy-js';

interface LevelFormData {
    name: string;
    code: string;
    description: string;
    order: number;
    is_active: boolean;
}

interface LevelFormProps {
    title?: string;
    subtitle?: string;
    level?: Level;
    onCancel: () => void;
}

/**
 * Reusable form component for creating and editing levels
 */
export function LevelForm({ title, subtitle, level, onCancel }: LevelFormProps) {
    const isEditMode = !!level;

    const [formData, setFormData] = useState<LevelFormData>({
        name: level?.name || '',
        code: level?.code || '',
        description: level?.description || '',
        order: level?.order || 0,
        is_active: level?.is_active ?? true,
    });

    const [errors, setErrors] = useState<Partial<Record<keyof LevelFormData, string>>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const { t } = useTranslations();

    const handleChange = (field: keyof LevelFormData, value: string | number | boolean) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
        setErrors((prev) => ({ ...prev, [field]: undefined }));
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        const submitOptions = {
            onError: (errors: Record<string, string>) => {
                setErrors(errors as Partial<Record<keyof LevelFormData, string>>);
                setIsSubmitting(false);
            },
            onSuccess: () => {
                setIsSubmitting(false);
            },
        };

        if (isEditMode) {
            router.put(route('admin.levels.update', level.id), formData as never, submitOptions);
        } else {
            router.post(route('admin.levels.store'), formData as never, submitOptions);
        }
    };

    const submitButtonText = isEditMode
        ? isSubmitting
            ? t('admin_pages.levels.updating')
            : t('admin_pages.levels.update_button')
        : isSubmitting
          ? t('admin_pages.levels.creating')
          : t('admin_pages.levels.create_button');

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
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Input
                            label={t('admin_pages.levels.name_label')}
                            name="name"
                            value={formData.name}
                            onChange={(e) => handleChange('name', e.target.value)}
                            error={errors.name}
                            required
                            placeholder={t('admin_pages.levels.name_placeholder')}
                        />

                        <Input
                            label={t('admin_pages.levels.code')}
                            name="code"
                            value={formData.code}
                            onChange={(e) => handleChange('code', e.target.value)}
                            error={errors.code}
                            required
                            placeholder={t('admin_pages.levels.code_placeholder')}
                        />
                    </div>

                    <Textarea
                        label={t('admin_pages.levels.description')}
                        name="description"
                        value={formData.description}
                        onChange={(e) => handleChange('description', e.target.value)}
                        error={errors.description}
                        placeholder={t('admin_pages.levels.description_placeholder')}
                        rows={3}
                    />

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Input
                            label={t('admin_pages.levels.order_label')}
                            name="order"
                            type="number"
                            value={formData.order}
                            onChange={(e) => handleChange('order', parseInt(e.target.value) || 0)}
                            error={errors.order}
                            required
                            min={0}
                        />

                        <div className="flex flex-col gap-2">
                            <label className="text-sm font-medium text-gray-700">
                                {t('admin_pages.levels.status_label')}
                            </label>
                            <Toggle
                                checked={formData.is_active}
                                onChange={() => handleChange('is_active', !formData.is_active)}
                                activeLabel={t('commons/status.active')}
                                inactiveLabel={t('commons/status.inactive')}
                                showLabel={true}
                            />
                        </div>
                    </div>
                </div>
            </Section>
        </form>
    );
}
