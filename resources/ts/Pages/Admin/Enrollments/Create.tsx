import { type FormEvent, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { type FormDataConvertible } from '@inertiajs/core';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type EnrollmentFormData, type ClassModel, type User } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section, Select } from '@/Components';
import { route } from 'ziggy-js';

interface Props {
  classes: ClassModel[];
  students: User[];
}

export default function EnrollmentCreate({ classes, students }: Props) {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();
  const [formData, setFormData] = useState<EnrollmentFormData>({
    class_id: 0,
    student_id: 0,
  });

  const [errors, setErrors] = useState<Partial<Record<keyof EnrollmentFormData, string>>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleChange = (field: keyof EnrollmentFormData, value: number) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    setErrors((prev) => ({ ...prev, [field]: undefined }));
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    router.post(route('admin.enrollments.store'), formData as unknown as unknown as Record<string, FormDataConvertible>, {
      onError: (errors) => {
        setErrors(errors as Partial<Record<keyof EnrollmentFormData, string>>);
        setIsSubmitting(false);
      },
      onSuccess: () => {
        setIsSubmitting(false);
      },
    });
  };

  const handleCancel = () => {
    router.visit(route('admin.enrollments.index'));
  };

  const selectedClass = classes.find(c => c.id === formData.class_id);
  const availableSlots = selectedClass
    ? (selectedClass.max_students - (selectedClass.active_enrollments_count || 0))
    : 0;

  const translations = useMemo(() => ({
    createTitle: t('admin_pages.enrollments.create_title'),
    createSubtitle: t('admin_pages.enrollments.create_subtitle'),
    studentLabel: t('admin_pages.enrollments.student'),
    classLabel: t('admin_pages.enrollments.class'),
    availableSlots: t('admin_pages.enrollments.available_slots'),
    classFull: t('admin_pages.enrollments.class_full'),
    cancel: t('admin_pages.common.cancel'),
    creating: t('admin_pages.enrollments.creating'),
    createButton: t('admin_pages.enrollments.create_button'),
  }), [t]);

  const studentsItem = useMemo(() => [
    { value: 0, label: t('admin_pages.enrollments.select_student') },
    ...students.map((student) => ({
      value: student.id,
      label: `${student.name} (${student.email})`
    }))
  ], [t, students]);

  const classesItem = useMemo(() => [
    { value: 0, label: t('admin_pages.enrollments.select_class') },
    ...classes.map((classItem) => ({
      value: classItem.id,
      label: `${classItem.name} - ${classItem.level?.name}(${(classItem.level?.description || '')?.substring(0, 30)})`
    }))
  ], [t, classes]);


  return (
    <AuthenticatedLayout
      title={translations.createTitle}
      breadcrumb={breadcrumbs.admin.createEnrollment()}
    >
      <Section
        title={translations.createTitle}
        subtitle={translations.createSubtitle}
      >
        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="grid grid-cols-1 gap-6">
            <Select
              label={translations.studentLabel}
              name="student_id"
              value={formData.student_id}
              onChange={(value) => handleChange('student_id', value as number)}
              error={errors.student_id}
              required
              searchable
              options={studentsItem}
            />

            <Select
              label={translations.classLabel}
              name="class_id"
              value={formData.class_id}
              onChange={(value) => handleChange('class_id', value as number)}
              error={errors.class_id}
              required
              searchable
              options={classesItem}
            />

            {selectedClass && (
              <div className={`p-4 rounded-lg ${availableSlots > 0 ? 'bg-blue-50 border border-blue-200' : 'bg-red-50 border border-red-200'}`}>
                <div className="text-sm">
                  <span className="font-medium">
                    {translations.availableSlots}:
                  </span>
                  <span className={`ml-2 ${availableSlots > 0 ? 'text-blue-900' : 'text-red-900'}`}>
                    {availableSlots} / {selectedClass.max_students}
                  </span>
                </div>
                {availableSlots === 0 && (
                  <div className="mt-2 text-sm text-red-700">
                    {translations.classFull}
                  </div>
                )}
              </div>
            )}
          </div>

          <div className="flex justify-end space-x-3 pt-6">
            <Button type="button" variant="outline" color="secondary" onClick={handleCancel} disabled={isSubmitting}>
              {translations.cancel}
            </Button>
            <Button
              type="submit"
              variant="solid"
              color="primary"
              disabled={isSubmitting || availableSlots === 0}
            >
              {isSubmitting ? translations.creating : translations.createButton}
            </Button>
          </div>
        </form>
      </Section>
    </AuthenticatedLayout>
  );
}
