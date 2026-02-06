import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { ClassSubject, UpdateCoefficientFormData } from '@/types';
import { breadcrumbs, trans } from '@/utils';
import { Button, Input, Section } from '@/Components';
import { route } from 'ziggy-js';

interface Props {
  classSubject: ClassSubject;
}

export default function ClassSubjectUpdateCoefficient({ classSubject }: Props) {
  const [formData, setFormData] = useState<UpdateCoefficientFormData>({
    coefficient: classSubject.coefficient,
  });

  const [errors, setErrors] = useState<Partial<Record<keyof UpdateCoefficientFormData, string>>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleChange = (value: number) => {
    setFormData({ coefficient: value });
    setErrors({});
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    router.put(route('admin.class-subjects.update-coefficient', classSubject.id), formData as any, {
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
    router.visit(route('admin.class-subjects.show', classSubject.id));
  };

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.class_subjects.update_coefficient_title')}
      breadcrumb={breadcrumbs.admin.updateCoefficientClassSubject(classSubject)}
    >
      <Section
        title={trans('admin_pages.class_subjects.update_coefficient_title')}
        subtitle={trans('admin_pages.class_subjects.update_coefficient_subtitle')}
      >
        <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div className="text-sm">
            <div className="font-medium text-blue-900 mb-2">
              {trans('admin_pages.class_subjects.assignment_info')}
            </div>
            <div className="text-blue-800 space-y-1">
              <div>
                <span className="font-medium">{trans('admin_pages.class_subjects.class')}:</span>{' '}
                {classSubject.class?.display_name || classSubject.class?.name}
              </div>
              <div>
                <span className="font-medium">{trans('admin_pages.class_subjects.subject')}:</span>{' '}
                {classSubject.subject?.code} - {classSubject.subject?.name}
              </div>
              <div>
                <span className="font-medium">{trans('admin_pages.class_subjects.teacher')}:</span>{' '}
                {classSubject.teacher?.name}
              </div>
              <div>
                <span className="font-medium">{trans('admin_pages.class_subjects.current_coefficient')}:</span>{' '}
                {classSubject.coefficient}
              </div>
            </div>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="grid grid-cols-1 gap-6">
            <Input
              label={trans('admin_pages.class_subjects.new_coefficient')}
              name="coefficient"
              type="number"
              min="0.5"
              max="10"
              step="0.5"
              value={formData.coefficient}
              onChange={(e) => handleChange(parseFloat(e.target.value))}
              error={errors.coefficient}
              required
            />

            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <div className="text-sm text-yellow-800">
                {trans('admin_pages.class_subjects.update_coefficient_warning')}
              </div>
            </div>
          </div>

          <div className="flex justify-end space-x-3 pt-6">
            <Button type="button" variant="outline" color="secondary" onClick={handleCancel} disabled={isSubmitting}>
              {trans('admin_pages.common.cancel')}
            </Button>
            <Button type="submit" variant="solid" color="primary" disabled={isSubmitting}>
              {isSubmitting ? trans('admin_pages.class_subjects.updating') : trans('admin_pages.class_subjects.update_button')}
            </Button>
          </div>
        </form>
      </Section>
    </AuthenticatedLayout>
  );
}
