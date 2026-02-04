import { useForm } from '@inertiajs/react';
import { Level } from '@/types';
import { route } from 'ziggy-js';

interface UseLevelFormProps {
  level?: Level;
}

interface LevelFormData {
  name: string;
  code: string;
  description: string;
  order: number;
  is_active: boolean;
}

export const useLevelForm = ({ level }: UseLevelFormProps = {}) => {
  const isEditing = !!level;

  const form = useForm<LevelFormData>({
    name: level?.name || '',
    code: level?.code || '',
    description: level?.description || '',
    order: level?.order || 0,
    is_active: level?.is_active ?? true,
  });

  const handleFieldChange = (field: keyof LevelFormData, value: string | number | boolean) => {
    form.setData(field, value as never);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (isEditing) {
      form.put(route('levels.update', level.id));
    } else {
      form.post(route('levels.store'));
    }
  };

  const handleCancel = () => {
    window.history.back();
  };

  return {
    formData: form.data,
    errors: form.errors,
    isSubmitting: form.processing,
    handleFieldChange,
    handleSubmit,
    handleCancel,
  };
};
