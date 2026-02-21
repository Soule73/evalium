import { type FormEvent, useCallback, useMemo, useState } from 'react';
import { Button, Section } from '@evalium/ui';
import { ArrowRightIcon, ExclamationTriangleIcon } from '@heroicons/react/24/outline';
import {
    AcademicYearForm,
    buildDefaultSemesters,
    toDateInputValue,
} from '@/Components/features/academic-years';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useAcademicYearWizard } from '@/contexts/AcademicYearWizardContext';
import type { AcademicYear, AcademicYearFormData } from '@/types';

interface AcademicYearFormStepProps {
    currentYear: AcademicYear | null;
    futureYearExists: boolean;
}

function addOneYear(dateStr: string): string {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    date.setFullYear(date.getFullYear() + 1);
    return date.toISOString().split('T')[0];
}

function buildNextYearName(startDate: string, endDate: string): string {
    if (!startDate || !endDate) return '';
    const startYear = new Date(startDate).getFullYear() + 1;
    const endYear = new Date(endDate).getFullYear() + 1;
    if (startYear === endYear) return String(startYear);
    return `${startYear}-${endYear}`;
}

function buildInitialFormData(currentYear: AcademicYear | null): AcademicYearFormData {
    if (!currentYear) {
        return {
            name: '',
            start_date: '',
            end_date: '',
            is_current: false,
            semesters: buildDefaultSemesters('', ''),
        };
    }

    const nextStart = addOneYear(toDateInputValue(currentYear.start_date));
    const nextEnd = addOneYear(toDateInputValue(currentYear.end_date));

    return {
        name: buildNextYearName(currentYear.start_date, currentYear.end_date),
        start_date: nextStart,
        end_date: nextEnd,
        is_current: false,
        semesters: buildDefaultSemesters(nextStart, nextEnd),
    };
}

/**
 * Step 1 of the academic year creation wizard.
 * Configures the new academic year with pre-filled dates from the current year.
 */
export function AcademicYearFormStep({ currentYear, futureYearExists }: AcademicYearFormStepProps) {
    const { t } = useTranslations();
    const { state, actions } = useAcademicYearWizard();

    const [formData, setFormData] = useState<AcademicYearFormData>(() =>
        state.formData.name ? state.formData : buildInitialFormData(currentYear),
    );

    const handleSubmit = useCallback(
        (e: FormEvent) => {
            e.preventDefault();
            actions.setFormData(formData);
            actions.goToStep(2);
        },
        [formData, actions],
    );

    const handleCancel = useCallback(() => {
        history.back();
    }, []);

    const translations = useMemo(
        () => ({
            next: t('admin_pages.academic_years.wizard_next'),
            futureTitle: t('admin_pages.academic_years.wizard_future_year_exists_title'),
            futureMessage: t('admin_pages.academic_years.wizard_future_year_exists_message'),
            noCurrentTitle: t('admin_pages.academic_years.wizard_no_current_year_title'),
            noCurrentMessage: t('admin_pages.academic_years.wizard_no_current_year_message'),
            formTitle: t('admin_pages.academic_years.wizard_form_step_title'),
            formSubtitle: t('admin_pages.academic_years.wizard_form_step_subtitle'),
        }),
        [t],
    );

    const actionsSlot = (
        <div className="flex items-center gap-2">
            <Button
                type="button"
                variant="outline"
                color="secondary"
                size="sm"
                onClick={handleCancel}
            >
                {t('commons/ui.cancel')}
            </Button>
            <Button type="submit" variant="solid" color="primary" size="sm">
                {translations.next}
                <ArrowRightIcon className="ml-1 h-4 w-4" />
            </Button>
        </div>
    );

    if (futureYearExists) {
        return (
            <Section title={translations.futureTitle}>
                <div className="flex items-start gap-3">
                    <ExclamationTriangleIcon className="h-5 w-5 shrink-0 text-amber-500 mt-0.5" />
                    <p className="text-sm text-gray-600">{translations.futureMessage}</p>
                </div>
            </Section>
        );
    }

    if (!currentYear) {
        return (
            <Section title={translations.noCurrentTitle}>
                <div className="flex items-start gap-3">
                    <ExclamationTriangleIcon className="h-5 w-5 shrink-0 text-amber-500 mt-0.5" />
                    <p className="text-sm text-gray-600">{translations.noCurrentMessage}</p>
                </div>
            </Section>
        );
    }

    return (
        <AcademicYearForm
            formData={formData}
            errors={{}}
            isSubmitting={false}
            sectionTitle={translations.formTitle}
            sectionSubtitle={translations.formSubtitle}
            submitLabel={translations.next}
            submittingLabel={translations.next}
            onFormDataChange={setFormData}
            onErrorsClear={() => {}}
            onSubmit={handleSubmit}
            onCancel={handleCancel}
            showNameHelper
            actionsSlot={actionsSlot}
        />
    );
}
