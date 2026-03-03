import { useMemo, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { Badge, Button } from '@evalium/ui';
import { DataTable } from '@/Components/shared/datatable';
import { useTranslations } from '@/hooks';
import type { GradeReport, GradeReportStatus } from '@evalium/utils/types';
import type { DataTableConfig } from '@evalium/utils/types/datatable';
import type { BadgeType } from '@evalium/ui/Badge/Badge';

interface GradeReportListProps {
    data: GradeReport[];
    onView?: (report: GradeReport) => void;
    onDownload?: (report: GradeReport) => void;
}

const STATUS_BADGE_MAP: Record<GradeReportStatus, BadgeType> = {
    draft: 'warning',
    validated: 'info',
    published: 'success',
};

/**
 * List component for grade reports, following the project's list component pattern.
 * Uses DataTable directly with static array data.
 */
export function GradeReportList({ data, onView, onDownload }: GradeReportListProps) {
    const { t } = useTranslations();

    const statusLabelMap: Record<GradeReportStatus, string> = useMemo(
        () => ({
            draft: t('admin_pages.grade_reports.status_draft'),
            validated: t('admin_pages.grade_reports.status_validated'),
            published: t('admin_pages.grade_reports.status_published'),
        }),
        [t],
    );

    const handleView = useCallback(
        (report: GradeReport) => {
            if (onView) {
                onView(report);
            } else {
                router.visit(route('admin.grade-reports.show', report.id));
            }
        },
        [onView],
    );

    const handleDownload = useCallback(
        (report: GradeReport) => {
            if (onDownload) {
                onDownload(report);
            } else {
                window.location.href = route('admin.grade-reports.download', report.id);
            }
        },
        [onDownload],
    );

    const statusFilterOptions = useMemo(
        () => [
            { value: '', label: t('admin_pages.grade_reports.all_statuses') },
            { value: 'draft', label: t('admin_pages.grade_reports.status_draft') },
            { value: 'validated', label: t('admin_pages.grade_reports.status_validated') },
            { value: 'published', label: t('admin_pages.grade_reports.status_published') },
        ],
        [t],
    );

    const config: DataTableConfig<GradeReport> = useMemo(
        () => ({
            columns: [
                {
                    key: 'student',
                    label: t('admin_pages.grade_reports.student'),
                    render: (report: GradeReport) => (
                        <div>
                            <div className="font-medium text-gray-900">
                                {report.enrollment?.student?.name ?? '-'}
                            </div>
                            {report.enrollment?.student?.email && (
                                <div className="text-sm text-gray-500">
                                    {report.enrollment.student.email}
                                </div>
                            )}
                        </div>
                    ),
                },
                {
                    key: 'average',
                    label: t('admin_pages.grade_reports.average'),
                    render: (report: GradeReport) => (
                        <span className="text-sm text-gray-700">
                            {report.average !== null ? `${report.average} / 20` : '\u2014'}
                        </span>
                    ),
                },
                {
                    key: 'rank',
                    label: t('admin_pages.grade_reports.rank'),
                    render: (report: GradeReport) => (
                        <span className="text-sm text-gray-700">{report.rank ?? '\u2014'}</span>
                    ),
                },
                {
                    key: 'semester',
                    label: t('admin_pages.grade_reports.semester'),
                    render: (report: GradeReport) => (
                        <span className="text-sm text-gray-700">
                            {report.semester?.name ?? t('admin_pages.grade_reports.annual')}
                        </span>
                    ),
                },
                {
                    key: 'status',
                    label: t('admin_pages.grade_reports.status'),
                    render: (report: GradeReport) => (
                        <Badge
                            label={statusLabelMap[report.status]}
                            type={STATUS_BADGE_MAP[report.status]}
                            size="sm"
                        />
                    ),
                },
                {
                    key: 'actions',
                    label: t('commons/table.actions'),
                    render: (report: GradeReport) => (
                        <div className="flex items-center gap-2">
                            <Button
                                size="sm"
                                variant="outline"
                                color="secondary"
                                onClick={() => handleView(report)}
                            >
                                {t('commons/ui.view')}
                            </Button>
                            {report.status !== 'draft' && (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    color="primary"
                                    onClick={() => handleDownload(report)}
                                >
                                    {t('admin_pages.grade_reports.download')}
                                </Button>
                            )}
                        </div>
                    ),
                },
            ],
            filters: [
                {
                    key: 'status',
                    label: t('admin_pages.grade_reports.status'),
                    type: 'select',
                    options: statusFilterOptions,
                },
            ],
            searchPlaceholder: t('commons/ui.search'),
            showPagination: false,
            emptyState: {
                title: t('admin_pages.grade_reports.no_reports'),
                subtitle: t('admin_pages.grade_reports.no_reports_hint'),
            },
            emptySearchState: {
                title: t('commons/table.no_results'),
                subtitle: t('commons/table.no_results_subtitle'),
                resetLabel: t('commons/table.reset_filters'),
            },
        }),
        [t, statusLabelMap, statusFilterOptions, handleView, handleDownload],
    );

    return <DataTable<GradeReport> data={data} config={config} />;
}
