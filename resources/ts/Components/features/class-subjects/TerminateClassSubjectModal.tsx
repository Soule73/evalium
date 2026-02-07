import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import { ClassSubject } from '@/types';
import { trans } from '@/utils';
import { Button, Modal } from '@/Components';
import { route } from 'ziggy-js';

interface TerminateClassSubjectModalProps {
  isOpen: boolean;
  onClose: () => void;
  classSubject: ClassSubject;
}

const getInitialEndDate = () => new Date().toISOString().split('T')[0];

/**
 * Modal component for terminating a class-subject assignment.
 */
export function TerminateClassSubjectModal({
  isOpen,
  onClose,
  classSubject,
}: TerminateClassSubjectModalProps) {
  const [endDate, setEndDate] = useState(getInitialEndDate);
  const [formErrors, setFormErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    setFormErrors({});

    router.post(
      route('admin.class-subjects.terminate', classSubject.id),
      { end_date: endDate } as any,
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
    setEndDate(getInitialEndDate());
    setFormErrors({});
    onClose();
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={resetAndClose}
      title={trans('admin_pages.class_subjects.terminate_title')}
      size="sm"
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div className="text-sm">
            <div className="font-medium text-blue-900 mb-2">
              {trans('admin_pages.class_subjects.assignment_info')}
            </div>
            <div className="text-blue-800 space-y-1">
              <div>
                <span className="font-medium">{trans('admin_pages.class_subjects.class')}:</span>{' '}
                {classSubject.class?.name}
              </div>
              <div>
                <span className="font-medium">{trans('admin_pages.class_subjects.subject')}:</span>{' '}
                {classSubject.subject?.code} - {classSubject.subject?.name}
              </div>
              <div>
                <span className="font-medium">{trans('admin_pages.class_subjects.teacher')}:</span>{' '}
                {classSubject.teacher?.name}
              </div>
            </div>
          </div>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            {trans('admin_pages.class_subjects.end_date')}
          </label>
          <input
            type="date"
            name="end_date"
            value={endDate}
            onChange={(e) => setEndDate(e.target.value)}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
            required
          />
          {formErrors.end_date && (
            <p className="mt-1 text-sm text-red-600">{formErrors.end_date}</p>
          )}
        </div>

        <div className="bg-red-50 border border-red-200 rounded-lg p-4">
          <div className="text-sm text-red-800">
            {trans('admin_pages.class_subjects.terminate_warning')}
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
            {trans('admin_pages.common.cancel')}
          </Button>
          <Button type="submit" variant="solid" color="danger" disabled={isSubmitting}>
            {isSubmitting
              ? trans('admin_pages.class_subjects.terminating')
              : trans('admin_pages.class_subjects.terminate_button')}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
