import React, { useMemo } from 'react';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { DataTable } from '@/Components/shared/datatable/DataTable';
import type { ColumnConfig } from '@/types/datatable';

interface StudentStat {
    enrollment_id: number;
    student_name: string;
    student_email: string;
    graded_count: number;
    submitted_count: number;
    average_score: number | null;
}

type StudentStatRow = StudentStat & { id: number };

interface StudentStatsTableProps {
    stats: StudentStat[];
    totalAssessments: number;
}

/**
 * Displays a table of student statistics for a class using DataTable.
 */
const StudentStatsTable: React.FC<StudentStatsTableProps> = ({ stats, totalAssessments }) => {
    const { t } = useTranslations();

    const rows: StudentStatRow[] = useMemo(
        () => stats.map((s) => ({ ...s, id: s.enrollment_id })),
        [stats],
    );

    const columns: ColumnConfig<StudentStatRow>[] = useMemo(
        () => [
            {
                key: 'student_name',
                label: t('teacher_class_pages.results.student_stats'),
                render: (row) => (
                    <div>
                        <div className="font-medium text-gray-900">{row.student_name}</div>
                        <div className="text-xs text-gray-400">{row.student_email}</div>
                    </div>
                ),
            },
            {
                key: 'graded_count',
                label: t('teacher_class_pages.results.graded'),
                className: 'text-right',
                render: (row) => (
                    <span className="text-gray-900">
                        {row.graded_count} / {totalAssessments}
                    </span>
                ),
            },
            {
                key: 'submitted_count',
                label: t('teacher_class_pages.results.submitted'),
                className: 'text-right',
                render: (row) => <span className="text-gray-600">{row.submitted_count}</span>,
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
        ],
        [t, totalAssessments],
    );

    return (
        <DataTable<StudentStatRow>
            data={rows}
            config={{
                columns,
                emptyState: {
                    title: t('teacher_class_pages.results.no_students'),
                    subtitle: '',
                },
            }}
        />
    );
};

export default React.memo(StudentStatsTable);
