import { type FormEvent, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { type ClassModel, type Subject, type User, type Semester } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button, Modal, Select, Input } from '@/Components';
import { route } from 'ziggy-js';

interface ClassSubjectFormData {
    classes: ClassModel[];
    subjects: Subject[];
    teachers: User[];
    semesters: Semester[];
}

interface ClassSubjectFormValues {
    class_id: number;
    subject_id: number;
    teacher_id: number | null;
    semester_id?: number;
    coefficient: number;
}

interface CreateClassSubjectModalProps {
    isOpen: boolean;
    onClose: () => void;
    formData: ClassSubjectFormData;
    classId?: number;
    redirectTo?: string;
}

const buildInitialValues = (classId?: number): ClassSubjectFormValues => ({
    class_id: classId ?? 0,
    subject_id: 0,
    teacher_id: null,
    semester_id: undefined,
    coefficient: 1,
});

/**
 * Modal component for creating a new class-subject assignment.
 * When classId is provided, the class selector is hidden and the class is pre-filled.
 * Teacher selection is optional — an assignment can be created without a teacher and assigned later.
 */
export function CreateClassSubjectModal({
    isOpen,
    onClose,
    formData,
    classId,
    redirectTo,
}: CreateClassSubjectModalProps) {
    const [formValues, setFormValues] = useState<ClassSubjectFormValues>(() =>
        buildInitialValues(classId),
    );
    const [formErrors, setFormErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);
    const { t } = useTranslations();

    const classOptions = useMemo(
        () => [
            { value: 0, label: t('admin_pages.class_subjects.select_class') },
            ...formData.classes.map((classItem) => ({
                value: classItem.id,
                label: classItem.name,
            })),
        ],
        [formData.classes, t],
    );

    const subjectOptions = useMemo(
        () => [
            { value: 0, label: t('admin_pages.class_subjects.select_subject') },
            ...formData.subjects.map((subject) => ({
                value: subject.id,
                label: `${subject.code} - ${subject.name}`,
            })),
        ],
        [formData.subjects, t],
    );

    const teacherOptions = useMemo(
        () => [
            { value: 0, label: t('admin_pages.class_subjects.no_teacher_yet') },
            ...formData.teachers.map((teacher) => ({
                value: teacher.id,
                label: `${teacher.name} (${teacher.email})`,
            })),
        ],
        [formData.teachers, t],
    );

    const semesterOptions = useMemo(
        () => [
            { value: 0, label: t('admin_pages.class_subjects.all_year') },
            ...(formData.semesters?.map((semester) => ({
                value: semester.id,
                label: `${t('admin_pages.class_subjects.semester')} ${semester.order_number}`,
            })) || []),
        ],
        [formData.semesters, t],
    );

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setFormErrors({});

        const payload = {
            class_id: classId ?? formValues.class_id,
            subject_id: formValues.subject_id,
            teacher_id: formValues.teacher_id || null,
            semester_id: formValues.semester_id || null,
            coefficient: formValues.coefficient,
            ...(redirectTo ? { redirect_to: redirectTo } : {}),
        };

        router.post(route('admin.class-subjects.store'), payload, {
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
        setFormValues(buildInitialValues(classId));
        setFormErrors({});
        onClose();
    };

    const isFormValid =
        (classId !== undefined ? classId > 0 : formValues.class_id !== 0) &&
        formValues.subject_id !== 0;

    return (
        <Modal
            isOpen={isOpen}
            onClose={resetAndClose}
            title={t('admin_pages.class_subjects.create_title')}
            size="2xl"
            isCloseableInside={false}
        >
            <form onSubmit={handleSubmit} className="space-y-4">
                {classId === undefined && (
                    <Select
                        label={t('admin_pages.class_subjects.class')}
                        name="class_id"
                        value={formValues.class_id}
                        onChange={(value) =>
                            setFormValues((prev) => ({ ...prev, class_id: value as number }))
                        }
                        error={formErrors.class_id}
                        required
                        searchable
                        options={classOptions}
                    />
                )}

                <Select
                    label={t('admin_pages.class_subjects.subject')}
                    name="subject_id"
                    value={formValues.subject_id}
                    onChange={(value) =>
                        setFormValues((prev) => ({ ...prev, subject_id: value as number }))
                    }
                    error={formErrors.subject_id}
                    required
                    searchable
                    options={subjectOptions}
                />

                <Select
                    label={t('admin_pages.class_subjects.teacher')}
                    name="teacher_id"
                    value={formValues.teacher_id ?? 0}
                    onChange={(value) =>
                        setFormValues((prev) => ({
                            ...prev,
                            teacher_id: value === 0 ? null : (value as number),
                        }))
                    }
                    error={formErrors.teacher_id}
                    searchable
                    options={teacherOptions}
                />

                <Input
                    label={t('admin_pages.class_subjects.coefficient')}
                    name="coefficient"
                    type="number"
                    min="0.5"
                    max="10"
                    step="0.5"
                    value={formValues.coefficient}
                    onChange={(e) =>
                        setFormValues((prev) => ({
                            ...prev,
                            coefficient: parseFloat(e.target.value) || 0,
                        }))
                    }
                    error={formErrors.coefficient}
                    required
                />

                {formData.semesters && formData.semesters.length > 0 && (
                    <Select
                        label={t('admin_pages.class_subjects.semester')}
                        name="semester_id"
                        value={formValues.semester_id || 0}
                        onChange={(value) =>
                            setFormValues((prev) => ({
                                ...prev,
                                semester_id: value === 0 ? undefined : (value as number),
                            }))
                        }
                        error={formErrors.semester_id}
                        options={semesterOptions}
                    />
                )}

                <div className="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                    <div className="text-sm text-indigo-800">
                        {classId !== undefined
                            ? t('admin_pages.class_subjects.create_info_class_scoped')
                            : t('admin_pages.class_subjects.create_info')}
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
                        disabled={isSubmitting || !isFormValid}
                    >
                        {isSubmitting
                            ? t('admin_pages.class_subjects.creating')
                            : t('admin_pages.class_subjects.create_button')}
                    </Button>
                </div>
            </form>
        </Modal>
    );
}
