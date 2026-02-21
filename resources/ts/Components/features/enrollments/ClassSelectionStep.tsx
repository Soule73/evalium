import { useCallback, useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import { route } from 'ziggy-js';
import { DataTable } from '@/Components/shared/datatable';
import { Badge, Button, Section } from '@evalium/ui';
import { useTranslations } from '@/hooks';
import { useEnrollmentWizard } from '@/contexts/EnrollmentWizardContext';
import type { ClassModel } from '@/types';
import type { DataTableConfig, PaginationType } from '@/types/datatable';
import { ArrowRightIcon } from '@heroicons/react/24/outline';

interface ClassSelectionStepProps {
    selectedYearId: number | null;
}

/**
 * Step 1 of the enrollment wizard: select a class using a single-select DataTable.
 * All classes for the active academic year are loaded once and filtered client-side.
 */
export function ClassSelectionStep({ selectedYearId }: ClassSelectionStepProps) {
    const { t } = useTranslations();
    const { actions } = useEnrollmentWizard();

    const [classes, setClasses] = useState<ClassModel[]>([]);
    const [isLoading, setIsLoading] = useState(false);

    useEffect(() => {
        let cancelled = false;
        setIsLoading(true);

        axios
            .get<PaginationType<ClassModel>>(route('admin.enrollments.search-classes'), {
                params: { academic_year_id: selectedYearId, per_page: 500 },
            })
            .then((response) => {
                if (!cancelled) {
                    setClasses(response.data.data);
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setIsLoading(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [selectedYearId]);

    const handleSelectionChange = useCallback(
        (ids: (number | string)[]) => {
            if (ids.length === 0) {
                actions.setSelectedClass(null);
                return;
            }
            const found = classes.find((c) => c.id === Number(ids[0]));
            if (found) {
                actions.setSelectedClass(found);
            }
        },
        [classes, actions],
    );

    const handleNext = useCallback(() => {
        actions.goToStep(2);
    }, [actions]);

    const config: DataTableConfig<ClassModel> = useMemo(
        () => ({
            searchPlaceholder: t('admin_pages.enrollments.search_classes'),
            enableSelection: true,
            maxSelectable: 1,
            columns: [
                {
                    key: 'name',
                    label: t('admin_pages.classes.name'),
                    render: (classItem) => (
                        <div>
                            <div className="font-medium text-gray-900">{classItem.name}</div>
                            {classItem.level && (
                                <div className="text-sm text-gray-500">
                                    {classItem.level.name}
                                    {classItem.level.description
                                        ? ` (${classItem.level.description})`
                                        : ''}
                                </div>
                            )}
                        </div>
                    ),
                },
                {
                    key: 'active_enrollments_count',
                    label: t('admin_pages.classes.students'),
                    render: (classItem) => {
                        const active = classItem.active_enrollments_count ?? 0;
                        const max = classItem.max_students ?? 0;
                        const isFull = max > 0 && active >= max;
                        return (
                            <div className="flex items-center gap-2">
                                <span className="text-sm text-gray-900">
                                    {active}
                                    {max > 0 ? ` / ${max}` : ''}
                                </span>
                                {isFull && (
                                    <Badge
                                        label={t('admin_pages.classes.full')}
                                        type="warning"
                                        size="sm"
                                    />
                                )}
                            </div>
                        );
                    },
                },
                {
                    key: 'academic_year',
                    label: t('admin_pages.classes.academic_year'),
                    render: (classItem) => (
                        <span className="text-sm text-gray-600">
                            {classItem.academic_year?.name ?? '-'}
                        </span>
                    ),
                },
            ],
            selectionActions: (ids) => (
                <Button
                    type="button"
                    color="primary"
                    variant="solid"
                    size="sm"
                    onClick={handleNext}
                    disabled={ids.length === 0}
                >
                    {t('admin_pages.enrollments.next_step')}
                    <ArrowRightIcon className="ml-2 h-4 w-4" />
                </Button>
            ),
            emptyState: {
                title: t('admin_pages.enrollments.no_classes_title'),
                subtitle: t('admin_pages.enrollments.no_classes_subtitle'),
            },
            emptySearchState: {
                title: t('admin_pages.enrollments.no_classes_title'),
                subtitle: t('admin_pages.enrollments.no_classes_subtitle'),
            },
        }),
        [t, handleNext],
    );

    return (
        <Section title={t('admin_pages.enrollments.step_select_class')}>
            <DataTable
                data={classes}
                config={config}
                isLoading={isLoading}
                onSelectionChange={handleSelectionChange}
            />
        </Section>
    );
}
