import { useCallback, useMemo, useState } from 'react';
import axios from 'axios';
import { route } from 'ziggy-js';
import { Button, Section } from '@evalium/ui';
import { ArrowLeftIcon, CheckIcon } from '@heroicons/react/24/outline';
import { useTranslations } from '@/hooks/shared/useTranslations';
import {
    useAcademicYearWizard,
    type AcademicYearWizardResult,
} from '@/contexts/AcademicYearWizardContext';
import type { AcademicYear } from '@/types';

interface AcademicYearConfirmStepProps {
    currentYear: AcademicYear | null;
}

/**
 * Step 3 of the academic year creation wizard.
 * Displays a summary of what will be created and submits via the wizard API endpoint.
 */
export function AcademicYearConfirmStep({ currentYear }: AcademicYearConfirmStepProps) {
    const { t } = useTranslations();
    const { state, actions } = useAcademicYearWizard();
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [submitError, setSubmitError] = useState<string | null>(null);

    const handleBack = useCallback(() => {
        actions.goToStep(2);
    }, [actions]);

    const handleConfirm = useCallback(async () => {
        setIsSubmitting(true);
        setSubmitError(null);

        try {
            const response = await axios.post<AcademicYearWizardResult>(
                route('admin.academic-years.wizard-store'),
                {
                    ...state.formData,
                    class_ids: state.selectedClassIds,
                },
            );
            actions.setResult(response.data);
        } catch (error: unknown) {
            if (axios.isAxiosError(error) && error.response?.data?.message) {
                setSubmitError(error.response.data.message as string);
            } else if (axios.isAxiosError(error) && error.response?.data?.errors) {
                const errors = error.response.data.errors as Record<string, string[]>;
                const firstError = Object.values(errors).flat()[0];
                setSubmitError(firstError ?? t('commons/ui.error'));
            } else {
                setSubmitError(t('admin_pages.academic_years.wizard_submit_error'));
            }
        } finally {
            setIsSubmitting(false);
        }
    }, [state.formData, state.selectedClassIds, actions, t]);

    const classCount = state.selectedClassIds.length;
    const selectedClasses = useMemo(
        () => (currentYear?.classes ?? []).filter((c) => state.selectedClassIds.includes(c.id)),
        [currentYear?.classes, state.selectedClassIds],
    );

    const actionsSlot = (
        <>
            <Button
                type="button"
                variant="outline"
                color="secondary"
                onClick={handleBack}
                disabled={isSubmitting}
            >
                <ArrowLeftIcon className="h-4 w-4 mr-1" />
                {t('commons/ui.back')}
            </Button>
            <Button
                type="button"
                variant="solid"
                color="primary"
                onClick={handleConfirm}
                disabled={isSubmitting}
            >
                <CheckIcon className="h-4 w-4 mr-1" />
                {isSubmitting
                    ? t('admin_pages.academic_years.wizard_creating')
                    : t('admin_pages.academic_years.wizard_confirm')}
            </Button>
        </>
    );

    return (
        <div className="space-y-6">
            <Section
                title={t('admin_pages.academic_years.wizard_step_3_label')}
                actions={actionsSlot}
            >
                {submitError && (
                    <div className="mb-4 rounded-lg bg-red-50 border border-red-200 p-4">
                        <p className="text-sm text-red-700">{submitError}</p>
                    </div>
                )}

                <dl className="space-y-4">
                    <div className="grid grid-cols-3 gap-4 border-b border-gray-100 pb-3">
                        <dt className="text-sm font-medium text-gray-500">
                            {t('admin_pages.academic_years.name_label')}
                        </dt>
                        <dd className="col-span-2 text-sm text-gray-900 font-semibold">
                            {state.formData.name}
                        </dd>
                    </div>
                    <div className="grid grid-cols-3 gap-4 border-b border-gray-100 pb-3">
                        <dt className="text-sm font-medium text-gray-500">
                            {t('admin_pages.academic_years.start_date_label')}
                        </dt>
                        <dd className="col-span-2 text-sm text-gray-900">
                            {state.formData.start_date}
                        </dd>
                    </div>
                    <div className="grid grid-cols-3 gap-4 border-b border-gray-100 pb-3">
                        <dt className="text-sm font-medium text-gray-500">
                            {t('admin_pages.academic_years.end_date_label')}
                        </dt>
                        <dd className="col-span-2 text-sm text-gray-900">
                            {state.formData.end_date}
                        </dd>
                    </div>
                    <div className="grid grid-cols-3 gap-4 border-b border-gray-100 pb-3">
                        <dt className="text-sm font-medium text-gray-500">
                            {t('admin_pages.academic_years.semesters_title')}
                        </dt>
                        <dd className="col-span-2 text-sm text-gray-900">
                            {state.formData.semesters.length}{' '}
                            {t('admin_pages.academic_years.semesters_count')}
                        </dd>
                    </div>
                    <div className="grid grid-cols-3 gap-4">
                        <dt className="text-sm font-medium text-gray-500">
                            {t('admin_pages.academic_years.wizard_class_step_title')}
                        </dt>
                        <dd className="col-span-2 text-sm text-gray-900">
                            {classCount === 0 ? (
                                <span className="text-gray-400">
                                    {t('admin_pages.academic_years.wizard_result_no_classes')}
                                </span>
                            ) : (
                                <ul className="space-y-1">
                                    {selectedClasses.map((cls) => (
                                        <li key={cls.id} className="flex items-center gap-2">
                                            <span className="text-gray-700">{cls.name}</span>
                                            {cls.level && (
                                                <span className="text-xs text-gray-400">
                                                    ({cls.level.name})
                                                </span>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </dd>
                    </div>
                </dl>
            </Section>
        </div>
    );
}
