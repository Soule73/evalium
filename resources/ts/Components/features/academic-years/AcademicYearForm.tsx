import React, { type FormEvent, useCallback, useMemo } from 'react';
import { type AcademicYearFormData, type SemesterFormData } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button, Checkbox, Input, Section } from '@/Components';
import { PlusIcon, TrashIcon } from '@heroicons/react/24/outline';
import { buildDefaultSemesters } from './academicYearFormUtils';

interface AcademicYearFormProps {
    formData: AcademicYearFormData;
    errors: Record<string, string>;
    isSubmitting: boolean;
    sectionTitle: string;
    sectionSubtitle: string;
    submitLabel: string;
    submittingLabel: string;
    onFormDataChange: (data: AcademicYearFormData) => void;
    onErrorsClear: (field: string) => void;
    onSubmit: (e: FormEvent) => void;
    onCancel: () => void;
    warningMessage?: string;
    showNameHelper?: boolean;
    actionsSlot?: React.ReactNode;
}

export default function AcademicYearForm({
    formData,
    errors,
    isSubmitting,
    sectionTitle,
    sectionSubtitle,
    submitLabel,
    submittingLabel,
    onFormDataChange,
    onErrorsClear,
    onSubmit,
    onCancel,
    warningMessage,
    showNameHelper = false,
    actionsSlot,
}: AcademicYearFormProps) {
    const { t } = useTranslations();

    const handleChange = useCallback(
        (field: keyof Omit<AcademicYearFormData, 'semesters'>, value: string | boolean) => {
            const next = { ...formData, [field]: value };

            if (
                (field === 'start_date' || field === 'end_date') &&
                next.start_date &&
                next.end_date
            ) {
                const hasUserEdited = formData.semesters.some((s) => s.start_date || s.end_date);
                if (!hasUserEdited) {
                    next.semesters = buildDefaultSemesters(
                        next.start_date as string,
                        next.end_date as string,
                    );
                }
            }

            onFormDataChange(next);
            onErrorsClear(field);
        },
        [formData, onFormDataChange, onErrorsClear],
    );

    const handleSemesterChange = useCallback(
        (index: number, field: keyof SemesterFormData, value: string) => {
            const semesters = [...formData.semesters];
            semesters[index] = { ...semesters[index], [field]: value };
            onFormDataChange({ ...formData, semesters });
            onErrorsClear(`semesters.${index}.${field}`);
        },
        [formData, onFormDataChange, onErrorsClear],
    );

    const addSemester = useCallback(() => {
        onFormDataChange({
            ...formData,
            semesters: [
                ...formData.semesters,
                { name: `Semester ${formData.semesters.length + 1}`, start_date: '', end_date: '' },
            ],
        });
    }, [formData, onFormDataChange]);

    const removeSemester = useCallback(
        (index: number) => {
            onFormDataChange({
                ...formData,
                semesters: formData.semesters.filter((_, i) => i !== index),
            });
        },
        [formData, onFormDataChange],
    );

    const translations = useMemo(
        () => ({
            nameLabel: t('admin_pages.academic_years.name_label'),
            namePlaceholder: t('admin_pages.academic_years.name_placeholder'),
            nameHelper: t('admin_pages.academic_years.name_helper'),
            startDateLabel: t('admin_pages.academic_years.start_date_label'),
            endDateLabel: t('admin_pages.academic_years.end_date_label'),
            isCurrent: t('admin_pages.academic_years.is_current'),
            isCurrentHelper: t('admin_pages.academic_years.is_current_helper'),
            semestersTitle: t('admin_pages.academic_years.semesters_config_title'),
            semestersSubtitle: t('admin_pages.academic_years.semesters_config_subtitle'),
            semesterName: t('admin_pages.academic_years.semester_name_label'),
            addSemester: t('admin_pages.academic_years.add_semester'),
            removeSemester: t('admin_pages.academic_years.remove_semester'),
            cancel: t('commons/ui.cancel'),
        }),
        [t],
    );

    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <Section title={sectionTitle} subtitle={sectionSubtitle} actions={actionsSlot}>
                <div className="grid grid-cols-1 gap-6">
                    <Input
                        label={translations.nameLabel}
                        name="name"
                        value={formData.name}
                        onChange={(e) => handleChange('name', e.target.value)}
                        error={errors.name}
                        required
                        placeholder={translations.namePlaceholder}
                        helperText={showNameHelper ? translations.nameHelper : undefined}
                    />

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Input
                            label={translations.startDateLabel}
                            name="start_date"
                            type="date"
                            value={formData.start_date}
                            onChange={(e) => handleChange('start_date', e.target.value)}
                            error={errors.start_date}
                            required
                        />
                        <Input
                            label={translations.endDateLabel}
                            name="end_date"
                            type="date"
                            value={formData.end_date}
                            onChange={(e) => handleChange('end_date', e.target.value)}
                            error={errors.end_date}
                            required
                        />
                    </div>

                    <div>
                        <Checkbox
                            label={translations.isCurrent}
                            name="is_current"
                            checked={formData.is_current || false}
                            onChange={(e) => handleChange('is_current', e.target.checked)}
                        />
                        <p className="text-sm text-gray-500 mt-1">{translations.isCurrentHelper}</p>
                    </div>
                </div>

                {warningMessage && (
                    <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4">
                        <p className="text-sm text-yellow-800">{warningMessage}</p>
                    </div>
                )}
            </Section>

            <Section
                title={translations.semestersTitle}
                subtitle={translations.semestersSubtitle}
                actions={
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        color="primary"
                        onClick={addSemester}
                    >
                        <PlusIcon className="w-4 h-4 mr-1" />
                        {translations.addSemester}
                    </Button>
                }
            >
                {errors.semesters && (
                    <p className="text-sm text-red-600 mb-4">{errors.semesters}</p>
                )}

                <div className="space-y-4">
                    {formData.semesters.map((semester, index) => (
                        <div
                            key={semester.id ?? `new-${index}`}
                            className="border border-gray-200 rounded-lg p-4 bg-gray-50"
                        >
                            <div className="flex items-center justify-between mb-3">
                                <h4 className="text-sm font-medium text-gray-700">
                                    {t('admin_pages.academic_years.semester_number', {
                                        number: index + 1,
                                    })}
                                </h4>
                                {formData.semesters.length > 1 && (
                                    <button
                                        type="button"
                                        onClick={() => removeSemester(index)}
                                        className="text-red-500 hover:text-red-700 p-1"
                                        title={translations.removeSemester}
                                    >
                                        <TrashIcon className="w-4 h-4" />
                                    </button>
                                )}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <Input
                                    label={translations.semesterName}
                                    name={`semesters.${index}.name`}
                                    value={semester.name}
                                    onChange={(e) =>
                                        handleSemesterChange(index, 'name', e.target.value)
                                    }
                                    error={errors[`semesters.${index}.name`]}
                                    required
                                />
                                <Input
                                    label={translations.startDateLabel}
                                    name={`semesters.${index}.start_date`}
                                    type="date"
                                    value={semester.start_date}
                                    onChange={(e) =>
                                        handleSemesterChange(index, 'start_date', e.target.value)
                                    }
                                    error={errors[`semesters.${index}.start_date`]}
                                    required
                                />
                                <Input
                                    label={translations.endDateLabel}
                                    name={`semesters.${index}.end_date`}
                                    type="date"
                                    value={semester.end_date}
                                    onChange={(e) =>
                                        handleSemesterChange(index, 'end_date', e.target.value)
                                    }
                                    error={errors[`semesters.${index}.end_date`]}
                                    required
                                />
                            </div>
                        </div>
                    ))}
                </div>
            </Section>

            {!actionsSlot && (
                <div className="flex justify-end space-x-3 pt-2">
                    <Button
                        type="button"
                        variant="outline"
                        color="secondary"
                        size="sm"
                        onClick={onCancel}
                        disabled={isSubmitting}
                    >
                        {translations.cancel}
                    </Button>
                    <Button
                        type="submit"
                        variant="solid"
                        color="primary"
                        size="sm"
                        disabled={isSubmitting}
                    >
                        {isSubmitting ? submittingLabel : submitLabel}
                    </Button>
                </div>
            )}
        </form>
    );
}
