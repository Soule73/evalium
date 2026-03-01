import React, { useMemo } from 'react';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { formatDate } from '@/utils';
import { DataTable } from '@/Components/shared/datatable/DataTable';
import type { ColumnConfig } from '@/types/datatable';

interface AssessmentStat {
    id: number;
    title: string;
    type: string;
    scheduled_at: string | null;
    subject_name: string;
    total_assigned: number;
    graded: number;
    submitted: number;
    in_progress: number;
    not_started: number;
    average_score: number | null;
    completion_rate: number;
}

interface AssessmentStatsTableProps {
    stats: AssessmentStat[];
}

const COMPLETION_THRESHOLDS = { high: 80, medium: 50 } as const;

const getCompletionColorClass = (rate: number): string => {
    if (rate >= COMPLETION_THRESHOLDS.high) return 'font-medium text-green-600';
    if (rate >= COMPLETION_THRESHOLDS.medium) return 'font-medium text-yellow-600';
    return 'font-medium text-red-500';
};

/**
 * Displays a table of assessment statistics for a class using DataTable.
 */
const AssessmentStatsTable: React.FC<AssessmentStatsTableProps> = ({ stats }) => {
    const { t } = useTranslations();

    const columns: ColumnConfig<AssessmentStat>[] = useMemo(
        () => [
            {
                key: 'title',
                label: t('teacher_class_pages.results.assessment_stats'),
                render: (row) => <span className="font-medium text-gray-900">{row.title}</span>,
            },
            {
                key: 'subject_name',
                label: t('teacher_class_pages.results.subject'),
                render: (row) => <span className="text-gray-600">{row.subject_name}</span>,
            },
            {
                key: 'scheduled_at',
                label: t('teacher_class_pages.results.scheduled_at'),
                render: (row) => (
                    <span className="text-gray-500">
                        {formatDate(row.scheduled_at ?? '', 'datetime')}
                    </span>
                ),
            },
            {
                key: 'graded',
                label: t('teacher_class_pages.results.graded'),
                className: 'text-right',
                render: (row) => (
                    <span className="text-gray-900">
                        {row.graded} / {row.total_assigned}
                    </span>
                ),
            },
            {
                key: 'submitted',
                label: t('teacher_class_pages.results.submitted'),
                className: 'text-right',
                render: (row) => <span className="text-gray-600">{row.submitted}</span>,
            },
            {
                key: 'in_progress',
                label: t('teacher_class_pages.results.in_progress'),
                className: 'text-right',
                render: (row) => <span className="text-gray-600">{row.in_progress}</span>,
            },
            {
                key: 'not_started',
                label: t('teacher_class_pages.results.not_started'),
                className: 'text-right',
                render: (row) => <span className="text-gray-600">{row.not_started}</span>,
            },
            {
                key: 'average_score',
                label: t('teacher_class_pages.results.score'),
                className: 'text-right',
                render: (row) => (
                    <span className="font-medium text-gray-900">
                        {row.average_score !== null ? `${row.average_score} / 20` : '\u2014'}
                    </span>
                ),
            },
            {
                key: 'completion_rate',
                label: t('teacher_class_pages.results.completion'),
                className: 'text-right',
                render: (row) => (
                    <span className={getCompletionColorClass(row.completion_rate)}>
                        {row.completion_rate}%
                    </span>
                ),
            },
        ],
        [t],
    );

    return (
        <DataTable<AssessmentStat>
            data={stats}
            config={{
                columns,
                emptyState: {
                    title: t('teacher_class_pages.results.no_assessments'),
                    subtitle: '',
                },
            }}
        />
    );
};

export default React.memo(AssessmentStatsTable);
