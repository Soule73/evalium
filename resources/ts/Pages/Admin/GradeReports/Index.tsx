import { useMemo, useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Button, Section, Stat, ConfirmationModal, Select } from '@/Components';
import { GradeReportList } from '@/Components/shared/lists';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import {
    type ClassModel,
    type GradeReport,
    type PageProps,
    type Semester,
} from '@evalium/utils/types';
import { DocumentTextIcon, CheckBadgeIcon, PaperAirplaneIcon } from '@heroicons/react/24/outline';

interface Props extends PageProps {
    class: ClassModel;
    reports: GradeReport[];
    semesters: Semester[];
    selectedSemesterId: string | null;
}

/**
 * Admin grade reports listing page for a given class.
 */
export default function GradeReportsIndex({
    class: classItem,
    reports,
    semesters,
    selectedSemesterId,
}: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const [confirmModal, setConfirmModal] = useState<{
        open: boolean;
        title: string;
        message: string;
        onConfirm: () => void;
        type: 'info' | 'warning' | 'danger';
    }>({ open: false, title: '', message: '', onConfirm: () => {}, type: 'info' });

    const [processing, setProcessing] = useState(false);

    const translations = useMemo(
        () => ({
            title: t('admin_pages.grade_reports.title'),
            subtitle: t('admin_pages.grade_reports.subtitle', { class: classItem.name }),
            generateDrafts: t('admin_pages.grade_reports.generate_drafts'),
            validateAll: t('admin_pages.grade_reports.validate_all'),
            publishAll: t('admin_pages.grade_reports.publish_all'),
            downloadAll: t('admin_pages.grade_reports.download_all'),
            back: t('admin_pages.grade_reports.back'),
            totalReports: t('admin_pages.grade_reports.total_reports'),
            draftCount: t('admin_pages.grade_reports.draft_count'),
            validatedCount: t('admin_pages.grade_reports.validated_count'),
            publishedCount: t('admin_pages.grade_reports.published_count'),
            selectSemester: t('admin_pages.grade_reports.select_semester'),
            allSemesters: t('admin_pages.grade_reports.all_semesters'),
        }),
        [t, classItem.name],
    );

    const confirmTranslations = useMemo(
        () => ({
            generateTitle: t('admin_pages.grade_reports.confirm_generate_title'),
            generateMessage: t('admin_pages.grade_reports.confirm_generate_message'),
            validateAllTitle: t('admin_pages.grade_reports.confirm_validate_all_title'),
            validateAllMessage: t('admin_pages.grade_reports.confirm_validate_all_message'),
            publishAllTitle: t('admin_pages.grade_reports.confirm_publish_all_title'),
            publishAllMessage: t('admin_pages.grade_reports.confirm_publish_all_message'),
            confirm: t('commons/ui.confirm'),
            cancel: t('commons/ui.cancel'),
        }),
        [t],
    );

    const stats = useMemo(() => {
        const drafts = reports.filter((r) => r.status === 'draft').length;
        const validated = reports.filter((r) => r.status === 'validated').length;
        const published = reports.filter((r) => r.status === 'published').length;
        return { total: reports.length, drafts, validated, published };
    }, [reports]);

    const semesterOptions = useMemo(() => {
        const options = [{ value: '', label: translations.allSemesters }];
        semesters.forEach((s) => {
            options.push({ value: String(s.id), label: s.name });
        });
        return options;
    }, [semesters, translations.allSemesters]);

    const handleSemesterChange = useCallback(
        (value: string | number) => {
            const params: Record<string, string> = {};
            if (value) {
                params.semester_id = String(value);
            }
            router.visit(
                route('admin.classes.grade-reports.index', { class: classItem.id, ...params }),
                { preserveState: true },
            );
        },
        [classItem.id],
    );

    const openConfirmModal = useCallback(
        (title: string, message: string, type: 'info' | 'warning', onConfirm: () => void) => {
            setConfirmModal({ open: true, title, message, type, onConfirm });
        },
        [],
    );

    const closeConfirmModal = useCallback(() => {
        setConfirmModal((prev) => ({ ...prev, open: false }));
    }, []);

    const handleGenerate = useCallback(() => {
        openConfirmModal(
            confirmTranslations.generateTitle,
            confirmTranslations.generateMessage,
            'warning',
            () => {
                setProcessing(true);
                router.post(
                    route('admin.classes.grade-reports.generate', classItem.id),
                    { semester_id: selectedSemesterId || undefined },
                    {
                        preserveScroll: true,
                        onFinish: () => {
                            setProcessing(false);
                            closeConfirmModal();
                        },
                    },
                );
            },
        );
    }, [
        classItem.id,
        selectedSemesterId,
        confirmTranslations,
        openConfirmModal,
        closeConfirmModal,
    ]);

    const handleValidateBatch = useCallback(() => {
        openConfirmModal(
            confirmTranslations.validateAllTitle,
            confirmTranslations.validateAllMessage,
            'info',
            () => {
                setProcessing(true);
                router.post(
                    route('admin.classes.grade-reports.validate-batch', classItem.id),
                    { semester_id: selectedSemesterId || undefined },
                    {
                        preserveScroll: true,
                        onFinish: () => {
                            setProcessing(false);
                            closeConfirmModal();
                        },
                    },
                );
            },
        );
    }, [
        classItem.id,
        selectedSemesterId,
        confirmTranslations,
        openConfirmModal,
        closeConfirmModal,
    ]);

    const handlePublishBatch = useCallback(() => {
        openConfirmModal(
            confirmTranslations.publishAllTitle,
            confirmTranslations.publishAllMessage,
            'info',
            () => {
                setProcessing(true);
                router.post(
                    route('admin.classes.grade-reports.publish-batch', classItem.id),
                    { semester_id: selectedSemesterId || undefined },
                    {
                        preserveScroll: true,
                        onFinish: () => {
                            setProcessing(false);
                            closeConfirmModal();
                        },
                    },
                );
            },
        );
    }, [
        classItem.id,
        selectedSemesterId,
        confirmTranslations,
        openConfirmModal,
        closeConfirmModal,
    ]);

    const handleDownloadBatch = useCallback(() => {
        const params: Record<string, string> = {};
        if (selectedSemesterId) {
            params.semester_id = selectedSemesterId;
        }
        window.location.href = route('admin.classes.grade-reports.download-batch', {
            class: classItem.id,
            ...params,
        });
    }, [classItem.id, selectedSemesterId]);

    const handleView = useCallback((report: GradeReport) => {
        router.visit(route('admin.grade-reports.show', report.id));
    }, []);

    const hasDrafts = stats.drafts > 0;
    const hasValidated = stats.validated > 0;
    const hasReports = reports.length > 0;

    return (
        <AuthenticatedLayout
            title={translations.title}
            breadcrumb={breadcrumbs.admin.classGradeReports(classItem)}
        >
            <div className="mb-6 flex items-center justify-between">
                <Button
                    size="sm"
                    variant="outline"
                    color="secondary"
                    onClick={() => router.visit(route('admin.classes.show', classItem.id))}
                >
                    {translations.back}
                </Button>
                <div className="w-52">
                    <Select
                        options={semesterOptions}
                        value={selectedSemesterId ?? ''}
                        onChange={handleSemesterChange}
                        placeholder={translations.selectSemester}
                        searchable={false}
                        size="sm"
                    />
                </div>
            </div>

            <Stat.Group columns={4} className="mb-6">
                <Stat.Item
                    icon={DocumentTextIcon}
                    title={translations.totalReports}
                    value={stats.total}
                />
                <Stat.Item
                    icon={DocumentTextIcon}
                    title={translations.draftCount}
                    value={stats.drafts}
                />
                <Stat.Item
                    icon={CheckBadgeIcon}
                    title={translations.validatedCount}
                    value={stats.validated}
                />
                <Stat.Item
                    icon={PaperAirplaneIcon}
                    title={translations.publishedCount}
                    value={stats.published}
                />
            </Stat.Group>

            <Section
                variant="flat"
                title={translations.title}
                subtitle={translations.subtitle}
                actions={
                    <div className="flex gap-2">
                        <Button size="sm" variant="solid" color="primary" onClick={handleGenerate}>
                            {translations.generateDrafts}
                        </Button>
                        {hasDrafts && (
                            <Button
                                size="sm"
                                variant="outline"
                                color="primary"
                                onClick={handleValidateBatch}
                            >
                                {translations.validateAll}
                            </Button>
                        )}
                        {hasValidated && (
                            <Button
                                size="sm"
                                variant="outline"
                                color="success"
                                onClick={handlePublishBatch}
                            >
                                {translations.publishAll}
                            </Button>
                        )}
                        {hasReports && (
                            <Button
                                size="sm"
                                variant="outline"
                                color="secondary"
                                onClick={handleDownloadBatch}
                            >
                                {translations.downloadAll}
                            </Button>
                        )}
                    </div>
                }
            >
                <GradeReportList data={reports} onView={handleView} />
            </Section>

            <ConfirmationModal
                isOpen={confirmModal.open}
                onClose={closeConfirmModal}
                onConfirm={confirmModal.onConfirm}
                title={confirmModal.title}
                message={confirmModal.message}
                confirmText={confirmTranslations.confirm}
                cancelText={confirmTranslations.cancel}
                type={confirmModal.type === 'info' ? 'info' : 'warning'}
                loading={processing}
            />
        </AuthenticatedLayout>
    );
}
