import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { Level } from '@/types';

interface LevelFormData {
    name: string;
    code: string;
    description: string;
    order: number;
    is_active: boolean;
}

interface UseLevelFormOptions {
    level?: Level;
}

export function useLevelForm({ level }: UseLevelFormOptions = {}) {
    const [formData, setFormData] = useState<LevelFormData>({
        name: level?.name || '',
        code: level?.code || '',
        description: level?.description || '',
        order: level?.order || 0,
        is_active: level?.is_active ?? true,
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleFieldChange = (field: keyof LevelFormData, value: string | number | boolean) => {
        setFormData(prev => ({ ...prev, [field]: value }));
        if (errors[field]) {
            setErrors(prev => {
                const newErrors = { ...prev };
                delete newErrors[field];
                return newErrors;
            });
        }
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        const routeName = level ? 'levels.update' : 'levels.store';
        const routeParams = level ? { level: level.id } : undefined;
        const method = level ? 'put' : 'post';

        router[method](route(routeName, routeParams), formData as any, {
            onError: (errors) => {
                setErrors(errors);
                setIsSubmitting(false);
            },
            onSuccess: () => {
                setIsSubmitting(false);
            },
        });
    };

    const handleCancel = () => {
        router.visit(route('levels.index'));
    };

    return {
        formData,
        errors,
        isSubmitting,
        handleFieldChange,
        handleSubmit,
        handleCancel,
    };
}
