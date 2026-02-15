import { type FormEvent, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { type FormDataConvertible } from '@inertiajs/core';
import { type Enrollment, type ClassModel } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button, Modal, Select } from '@/Components';
import { route } from 'ziggy-js';

interface TransferEnrollmentModalProps {
  isOpen: boolean;
  onClose: () => void;
  enrollment: Enrollment;
  classes: ClassModel[];
}

interface FormValues {
  new_class_id: number;
}

const getInitialValues = (): FormValues => ({
  new_class_id: 0,
});

/**
 * Modal component for transferring a student to another class.
 */
export function TransferEnrollmentModal({
  isOpen,
  onClose,
  enrollment,
  classes,
}: TransferEnrollmentModalProps) {
  const [formValues, setFormValues] = useState<FormValues>(getInitialValues);
  const [formErrors, setFormErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { t } = useTranslations();

  const availableClasses = useMemo(
    () =>
      classes.filter(
        (c) =>
          c.id !== enrollment.class_id &&
          c.academic_year_id === enrollment.class?.academic_year_id
      ),
    [classes, enrollment.class_id, enrollment.class?.academic_year_id]
  );

  const classOptions = useMemo(
    () => [
      { value: 0, label: t('admin_pages.enrollments.select_target_class') },
      ...availableClasses.map((classItem) => ({
        value: classItem.id,
        label: `${classItem.name} - ${classItem.level?.name} (${classItem.active_enrollments_count || 0}/${classItem.max_students})`,
      })),
    ],
    [availableClasses, t]
  );

  const selectedClass = classes.find((c) => c.id === formValues.new_class_id);
  const availableSlots = selectedClass
    ? selectedClass.max_students - (selectedClass.active_enrollments_count || 0)
    : 0;

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    setFormErrors({});

    router.post(route('admin.enrollments.transfer', enrollment.id), formValues as unknown as unknown as Record<string, FormDataConvertible>, {
      onError: (errors) => {
        setFormErrors(errors);
        setIsSubmitting(false);
      },
      onSuccess: () => {
        setIsSubmitting(false);
        resetAndClose();
      },
    });
  };

  const resetAndClose = () => {
    setFormValues(getInitialValues());
    setFormErrors({});
    onClose();
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={resetAndClose}
      title={t('admin_pages.enrollments.transfer_title')}
      size="md"
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="p-4 bg-indigo-50 border border-indigo-200 rounded-lg">
          <div className="text-sm">
            <div className="font-medium text-indigo-900 mb-2">
              {t('admin_pages.enrollments.current_enrollment')}
            </div>
            <div className="text-indigo-800 space-y-1">
              <div>
                <span className="font-medium">{t('admin_pages.enrollments.student')}:</span>{' '}
                {enrollment.student?.name}
              </div>
              <div>
                <span className="font-medium">{t('admin_pages.enrollments.class')}:</span>{' '}
                {enrollment.class?.name} ({enrollment.class?.level?.name})
              </div>
            </div>
          </div>
        </div>

        <Select
          label={t('admin_pages.enrollments.transfer_to_class')}
          name="new_class_id"
          value={formValues.new_class_id}
          onChange={(value) => setFormValues({ new_class_id: value as number })}
          error={formErrors.new_class_id}
          required
          searchable
          options={classOptions}
        />

        {selectedClass && (
          <div
            className={`p-4 rounded-lg ${availableSlots > 0
              ? 'bg-green-50 border border-green-200'
              : 'bg-red-50 border border-red-200'
              }`}
          >
            <div className="text-sm">
              <span className="font-medium">
                {t('admin_pages.enrollments.available_slots')}:
              </span>
              <span className={`ml-2 ${availableSlots > 0 ? 'text-green-900' : 'text-red-900'}`}>
                {availableSlots} / {selectedClass.max_students}
              </span>
            </div>
            {availableSlots === 0 && (
              <div className="mt-2 text-sm text-red-700">
                {t('admin_pages.enrollments.class_full')}
              </div>
            )}
          </div>
        )}

        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <p className="text-sm text-yellow-800">
            {t('admin_pages.enrollments.transfer_warning')}
          </p>
        </div>

        <div className="flex justify-end space-x-3 pt-4">
          <Button
            type="button"
            variant="outline"
            color="secondary"
            onClick={resetAndClose}
            disabled={isSubmitting}
          >
            {t('admin_pages.common.cancel')}
          </Button>
          <Button
            type="submit"
            variant="solid"
            color="primary"
            disabled={isSubmitting || formValues.new_class_id === 0 || availableSlots === 0}
          >
            {isSubmitting
              ? t('admin_pages.enrollments.transferring')
              : t('admin_pages.enrollments.transfer_button')}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
