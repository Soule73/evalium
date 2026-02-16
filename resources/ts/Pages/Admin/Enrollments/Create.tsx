import { type FormEvent, useMemo, useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { type FormDataConvertible } from '@inertiajs/core';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type EnrollmentFormData, type ClassModel, type User } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section, Select } from '@/Components';
import { UserAvatar } from '@/Components/layout/UserAvatar';
import { RoleBadge } from '@/Components/layout/RoleBadge';
import { CreateStudentModal } from '@/Components/features';
import { route } from 'ziggy-js';
import { UserPlusIcon } from '@heroicons/react/24/outline';

interface Props {
    classes: ClassModel[];
    students: User[];
}

export default function EnrollmentCreate({ classes, students: initialStudents }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();
    const [students, setStudents] = useState<User[]>(initialStudents);
    const [formData, setFormData] = useState<EnrollmentFormData>({
        class_id: 0,
        student_id: 0,
    });

    const [errors, setErrors] = useState<Partial<Record<keyof EnrollmentFormData, string>>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [showCreateModal, setShowCreateModal] = useState(false);

    const handleChange = (field: keyof EnrollmentFormData, value: number) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
        setErrors((prev) => ({ ...prev, [field]: undefined }));
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        router.post(
            route('admin.enrollments.store'),
            formData as unknown as unknown as Record<string, FormDataConvertible>,
            {
                onError: (errors) => {
                    setErrors(errors as Partial<Record<keyof EnrollmentFormData, string>>);
                    setIsSubmitting(false);
                },
                onSuccess: () => {
                    setIsSubmitting(false);
                },
            },
        );
    };

    const handleCancel = () => {
        router.visit(route('admin.enrollments.index'));
    };

    const handleStudentCreated = useCallback(
        (newStudent: Pick<User, 'id' | 'name' | 'email' | 'avatar'>) => {
            const studentUser = newStudent as User;
            setStudents((prev) =>
                [...prev, studentUser].sort((a, b) => a.name.localeCompare(b.name)),
            );
            setFormData((prev) => ({ ...prev, student_id: newStudent.id }));
        },
        [],
    );

    const selectedStudent = students.find((s) => s.id === formData.student_id);
    const selectedClass = classes.find((c) => c.id === formData.class_id);
    const availableSlots = selectedClass
        ? selectedClass.max_students - (selectedClass.active_enrollments_count || 0)
        : 0;

    const enrolledStudents = selectedClass?.enrollments?.filter((e) => e.status === 'active') || [];

    const isStudentAlreadyEnrolled =
        selectedStudent && enrolledStudents.some((e) => e.student_id === selectedStudent.id);

    const translations = useMemo(
        () => ({
            createTitle: t('admin_pages.enrollments.create_title'),
            createSubtitle: t('admin_pages.enrollments.create_subtitle'),
            studentLabel: t('admin_pages.enrollments.student'),
            classLabel: t('admin_pages.enrollments.class'),
            availableSlots: t('admin_pages.enrollments.available_slots'),
            classFull: t('admin_pages.enrollments.class_full'),
            cancel: t('admin_pages.common.cancel'),
            creating: t('admin_pages.enrollments.creating'),
            createButton: t('admin_pages.enrollments.create_button'),
            quickCreate: t('admin_pages.enrollments.quick_create_student'),
            enrolledStudents: t('admin_pages.enrollments.enrolled_students'),
            noEnrolledStudents: t('admin_pages.enrollments.no_enrolled_students'),
            alreadyEnrolled: t('admin_pages.enrollments.already_enrolled_warning'),
            capacityLabel: t('admin_pages.enrollments.capacity'),
        }),
        [t],
    );

    const studentsItem = useMemo(
        () => [
            { value: 0, label: t('admin_pages.enrollments.select_student') },
            ...students.map((student) => ({
                value: student.id,
                label: `${student.name} (${student.email})`,
            })),
        ],
        [t, students],
    );

    const classesItem = useMemo(
        () => [
            { value: 0, label: t('admin_pages.enrollments.select_class') },
            ...classes.map((classItem) => ({
                value: classItem.id,
                label: `${classItem.name} - ${classItem.level?.name} (${(classItem.level?.description || '')?.substring(0, 30)})`,
            })),
        ],
        [t, classes],
    );

    const capacityPercentage = selectedClass
        ? Math.round(
              ((selectedClass.active_enrollments_count || 0) / selectedClass.max_students) * 100,
          )
        : 0;

    const capacityColor =
        capacityPercentage >= 100
            ? 'bg-red-500'
            : capacityPercentage >= 80
              ? 'bg-amber-500'
              : 'bg-emerald-500';

    return (
        <AuthenticatedLayout
            title={translations.createTitle}
            breadcrumb={breadcrumbs.admin.createEnrollment()}
        >
            <Section title={translations.createTitle} subtitle={translations.createSubtitle}>
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        <div className="space-y-4">
                            <div className="flex items-end gap-3">
                                <div className="flex-1">
                                    <Select
                                        label={translations.studentLabel}
                                        name="student_id"
                                        value={formData.student_id}
                                        onChange={(value) =>
                                            handleChange('student_id', value as number)
                                        }
                                        error={errors.student_id}
                                        required
                                        searchable
                                        options={studentsItem}
                                    />
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    color="primary"
                                    size="sm"
                                    onClick={() => setShowCreateModal(true)}
                                    className="mb-0.5"
                                >
                                    <UserPlusIcon className="h-4 w-4" />
                                </Button>
                            </div>

                            {selectedStudent && (
                                <div className="flex items-center gap-3 rounded-lg border border-indigo-200 bg-indigo-50 p-3">
                                    <UserAvatar
                                        name={selectedStudent.name}
                                        avatar={selectedStudent.avatar}
                                        size="lg"
                                    />
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-semibold text-gray-900 truncate">
                                            {selectedStudent.name}
                                        </p>
                                        <p className="text-xs text-gray-500 truncate">
                                            {selectedStudent.email}
                                        </p>
                                    </div>
                                    <RoleBadge role="student" />
                                </div>
                            )}

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
                        </div>

                        <div className="space-y-4">
                            {selectedClass && (
                                <>
                                    <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                                        <div className="mb-3 flex items-center justify-between">
                                            <span className="text-sm font-medium text-gray-700">
                                                {translations.capacityLabel}
                                            </span>
                                            <span
                                                className={`text-sm font-semibold ${availableSlots > 0 ? 'text-gray-900' : 'text-red-600'}`}
                                            >
                                                {selectedClass.active_enrollments_count || 0} /{' '}
                                                {selectedClass.max_students}
                                            </span>
                                        </div>

                                        <div className="mb-2 h-2.5 w-full overflow-hidden rounded-full bg-gray-200">
                                            <div
                                                className={`h-full rounded-full transition-all duration-300 ${capacityColor}`}
                                                style={{
                                                    width: `${Math.min(capacityPercentage, 100)}%`,
                                                }}
                                            />
                                        </div>

                                        <div className="flex items-center justify-between text-xs text-gray-500">
                                            <span>
                                                {translations.availableSlots}: {availableSlots}
                                            </span>
                                            <span>{capacityPercentage}%</span>
                                        </div>

                                        {availableSlots === 0 && (
                                            <div className="mt-3 rounded-md bg-red-50 p-2 text-sm text-red-700">
                                                {translations.classFull}
                                            </div>
                                        )}

                                        {isStudentAlreadyEnrolled && (
                                            <div className="mt-3 rounded-md bg-amber-50 p-2 text-sm text-amber-700">
                                                {translations.alreadyEnrolled}
                                            </div>
                                        )}
                                    </div>

                                    <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                                        <h4 className="mb-3 text-sm font-medium text-gray-700">
                                            {translations.enrolledStudents} (
                                            {enrolledStudents.length})
                                        </h4>
                                        {enrolledStudents.length === 0 ? (
                                            <p className="text-xs text-gray-400 italic">
                                                {translations.noEnrolledStudents}
                                            </p>
                                        ) : (
                                            <ul className="max-h-48 space-y-2 overflow-y-auto">
                                                {enrolledStudents.map((enrollment) => (
                                                    <li
                                                        key={enrollment.id}
                                                        className="flex items-center gap-2"
                                                    >
                                                        <UserAvatar
                                                            name={enrollment.student?.name || '?'}
                                                            avatar={enrollment.student?.avatar}
                                                            size="sm"
                                                        />
                                                        <span className="truncate text-xs text-gray-700">
                                                            {enrollment.student?.name}
                                                        </span>
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </div>
                                </>
                            )}
                        </div>
                    </div>

                    <div className="flex justify-end space-x-3 pt-6">
                        <Button
                            type="button"
                            variant="outline"
                            color="secondary"
                            onClick={handleCancel}
                            disabled={isSubmitting}
                        >
                            {translations.cancel}
                        </Button>
                        <Button
                            type="submit"
                            variant="solid"
                            color="primary"
                            disabled={
                                isSubmitting || availableSlots === 0 || !!isStudentAlreadyEnrolled
                            }
                        >
                            {isSubmitting ? translations.creating : translations.createButton}
                        </Button>
                    </div>
                </form>
            </Section>

            <CreateStudentModal
                isOpen={showCreateModal}
                onClose={() => setShowCreateModal(false)}
                onStudentCreated={handleStudentCreated}
            />
        </AuthenticatedLayout>
    );
}
