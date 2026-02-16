import { type FormEvent, useCallback, useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { type FormDataConvertible } from '@inertiajs/core';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type AcademicYearFormData } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { AcademicYearForm, buildDefaultSemesters } from '@/Components/features/academic-years';
import { route } from 'ziggy-js';

export default function AcademicYearCreate() {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const [formData, setFormData] = useState<AcademicYearFormData>({
        name: '',
        start_date: '',
        end_date: '',
        is_current: false,
        semesters: buildDefaultSemesters('', ''),
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = useCallback(
        (e: FormEvent) => {
            e.preventDefault();
            setIsSubmitting(true);

            router.post(
                route('admin.academic-years.store'),
                formData as unknown as Record<string, FormDataConvertible>,
                {
                    onError: (errs) => {
                        setErrors(errs as Record<string, string>);
                        setIsSubmitting(false);
                    },
                    onSuccess: () => setIsSubmitting(false),
                },
            );
        },
        [formData],
    );

    const handleCancel = useCallback(() => {
        router.visit(route('admin.academic-years.archives'));
    }, []);

    const handleErrorsClear = useCallback((field: string) => {
        setErrors((prev) => ({ ...prev, [field]: '' }));
    }, []);

    const translations = useMemo(
        () => ({
            pageTitle: t('admin_pages.academic_years.create_page_title'),
            sectionTitle: t('admin_pages.academic_years.create_title'),
            sectionSubtitle: t('admin_pages.academic_years.create_subtitle'),
            creating: t('admin_pages.common.creating'),
            create: t('admin_pages.common.create'),
        }),
        [t],
    );

    return (
        <AuthenticatedLayout
            title={translations.pageTitle}
            breadcrumb={breadcrumbs.admin.createAcademicYear()}
        >
            <AcademicYearForm
                formData={formData}
                errors={errors}
                isSubmitting={isSubmitting}
                sectionTitle={translations.sectionTitle}
                sectionSubtitle={translations.sectionSubtitle}
                submitLabel={translations.create}
                submittingLabel={translations.creating}
                onFormDataChange={setFormData}
                onErrorsClear={handleErrorsClear}
                onSubmit={handleSubmit}
                onCancel={handleCancel}
                showNameHelper
            />
        </AuthenticatedLayout>
    );
}
