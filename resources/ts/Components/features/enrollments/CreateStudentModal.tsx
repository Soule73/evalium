import { type FormEvent, useMemo, useState } from 'react';
import { type User } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button, Input, Modal } from '@/Components';
import { route } from 'ziggy-js';

interface CreateStudentModalProps {
  isOpen: boolean;
  onClose: () => void;
  onStudentCreated: (student: Pick<User, 'id' | 'name' | 'email' | 'avatar'>) => void;
}

/**
 * Modal for quickly creating a student from the enrollment form.
 */
export function CreateStudentModal({ isOpen, onClose, onStudentCreated }: CreateStudentModalProps) {
  const { t } = useTranslations();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const translations = useMemo(() => ({
    title: t('admin_pages.enrollments.quick_create_student_title'),
    subtitle: t('admin_pages.enrollments.quick_create_student_subtitle'),
    nameLabel: t('admin_pages.users.name'),
    emailLabel: t('admin_pages.users.email'),
    cancel: t('admin_pages.common.cancel'),
    create: t('admin_pages.enrollments.quick_create_button'),
    creating: t('admin_pages.enrollments.quick_creating'),
  }), [t]);

  const resetForm = () => {
    setName('');
    setEmail('');
    setErrors({});
    setIsSubmitting(false);
  };

  const handleClose = () => {
    resetForm();
    onClose();
  };

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    setErrors({});

    try {
      const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content;

      const response = await fetch(route('admin.enrollments.quick-student'), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken || '',
        },
        body: JSON.stringify({ name, email }),
      });

      if (!response.ok) {
        const data = await response.json();
        if (data.errors) {
          setErrors(data.errors);
        }
        setIsSubmitting(false);
        return;
      }

      const student = await response.json();
      onStudentCreated(student);
      handleClose();
    } catch {
      setIsSubmitting(false);
    }
  };

  return (
    <Modal isOpen={isOpen} onClose={handleClose} title={translations.title} size="md">
      <p className="text-sm text-gray-500 mb-6">{translations.subtitle}</p>

      <form onSubmit={handleSubmit} className="space-y-4">
        <Input
          label={translations.nameLabel}
          name="name"
          value={name}
          onChange={(e) => setName(e.target.value)}
          required
        />
        {errors.name && <p className="text-sm text-red-600 -mt-2">{errors.name}</p>}

        <Input
          label={translations.emailLabel}
          name="email"
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
        />
        {errors.email && <p className="text-sm text-red-600 -mt-2">{errors.email}</p>}

        <div className="flex justify-end space-x-3 pt-4">
          <Button type="button" variant="outline" color="secondary" onClick={handleClose} disabled={isSubmitting}>
            {translations.cancel}
          </Button>
          <Button type="submit" variant="solid" color="primary" disabled={isSubmitting}>
            {isSubmitting ? translations.creating : translations.create}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
