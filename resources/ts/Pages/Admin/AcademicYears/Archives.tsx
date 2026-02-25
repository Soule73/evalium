import { useCallback, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type PaginationType } from '@/types/datatable';
import { type AcademicYear, type PageProps } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, ConfirmationModal, Section } from '@/Components';
import { AcademicYearDetailsModal } from '@/Components/features/academic-years';
import { AcademicYearList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';
import { hasPermission } from '@/utils';

interface Props extends PageProps {
    academicYears: PaginationType<AcademicYear>;
    filters: Filters;
}

interface Filters {
    search?: string;
    is_current?: string;
}

export default function AcademicYearArchives({ academicYears, auth }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const [detailsModal, setDetailsModal] = useState<{
        isOpen: boolean;
        year: AcademicYear | null;
    }>({
        isOpen: false,
        year: null,
    });

    const [setCurrentModal, setSetCurrentModal] = useState<{
        isOpen: boolean;
        year: AcademicYear | null;
    }>({
        isOpen: false,
        year: null,
    });

    const canCreate = hasPermission(auth.permissions, 'create academic years');

    const handleDetails = useCallback((year: AcademicYear) => {
        setDetailsModal({ isOpen: true, year });
    }, []);

    const handleSetCurrent = useCallback((year: AcademicYear) => {
        setSetCurrentModal({ isOpen: true, year });
    }, []);

    const handleSwitch = useCallback((year: AcademicYear) => {
        router.post(
            route('api.academic-years.set-current'),
            { academic_year_id: year.id },
            { preserveScroll: true },
        );
    }, []);

    const confirmSetCurrent = () => {
        if (setCurrentModal.year) {
            router.post(
                route('admin.academic-years.set-current', setCurrentModal.year.id),
                {},
                {
                    onSuccess: () => {
                        setSetCurrentModal({ isOpen: false, year: null });
                    },
                },
            );
        }
    };

    const handleDelete = useCallback(
        (year: AcademicYear) => {
            if (confirm(t('admin_pages.academic_years.confirm_delete'))) {
                router.delete(route('admin.academic-years.destroy', year.id));
            }
        },
        [t],
    );

    const translations = useMemo(
        () => ({
            archivesTitle: t('admin_pages.academic_years.archives_title'),
            archivesSubtitle: t('admin_pages.academic_years.archives_subtitle'),
            activateYearModalTitle: t('admin_pages.academic_years.activate_year_modal_title'),
            activateAndSwitch: t('admin_pages.academic_years.activate_and_switch'),
            cancel: t('commons/ui.cancel'),
            create: t('admin_pages.academic_years.create'),
        }),
        [t],
    );

    return (
        <AuthenticatedLayout breadcrumb={breadcrumbs.adminAcademicYears()}>
            <Section
                variant="flat"
                title={translations.archivesTitle}
                subtitle={translations.archivesSubtitle}
                actions={
                    canCreate && (
                        <Button
                            size="sm"
                            variant="solid"
                            color="primary"
                            onClick={() => router.visit(route('admin.academic-years.create'))}
                        >
                            {translations.create}
                        </Button>
                    )
                }
            >
                <AcademicYearList
                    data={academicYears}
                    onDetails={handleDetails}
                    onSetCurrent={handleSetCurrent}
                    onSwitch={handleSwitch}
                    onDelete={handleDelete}
                />
            </Section>

            <AcademicYearDetailsModal
                isOpen={detailsModal.isOpen}
                onClose={() => setDetailsModal({ isOpen: false, year: null })}
                academicYear={detailsModal.year}
            />

            <ConfirmationModal
                isOpen={setCurrentModal.isOpen}
                title={translations.activateYearModalTitle}
                message={t('admin_pages.academic_years.activate_year_modal_message', {
                    name: setCurrentModal.year?.name || '',
                })}
                confirmText={translations.activateAndSwitch}
                cancelText={translations.cancel}
                onConfirm={confirmSetCurrent}
                onClose={() => setSetCurrentModal({ isOpen: false, year: null })}
            />
        </AuthenticatedLayout>
    );
}
