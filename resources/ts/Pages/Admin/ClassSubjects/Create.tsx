import { FormEvent, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { ClassSubjectFormData, ClassModel, Subject, Semester, User } from '@/types';
import { breadcrumbs, trans } from '@/utils';
import { Button, Input, Section, Select } from '@/Components';
import { route } from 'ziggy-js';

interface Props {
  classes: ClassModel[];
  subjects: Subject[];
  teachers: User[];
  semesters?: Semester[];
}

export default function ClassSubjectCreate({ classes, subjects, teachers, semesters }: Props) {
  const [formData, setFormData] = useState<ClassSubjectFormData>({
    class_id: 0,
    subject_id: 0,
    teacher_id: 0,
    semester_id: undefined,
    coefficient: 1,
  });

  const [errors, setErrors] = useState<Partial<Record<keyof ClassSubjectFormData, string>>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleChange = (field: keyof ClassSubjectFormData, value: number | undefined) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    setErrors((prev) => ({ ...prev, [field]: undefined }));
  };

  const handleSubmit = (e: FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);

    router.post(route('admin.class-subjects.store'), formData as any, {
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
    router.visit(route('admin.class-subjects.index'));
  };

  const classesItem = useMemo(() => [
    { value: 0, label: trans('admin_pages.class_subjects.select_class') },
    ...classes.map((classItem) => ({
      value: classItem.id,
      label: `${classItem.name} - ${classItem.level?.name}(${(classItem.level?.description || '')?.substring(0, 30)})`
    }))
  ], [classes]);

  const subjectsItem = useMemo(() => [
    { value: 0, label: trans('admin_pages.class_subjects.select_subject') },
    ...subjects.map((subject) => ({
      value: subject.id,
      label: `${subject.code} - ${subject.name}`
    }))
  ], [subjects]);

  const teachersItem = useMemo(() => [
    { value: 0, label: trans('admin_pages.class_subjects.select_teacher') },
    ...teachers.map((teacher) => ({
      value: teacher.id,
      label: `${teacher.name} (${teacher.email})`
    }))
  ], [teachers]);


  const semestersItem = useMemo(() => [
    { value: 0, label: trans('admin_pages.class_subjects.all_year') },
    ...(semesters?.map((semester) => ({
      value: semester.id,
      label: `${trans('admin_pages.class_subjects.semester')} ${semester.order_number}`
    })) || [])
  ], [semesters]);

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.class_subjects.create_title')}
      breadcrumb={breadcrumbs.admin.createClassSubject()}
    >
      <Section
        title={trans('admin_pages.class_subjects.create_title')}
        subtitle={trans('admin_pages.class_subjects.create_subtitle')}
      >
        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Select
              label={trans('admin_pages.class_subjects.class')}
              name="class_id"
              value={formData.class_id}
              onChange={(value) => handleChange('class_id', value as number)}
              error={errors.class_id}
              required
              searchable
              options={classesItem}
            />

            <Select
              label={trans('admin_pages.class_subjects.subject')}
              name="subject_id"
              value={formData.subject_id}
              onChange={(value) => handleChange('subject_id', value as number)}
              error={errors.subject_id}
              required
              searchable
              options={subjectsItem}
            />

            <Select
              label={trans('admin_pages.class_subjects.teacher')}
              name="teacher_id"
              value={formData.teacher_id}
              onChange={(value) => handleChange('teacher_id', value as number)}
              error={errors.teacher_id}
              required
              searchable
              options={teachersItem}
            />

            <Input
              label={trans('admin_pages.class_subjects.coefficient')}
              name="coefficient"
              type="number"
              min="0.5"
              max="10"
              step="0.5"
              value={formData.coefficient}
              onChange={(e) => handleChange('coefficient', parseFloat(e.target.value))}
              error={errors.coefficient}
              required
            />

            {semesters && semesters.length > 0 && (
              <Select
                label={trans('admin_pages.class_subjects.semester')}
                name="semester_id"
                value={formData.semester_id || 0}
                onChange={(value) => handleChange('semester_id', value === 0 ? undefined : value as number)}
                error={errors.semester_id}
                options={semestersItem}
              />
            )}
          </div>

          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div className="text-sm text-blue-800">
              {trans('admin_pages.class_subjects.create_info')}
            </div>
          </div>

          <div className="flex justify-end space-x-3 pt-6 border-t">
            <Button type="button" variant="outline" color="secondary" onClick={handleCancel} disabled={isSubmitting}>
              {trans('admin_pages.common.cancel')}
            </Button>
            <Button type="submit" variant="solid" color="primary" disabled={isSubmitting}>
              {isSubmitting ? trans('admin_pages.class_subjects.creating') : trans('admin_pages.class_subjects.create_button')}
            </Button>
          </div>
        </form>
      </Section>
    </AuthenticatedLayout>
  );
}
