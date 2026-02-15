import { type FormEvent, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { type FormDataConvertible } from '@inertiajs/core';
import { type ClassSubject, type User } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button, Modal, Select } from '@/Components';
import { route } from 'ziggy-js';

interface ReplaceTeacherModalProps {
  isOpen: boolean;
  onClose: () => void;
  classSubject: ClassSubject;
  teachers: User[];
}

interface FormValues {
  new_teacher_id: number;
  effective_date: string;
}

const getInitialValues = (): FormValues => ({
  new_teacher_id: 0,
  effective_date: new Date().toISOString().split('T')[0],
});

/**
 * Modal component for replacing the teacher of a class-subject assignment.
 */
export function ReplaceTeacherModal({
  isOpen,
  onClose,
  classSubject,
  teachers,
}: ReplaceTeacherModalProps) {
  const [formValues, setFormValues] = useState<FormValues>(getInitialValues);
  const [formErrors, setFormErrors] = useState<Record<string, string>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { t } = useTranslations();

  const availableTeachers = useMemo(
    () => teachers.filter((teacher) => teacher.id !== classSubject.teacher_id),
    [teachers, classSubject.teacher_id]
  );

  const teacherOptions = useMemo(
    () => [
      { value: 0, label: t('admin_pages.class_subjects.select_new_teacher') },
      ...availableTeachers.map((teacher) => ({
        value: teacher.id,
        label: `${teacher.name} (${teacher.email})`,
      })),
    ],
    [availableTeachers, t]
  );

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    setFormErrors({});

    router.post(route('admin.class-subjects.replace-teacher', classSubject.id), formValues as unknown as unknown as Record<string, FormDataConvertible>, {
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
      title={t('admin_pages.class_subjects.replace_teacher_title')}
      size="md"
    >
      <form onSubmit={handleSubmit} className="space-y-4">
        <div className="p-4 bg-indigo-50 border border-indigo-200 rounded-lg">
          <div className="text-sm">
            <div className="font-medium text-indigo-900 mb-2">
              {t('admin_pages.class_subjects.current_assignment')}
            </div>
            <div className="text-indigo-800 space-y-1">
              <div>
                <span className="font-medium">{t('admin_pages.class_subjects.class')}:</span>{' '}
                {classSubject.class?.name}
              </div>
              <div>
                <span className="font-medium">{t('admin_pages.class_subjects.subject')}:</span>{' '}
                {classSubject.subject?.code} - {classSubject.subject?.name}
              </div>
              <div>
                <span className="font-medium">{t('admin_pages.class_subjects.current_teacher')}:</span>{' '}
                {classSubject.teacher?.name}
              </div>
            </div>
          </div>
        </div>

        <Select
          label={t('admin_pages.class_subjects.new_teacher')}
          name="new_teacher_id"
          value={formValues.new_teacher_id}
          onChange={(value) => setFormValues((prev) => ({ ...prev, new_teacher_id: value as number }))}
          error={formErrors.new_teacher_id}
          required
          searchable
          options={teacherOptions}
        />

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            {t('admin_pages.class_subjects.effective_date')}
          </label>
          <input
            type="date"
            name="effective_date"
            value={formValues.effective_date}
            onChange={(e) => setFormValues((prev) => ({ ...prev, effective_date: e.target.value }))}
            className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
            required
          />
          {formErrors.effective_date && (
            <p className="mt-1 text-sm text-red-600">{formErrors.effective_date}</p>
          )}
        </div>

        <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
          <div className="text-sm text-yellow-800">
            {t('admin_pages.class_subjects.replace_teacher_warning')}
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
          <Button
            type="submit"
            variant="solid"
            color="primary"
            disabled={isSubmitting || formValues.new_teacher_id === 0}
          >
            {isSubmitting
              ? t('admin_pages.class_subjects.replacing')
              : t('admin_pages.class_subjects.replace_button')}
          </Button>
        </div>
      </form>
    </Modal>
  );
}
