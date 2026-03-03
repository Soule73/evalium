import { useMemo, useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Badge, Button, Section, Stat, ConfirmationModal } from '@/Components';
import Modal from '@evalium/ui/Modal/Modal';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { type GradeReport, type PageProps } from '@evalium/utils/types';
import type { BadgeType } from '@evalium/ui/Badge/Badge';
import {
    AcademicCapIcon,
    CalendarIcon,
    ChartBarIcon,
    EyeIcon,
    UserIcon,
} from '@heroicons/react/24/outline';

interface Props extends PageProps {
    report: GradeReport;
}

const STATUS_BADGE_MAP: Record<string, BadgeType> = {
    draft: 'warning',
    validated: 'info',
    published: 'success',
};

/**
 * Admin grade report detail page for a single student.
 */
export default function GradeReportShow({ report }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const canValidate = report.status === 'draft';
    const canPublish = report.status === 'validated';
    const canUpdateRemark = report.status === 'draft';

    const [generalRemark, setGeneralRemark] = useState(report.general_remark ?? '');
    const [remarkSaving, setRemarkSaving] = useState(false);
    const [processing, setProcessing] = useState(false);
    const [previewOpen, setPreviewOpen] = useState(false);

    const [confirmModal, setConfirmModal] = useState<{
        open: boolean;
        title: string;
        message: string;
        onConfirm: () => void;
    }>({ open: false, title: '', message: '', onConfirm: () => {} });

    const classItem = report.enrollment?.class;
    const student = report.enrollment?.student;

    const translations = useMemo(
        () => ({
            title: t('admin_pages.grade_reports.show_title'),
            subtitle: t('admin_pages.grade_reports.show_subtitle', {
                student: student?.name ?? '',
            }),
            back: t('admin_pages.grade_reports.back'),
            validate: t('admin_pages.grade_reports.validate'),
            publish: t('admin_pages.grade_reports.publish'),
            download: t('admin_pages.grade_reports.download'),
            previewPdf: t('admin_pages.grade_reports.preview_pdf'),
            statusDraft: t('admin_pages.grade_reports.status_draft'),
            statusValidated: t('admin_pages.grade_reports.status_validated'),
            statusPublished: t('admin_pages.grade_reports.status_published'),
            generalRemark: t('admin_pages.grade_reports.general_remark'),
            generalRemarkPlaceholder: t('admin_pages.grade_reports.general_remark_placeholder'),
            subjectGrades: t('admin_pages.grade_reports.subjects_grades'),
            reportSummary: t('admin_pages.grade_reports.report_summary'),
            studentInfo: t('admin_pages.grade_reports.student_info'),
            className: t('admin_pages.grade_reports.class'),
            level: t('admin_pages.grade_reports.level'),
            academicYear: t('admin_pages.grade_reports.academic_year'),
            period: t('admin_pages.grade_reports.period'),
            subject: t('admin_pages.grade_reports.subject'),
            coefficient: t('admin_pages.grade_reports.coefficient'),
            average: t('admin_pages.grade_reports.average'),
            classAverage: t('admin_pages.grade_reports.class_average'),
            min: t('admin_pages.grade_reports.min'),
            max: t('admin_pages.grade_reports.max'),
            remark: t('admin_pages.grade_reports.remark'),
            rank: t('admin_pages.grade_reports.rank'),
            status: t('admin_pages.grade_reports.status'),
            validatedBy: t('admin_pages.grade_reports.validated_by'),
            validatedAt: t('admin_pages.grade_reports.validated_at'),
            confirm: t('commons/ui.confirm'),
            cancel: t('commons/ui.cancel'),
            save: t('commons/ui.save'),
        }),
        [t, student?.name],
    );

    const confirmTranslations = useMemo(
        () => ({
            validateTitle: t('admin_pages.grade_reports.confirm_validate_title'),
            validateMessage: t('admin_pages.grade_reports.confirm_validate_message'),
            publishTitle: t('admin_pages.grade_reports.confirm_publish_title'),
            publishMessage: t('admin_pages.grade_reports.confirm_publish_message'),
        }),
        [t],
    );

    const statusLabel = useMemo(() => {
        const map: Record<string, string> = {
            draft: translations.statusDraft,
            validated: translations.statusValidated,
            published: translations.statusPublished,
        };
        return map[report.status] ?? report.status;
    }, [report.status, translations]);

    const handleBack = useCallback(() => {
        if (classItem) {
            router.visit(route('admin.classes.grade-reports.index', classItem.id));
        } else {
            router.visit(route('admin.classes.index'));
        }
    }, [classItem]);

    const handleSaveRemark = useCallback(() => {
        setRemarkSaving(true);
        router.put(
            route('admin.grade-reports.update-general-remark', report.id),
            { general_remark: generalRemark },
            {
                preserveScroll: true,
                onFinish: () => setRemarkSaving(false),
            },
        );
    }, [report.id, generalRemark]);

    const handleValidate = useCallback(() => {
        setConfirmModal({
            open: true,
            title: confirmTranslations.validateTitle,
            message: confirmTranslations.validateMessage,
            onConfirm: () => {
                setProcessing(true);
                router.post(
                    route('admin.grade-reports.validate', report.id),
                    {},
                    {
                        preserveScroll: true,
                        onFinish: () => {
                            setProcessing(false);
                            setConfirmModal((prev) => ({ ...prev, open: false }));
                        },
                    },
                );
            },
        });
    }, [report.id, confirmTranslations]);

    const handlePublish = useCallback(() => {
        setConfirmModal({
            open: true,
            title: confirmTranslations.publishTitle,
            message: confirmTranslations.publishMessage,
            onConfirm: () => {
                setProcessing(true);
                router.post(
                    route('admin.grade-reports.publish', report.id),
                    {},
                    {
                        preserveScroll: true,
                        onFinish: () => {
                            setProcessing(false);
                            setConfirmModal((prev) => ({ ...prev, open: false }));
                        },
                    },
                );
            },
        });
    }, [report.id, confirmTranslations]);

    const handleDownload = useCallback(() => {
        window.location.href = route('admin.grade-reports.download', report.id);
    }, [report.id]);

    const { data } = report;
    const subjects = data?.subjects ?? [];
    const header = data?.header;
    const footer = data?.footer;

    const getSubjectRemark = useCallback(
        (classSubjectId: number): string => {
            const remarks = report.remarks?.subjects ?? [];
            const found = remarks.find((r) => r.class_subject_id === classSubjectId);
            return found?.remark ?? '';
        },
        [report.remarks],
    );

    const breadcrumbClassItem = classItem
        ? { id: classItem.id, name: classItem.name, level: classItem.level }
        : { id: 0, name: '' };

    return (
        <AuthenticatedLayout
            title={translations.title}
            breadcrumb={breadcrumbs.admin.showGradeReport(breadcrumbClassItem, student?.name ?? '')}
        >
            <div className="mb-6 flex items-center justify-between">
                <Button size="sm" variant="outline" color="secondary" onClick={handleBack}>
                    {translations.back}
                </Button>
                <div className="flex items-center gap-2">
                    <Badge label={statusLabel} type={STATUS_BADGE_MAP[report.status]} />
                    {canValidate && (
                        <Button size="sm" variant="solid" color="primary" onClick={handleValidate}>
                            {translations.validate}
                        </Button>
                    )}
                    {canPublish && (
                        <Button size="sm" variant="solid" color="success" onClick={handlePublish}>
                            {translations.publish}
                        </Button>
                    )}
                    <Button
                        size="sm"
                        variant="outline"
                        color="secondary"
                        onClick={() => setPreviewOpen(true)}
                    >
                        <EyeIcon className="mr-1 h-4 w-4" />
                        {translations.previewPdf}
                    </Button>
                    {report.status !== 'draft' && (
                        <Button
                            size="sm"
                            variant="outline"
                            color="secondary"
                            onClick={handleDownload}
                        >
                            {translations.download}
                        </Button>
                    )}
                </div>
            </div>

            <Stat.Group columns={4} className="mb-6">
                <Stat.Item
                    icon={UserIcon}
                    title={translations.studentInfo}
                    value={
                        <span className="text-sm text-gray-900">{header?.student_name ?? '-'}</span>
                    }
                />
                <Stat.Item
                    icon={AcademicCapIcon}
                    title={translations.className}
                    value={
                        <span className="text-sm text-gray-900">
                            {header?.class_name ?? '-'}
                            {header?.level_name ? ` (${header.level_name})` : ''}
                        </span>
                    }
                />
                <Stat.Item
                    icon={CalendarIcon}
                    title={translations.period}
                    value={<span className="text-sm text-gray-900">{header?.period ?? '-'}</span>}
                />
                <Stat.Item
                    icon={ChartBarIcon}
                    title={translations.average}
                    value={
                        <span className="text-sm font-semibold text-gray-900">
                            {footer?.average !== null && footer?.average !== undefined
                                ? `${footer.average} / 20`
                                : '\u2014'}
                            {footer?.rank !== null && footer?.rank !== undefined
                                ? ` (${translations.rank}: ${footer.rank}/${footer?.class_size ?? '-'})`
                                : ''}
                        </span>
                    }
                />
            </Stat.Group>

            <Section title={translations.subjectGrades} className="mb-6">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    {translations.subject}
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    {translations.coefficient}
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    {translations.average}
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    {translations.classAverage}
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    {translations.min}
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    {translations.max}
                                </th>
                                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                    {translations.remark}
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200 bg-white">
                            {subjects.map((subject) => (
                                <tr key={subject.class_subject_id} className="hover:bg-gray-50">
                                    <td className="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">
                                        {subject.subject_name}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                        {subject.coefficient}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                        {subject.grade !== null && subject.grade !== undefined
                                            ? `${subject.grade} / 20`
                                            : '\u2014'}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                        {subject.class_average !== null &&
                                        subject.class_average !== undefined
                                            ? `${subject.class_average}`
                                            : '\u2014'}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                        {subject.min !== null && subject.min !== undefined
                                            ? `${subject.min}`
                                            : '\u2014'}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                        {subject.max !== null && subject.max !== undefined
                                            ? `${subject.max}`
                                            : '\u2014'}
                                    </td>
                                    <td className="px-4 py-3 text-sm text-gray-600">
                                        {getSubjectRemark(subject.class_subject_id) || '\u2014'}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Section>

            <Section title={translations.generalRemark} className="mb-6">
                <div className="space-y-3">
                    <textarea
                        className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-500"
                        rows={3}
                        value={generalRemark}
                        onChange={(e) => setGeneralRemark(e.target.value)}
                        placeholder={translations.generalRemarkPlaceholder}
                        disabled={!canUpdateRemark}
                        maxLength={500}
                    />
                    {canUpdateRemark && (
                        <div className="flex justify-end">
                            <Button
                                size="sm"
                                variant="solid"
                                color="primary"
                                onClick={handleSaveRemark}
                                disabled={
                                    remarkSaving || generalRemark === (report.general_remark ?? '')
                                }
                            >
                                {translations.save}
                            </Button>
                        </div>
                    )}
                </div>
            </Section>

            {report.validator && (
                <Section title={translations.reportSummary}>
                    <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt className="text-sm font-medium text-gray-500">
                                {translations.validatedBy}
                            </dt>
                            <dd className="mt-1 text-sm text-gray-900">{report.validator.name}</dd>
                        </div>
                        <div>
                            <dt className="text-sm font-medium text-gray-500">
                                {translations.validatedAt}
                            </dt>
                            <dd className="mt-1 text-sm text-gray-900">
                                {report.validated_at
                                    ? new Date(report.validated_at).toLocaleDateString()
                                    : '\u2014'}
                            </dd>
                        </div>
                    </dl>
                </Section>
            )}

            <ConfirmationModal
                isOpen={confirmModal.open}
                onClose={() => setConfirmModal((prev) => ({ ...prev, open: false }))}
                onConfirm={confirmModal.onConfirm}
                title={confirmModal.title}
                message={confirmModal.message}
                confirmText={translations.confirm}
                cancelText={translations.cancel}
                type="info"
                loading={processing}
            />

            <Modal
                isOpen={previewOpen}
                onClose={() => setPreviewOpen(false)}
                title={translations.previewPdf}
                size="full"
            >
                <div className="flex min-h-0 flex-1 flex-col">
                    <div className="min-h-0 flex-1">
                        <iframe
                            src={route('admin.grade-reports.preview', report.id)}
                            title={translations.previewPdf}
                            className="h-full w-full rounded-lg border border-gray-200"
                        />
                    </div>
                    <div className="mt-auto flex shrink-0 justify-end gap-2 border-t border-gray-200 pt-4">
                        <Button
                            size="sm"
                            color="secondary"
                            variant="outline"
                            onClick={() => setPreviewOpen(false)}
                        >
                            {translations.cancel}
                        </Button>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
