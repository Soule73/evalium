import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { ClassSubject, ReplaceTeacherFormData, User } from '@/types';
import { breadcrumbs, trans, formatDate } from '@/utils';
import { Button, Section, Select } from '@/Components';
import { route } from 'ziggy-js';

interface Props {
  classSubject: ClassSubject;
  teachers: User[];
}

export default function ClassSubjectReplaceTeacher({ classSubject, teachers }: Props) {
  const [formData, setFormData] = useState<ReplaceTeacherFormData>({
    new_teacher_id: 0,
    effective_date: new Date().toISOString().split('T')[0],
  });

  const [errors, setErrors] = useState<Partial<Record<keyof ReplaceTeacherFormData, string>>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleChange = (field: keyof ReplaceTeacherFormData, value: number | string) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    setErrors((prev) => ({ ...prev, [field]: undefined }));
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    router.post(route('admin.class-subjects.replace-teacher.store', classSubject.id), formData as any, {
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

  const availableTeachers = teachers.filter(t => t.id !== classSubject.teacher_id);

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.class_subjects.replace_teacher_title')}
      breadcrumb={breadcrumbs.admin.replaceTeacherClassSubject(classSubject)}
    >
      <Section
        title={trans('admin_pages.class_subjects.replace_teacher_title')}
        subtitle={trans('admin_pages.class_subjects.replace_teacher_subtitle')}
      >
        <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div className="text-sm">
            <div className="font-medium text-blue-900 mb-2">
              {trans('admin_pages.class_subjects.current_assignment')}
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
                <span className="font-medium">{trans('admin_pages.class_subjects.current_teacher')}:</span>{' '}
                {classSubject.teacher?.name}
              </div>
              <div>
                <span className="font-medium">{trans('admin_pages.class_subjects.since')}:</span>{' '}
                {formatDate(classSubject.valid_from)}
              </div>
            </div>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="grid grid-cols-1 gap-6">
            <Select
              label={trans('admin_pages.class_subjects.new_teacher')}
              name="new_teacher_id"
              value={formData.new_teacher_id}
              onChange={(value) => handleChange('new_teacher_id', value as number)}
              error={errors.new_teacher_id}
              required
              searchable
              options={[
                { value: 0, label: trans('admin_pages.class_subjects.select_new_teacher') },
                ...availableTeachers.map((teacher) => ({
                  value: teacher.id,
                  label: `${teacher.name} (${teacher.email})`
                }))
              ]}
            />

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-2">
                {trans('admin_pages.class_subjects.effective_date')}
              </label>
              <input
                type="date"
                name="effective_date"
                value={formData.effective_date}
                onChange={(e) => handleChange('effective_date', e.target.value)}
                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                required
              />
              {errors.effective_date && (
                <p className="mt-1 text-sm text-red-600">{errors.effective_date}</p>
              )}
            </div>

            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <div className="text-sm text-yellow-800">
                {trans('admin_pages.class_subjects.replace_teacher_warning')}
              </div>
            </div>
          </div>

          <div className="flex justify-end space-x-3 pt-6">
            <Button type="button" variant="outline" color="secondary" onClick={handleCancel} disabled={isSubmitting}>
              {trans('admin_pages.common.cancel')}
            </Button>
            <Button type="submit" variant="solid" color="primary" disabled={isSubmitting}>
              {isSubmitting ? trans('admin_pages.class_subjects.replacing') : trans('admin_pages.class_subjects.replace_button')}
            </Button>
          </div>
        </form>
      </Section>
    </AuthenticatedLayout>
  );
}
