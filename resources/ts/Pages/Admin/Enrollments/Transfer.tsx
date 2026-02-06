import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Enrollment, TransferStudentFormData, ClassModel } from '@/types';
import { breadcrumbs, trans } from '@/utils';
import { Button, Section, Select } from '@/Components';
import { route } from 'ziggy-js';

interface Props {
  enrollment: Enrollment;
  classes: ClassModel[];
}

export default function EnrollmentTransfer({ enrollment, classes }: Props) {
  const [formData, setFormData] = useState<TransferStudentFormData>({
    new_class_id: 0,
  });

  const [errors, setErrors] = useState<Partial<Record<keyof TransferStudentFormData, string>>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleChange = (field: keyof TransferStudentFormData, value: number) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    setErrors((prev) => ({ ...prev, [field]: undefined }));
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    router.post(route('admin.enrollments.transfer.store', enrollment.id), formData as any, {
      onError: (errors) => {
        setErrors(errors as any);
        setIsSubmitting(false);
      },
      onSuccess: () => {
        setIsSubmitting(false);
      },
    });
  };

  const handleCancel = () => {
    router.visit(route('admin.enrollments.show', enrollment.id));
  };

  const selectedClass = classes.find(c => c.id === formData.new_class_id);
  const availableSlots = selectedClass
    ? (selectedClass.max_students - (selectedClass.active_enrollments_count || 0))
    : 0;

  const availableClasses = classes.filter(c =>
    c.id !== enrollment.class_id &&
    c.academic_year_id === enrollment.class?.academic_year_id
  );

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.enrollments.transfer_title')}
      breadcrumb={breadcrumbs.admin.transferEnrollment(enrollment)}
    >
      <Section
        title={trans('admin_pages.enrollments.transfer_title')}
        subtitle={trans('admin_pages.enrollments.transfer_subtitle')}
      >
        <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div className="text-sm">
            <div className="font-medium text-blue-900 mb-2">
              {trans('admin_pages.enrollments.current_enrollment')}
            </div>
            <div className="text-blue-800">
              <span className="font-medium">{enrollment.student?.name}</span> → {' '}
              <span className="font-medium">
                {enrollment.class?.name}
              </span>
              {' '}({enrollment.class?.level?.name})
            </div>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="grid grid-cols-1 gap-6">
            <Select
              label={trans('admin_pages.enrollments.transfer_to_class')}
              name="new_class_id"
              value={formData.new_class_id}
              onChange={(value) => handleChange('new_class_id', value as number)}
              error={errors.new_class_id}
              required
              searchable
              options={[
                { value: 0, label: trans('admin_pages.enrollments.select_target_class') },
                ...availableClasses.map((classItem) => ({
                  value: classItem.id,
                  label: `${classItem.name} - ${classItem.level?.name} (${classItem.active_enrollments_count}/${classItem.max_students})`
                }))
              ]}
            />

            {selectedClass && (
              <div className={`p-4 rounded-lg ${availableSlots > 0 ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'}`}>
                <div className="text-sm">
                  <span className="font-medium">
                    {trans('admin_pages.enrollments.available_slots')}:
                  </span>
                  <span className={`ml-2 ${availableSlots > 0 ? 'text-green-900' : 'text-red-900'}`}>
                    {availableSlots} / {selectedClass.max_students}
                  </span>
                </div>
                {availableSlots === 0 && (
                  <div className="mt-2 text-sm text-red-700">
                    {trans('admin_pages.enrollments.class_full')}
                  </div>
                )}
              </div>
            )}

            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <div className="text-sm text-yellow-800">
                {trans('admin_pages.enrollments.transfer_warning')}
              </div>
            </div>
          </div>

          <div className="flex justify-end space-x-3 pt-6">
            <Button type="button" variant="outline" color="secondary" onClick={handleCancel} disabled={isSubmitting}>
              {trans('admin_pages.common.cancel')}
            </Button>
            <Button
              type="submit"
              variant="solid"
              color="primary"
              disabled={isSubmitting || availableSlots === 0}
            >
              {isSubmitting ? trans('admin_pages.enrollments.transferring') : trans('admin_pages.enrollments.transfer_button')}
            </Button>
          </div>
        </form>
      </Section>
    </AuthenticatedLayout>
  );
}
