import { useCallback, useMemo, useState } from 'react';
import { Button, Section } from '@evalium/ui';
import { ArrowLeftIcon, ArrowRightIcon } from '@heroicons/react/24/outline';
import { DataTable } from '@/Components/shared/datatable/DataTable';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useAcademicYearWizard } from '@/contexts/AcademicYearWizardContext';
import type { AcademicYear, ClassModel } from '@/types';
import type { DataTableConfig } from '@/types/datatable';

interface ClassDuplicationStepProps {
    currentYear: AcademicYear;
}

/**
 * Step 2 of the academic year creation wizard.
 * Displays classes from the current academic year so the admin can
 * choose which ones to duplicate to the new year.
 * All classes are pre-selected by default.
 */
export function ClassDuplicationStep({ currentYear }: ClassDuplicationStepProps) {
    const { t } = useTranslations();
    const { state, actions } = useAcademicYearWizard();

    const classes = currentYear.classes ?? [];

    const initialIds = useMemo<(number | string)[]>(() => {
        if (state.selectedClassIds.length > 0) {
            return state.selectedClassIds;
        }
        return classes.map((c) => c.id);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const [selectedIds, setSelectedIds] = useState<(number | string)[]>(initialIds);

    const handleSelectionChange = useCallback((ids: (number | string)[]) => {
        setSelectedIds(ids);
    }, []);

    const handleNext = useCallback(() => {
        actions.setSelectedClassIds(selectedIds.map(Number));
        actions.goToStep(3);
    }, [selectedIds, actions]);

    const handleBack = useCallback(() => {
        actions.goToStep(1);
    }, [actions]);

    const config: DataTableConfig<ClassModel> = useMemo(
        () => ({
            enableSelection: true,
            columns: [
                {
                    key: 'name',
                    label: t('admin_pages.academic_years.wizard_class_column_name'),
                    render: (cls) => (
                        <span className="text-sm font-medium text-gray-900">{cls.name}</span>
                    ),
                },
                {
                    key: 'level',
                    label: t('admin_pages.academic_years.wizard_class_column_level'),
                    render: (cls) => (
                        <span className="text-sm text-gray-500">{cls.level?.name ?? '—'}</span>
                    ),
                },
            ],
            emptyState: {
                title: t('admin_pages.academic_years.wizard_class_step_no_classes'),
                subtitle: '',
            },
        }),
        [t],
    );

    const subtitle = t('admin_pages.academic_years.wizard_class_step_subtitle', {
        year: currentYear.name,
    });

    const actionsSlot = (
        <div className="flex items-center gap-2">
            <Button
                type="button"
                variant="outline"
                color="secondary"
                size="sm"
                onClick={handleBack}
            >
                <ArrowLeftIcon className="mr-1 h-4 w-4" />
                {t('commons/ui.back')}
            </Button>
            <Button type="button" variant="solid" color="primary" size="sm" onClick={handleNext}>
                {t('commons/ui.next')}
                <ArrowRightIcon className="ml-1 h-4 w-4" />
            </Button>
        </div>
    );

    return (
        <Section
            title={t('admin_pages.academic_years.wizard_class_step_title')}
            subtitle={subtitle}
            actions={actionsSlot}
        >
            <DataTable
                data={classes}
                config={config}
                selectedIds={selectedIds}
                onSelectionChange={handleSelectionChange}
            />
        </Section>
    );
}
