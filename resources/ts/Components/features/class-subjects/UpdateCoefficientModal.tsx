import { type FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import { type FormDataConvertible } from '@inertiajs/core';
import { type ClassSubject } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button, Modal, Input } from '@/Components';
import { route } from 'ziggy-js';

interface UpdateCoefficientModalProps {
  isOpen: boolean;
  onClose: () => void;
  classSubject: ClassSubject;
}

/**
 * Modal component for updating the coefficient of a class-subject assignment.
 */
export function UpdateCoefficientModal({
  isOpen,
  onClose,
  classSubject,
}: UpdateCoefficientModalProps) {
  const [coefficient, setCoefficient] = useState(classSubject.coefficient);
  const [formErrors, setFormErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { t } = useTranslations();

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    setFormErrors({});

    router.post(
      route('admin.class-subjects.update-coefficient', classSubject.id),
      { coefficient } as unknown as unknown as Record<string, FormDataConvertible>,
      {
        onError: (errors) => {
          setFormErrors(errors);
          setIsSubmitting(false);
        },
        onSuccess: () => {
          setIsSubmitting(false);
          onClose();
        },
      }
    );
  };

  const resetAndClose = () => {
    setCoefficient(classSubject.coefficient);
    setFormErrors({});
    onClose();
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={resetAndClose}
      title={t('admin_pages.class_subjects.update_coefficient_title')}
      size="sm"
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div className="text-sm">
            <div className="font-medium text-blue-900 mb-2">
              {t('admin_pages.class_subjects.assignment_info')}
            </div>
            <div className="text-blue-800 space-y-1">
              <div>
                <span className="font-medium">{t('admin_pages.class_subjects.class')}:</span>{' '}
                {classSubject.class?.name}
              </div>
              <div>
                <span className="font-medium">{t('admin_pages.class_subjects.subject')}:</span>{' '}
                {classSubject.subject?.code} - {classSubject.subject?.name}
              </div>
              <div>
                <span className="font-medium">{t('admin_pages.class_subjects.current_coefficient')}:</span>{' '}
                {classSubject.coefficient}
              </div>
            </div>
          </div>
        </div>

        <Input
          label={t('admin_pages.class_subjects.new_coefficient')}
          name="coefficient"
          type="number"
          min="0.5"
          max="10"
          step="0.5"
          value={coefficient}
          onChange={(e) => setCoefficient(parseFloat(e.target.value) || 0)}
          error={formErrors.coefficient}
          required
        />

        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <div className="text-sm text-yellow-800">
            {t('admin_pages.class_subjects.update_coefficient_warning')}
          </div>
        </div>

        <div className="flex justify-end space-x-3 pt-4 border-t">
          <Button
            type="button"
            variant="outline"
            color="secondary"
            onClick={resetAndClose}
            disabled={isSubmitting}
          >
            {t('admin_pages.common.cancel')}
          </Button>
          <Button type="submit" variant="solid" color="primary" disabled={isSubmitting}>
            {isSubmitting
              ? t('admin_pages.class_subjects.updating')
              : t('admin_pages.class_subjects.update_button')}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
