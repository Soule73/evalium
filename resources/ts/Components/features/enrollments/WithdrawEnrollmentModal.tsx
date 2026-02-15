import { type FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import { type Enrollment } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button, Modal } from '@/Components';
import { route } from 'ziggy-js';

interface WithdrawEnrollmentModalProps {
  isOpen: boolean;
  onClose: () => void;
  enrollment: Enrollment;
}

/**
 * Modal component for withdrawing a student from a class.
 */
export function WithdrawEnrollmentModal({
  isOpen,
  onClose,
  enrollment,
}: WithdrawEnrollmentModalProps) {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { t } = useTranslations();

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    router.post(route('admin.enrollments.withdraw', enrollment.id), {}, {
      onError: () => {
        setIsSubmitting(false);
      },
      onSuccess: () => {
        setIsSubmitting(false);
        onClose();
      },
    });
  };

  const resetAndClose = () => {
    setIsSubmitting(false);
    onClose();
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={resetAndClose}
      title={t('admin_pages.enrollments.withdraw_title')}
      size="sm"
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="p-4 ">
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

        <div className="p-4">
          <p className="text-sm text-red-800">
            {t('admin_pages.enrollments.withdraw_confirm_message')}
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
            color="danger"
            disabled={isSubmitting}
          >
            {isSubmitting
              ? t('admin_pages.common.processing')
              : t('admin_pages.enrollments.withdraw_confirm')}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
