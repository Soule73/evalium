import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { formatDate, formatDuration } from '@/utils/formatters';
import { Exam, PageProps } from '@/types';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import type { FilterConfig } from '@/types/datatable';
import { hasPermission } from '@/utils/permissions';
import { trans } from '@/utils/translations';
import { MarkdownRenderer, Toggle } from '@/Components/forms';
import { DataTable } from '@/Components/shared';
import { Button } from '@/Components/ui';

interface ExamListProps {
    data: PaginationType<Exam>;
    variant?: 'teacher' | 'admin';
    showFilters?: boolean;
    showSearch?: boolean;
}

/**
 * Composant UNIFIÉ pour afficher les examens - Basé sur PERMISSIONS
 * 
 * STRATÉGIE HYBRIDE :
 * - Affichage dynamique selon les permissions de l'utilisateur
 * - Actions conditionnelles (edit, assign, toggle) selon permissions
 * - Utilisé par tous les rôles sauf students (ont leur propre StudentExamList)
 */
const ExamList: React.FC<ExamListProps> = ({
    data,
    variant = 'teacher',
    showFilters = true,
    showSearch = true
}) => {
    const { auth } = usePage<PageProps>().props;
    const canViewExams = hasPermission(auth.permissions, 'view exams') || hasPermission(auth.permissions, 'view any exams');
    const canPublishExams = hasPermission(auth.permissions, 'update exams');

    const [togglingExams, setTogglingExams] = useState<Set<number>>(new Set());

    const handleToggleStatus = (examId: number) => {
        if (togglingExams.has(examId) || !canPublishExams) return;

        setTogglingExams(prev => new Set(prev).add(examId));
        router.patch(
            route('exams.toggle-active', examId),
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setTogglingExams(prev => {
                        const next = new Set(prev);
                        next.delete(examId);
                        return next;
                    });
                },
            }
        );
    };


    const renderTitle = (exam: Exam) => (
        <div>
            <div className="text-sm font-medium text-gray-900">{exam.title}</div>
            <div className="text-sm text-gray-500 truncate max-w-sm line-clamp-2">
                <MarkdownRenderer>
                    {exam.description ?? ''}
                </MarkdownRenderer>
            </div>
        </div>
    );

    const renderDuration = (exam: Exam) => (
        <span className="text-sm text-gray-900">{formatDuration(exam.duration)}</span>
    );

    const renderStatus = (exam: Exam) => (
        <div className="flex items-center space-x-2">
            <Toggle
                checked={exam.is_active}
                onChange={() => handleToggleStatus(exam.id)}
                disabled={togglingExams.has(exam.id) || !canPublishExams}
                color="green"
                size="sm"
                showLabel={false}
            />
        </div>
    );

    const renderCreatedAt = (exam: Exam) => (
        <span className="text-sm text-gray-500">{formatDate(exam.created_at, "datetime")}</span>
    );

    const renderActions = (exam: Exam) => (
        <div className="flex items-center justify-end space-x-2">
            {canViewExams && <Button
                size="sm"
                onClick={() => router.visit(route('exams.show', exam.id))}
                title={trans('components.exam_list.view_exam_title')}
                variant="outline"
            >
                {trans('components.exam_list.view_exam')}
            </Button>}
        </div>
    );

    const columns: DataTableConfig<Exam>["columns"] =
        variant === 'admin' ? [
            { key: 'title', label: trans('components.exam_list.exam_label'), render: renderTitle },
            { key: 'duration', label: trans('components.exam_list.duration_label'), render: renderDuration },
            { key: 'is_active', label: trans('components.exam_list.status_label'), render: renderStatus },
            { key: 'created_at', label: trans('components.exam_list.created_on'), render: renderCreatedAt },
        ] : [
            { key: 'title', label: trans('components.exam_list.exam_label'), render: renderTitle },
            { key: 'duration', label: trans('components.exam_list.duration_label'), render: renderDuration },
            { key: 'is_active', label: trans('components.exam_list.status_label'), render: renderStatus },
            { key: 'created_at', label: trans('components.exam_list.created_on'), render: renderCreatedAt },
            { key: 'actions', label: trans('components.exam_list.actions_label'), className: 'text-right', render: renderActions },
        ];

    const filters: FilterConfig[] = showFilters ? [
        {
            key: 'status',
            label: trans('components.exam_list.status_label'),
            type: 'select',
            options: [
                { value: '1', label: trans('components.exam_list.status_active') },
                { value: '0', label: trans('components.exam_list.status_inactive') }
            ]
        }
    ] : [];

    const searchPlaceholder = showSearch ? trans('components.exam_list.search_placeholder') : undefined;

    const emptyState = {
        title: trans('components.exam_list.empty_title'),
        subtitle: trans('components.exam_list.empty_subtitle')
    };

    const emptySearchState = {
        title: trans('components.exam_list.empty_search_title'),
        subtitle: trans('components.exam_list.empty_search_subtitle'),
        resetLabel: trans('components.exam_list.reset_filters')
    };

    const perPageOptions = [10, 25, 50];

    const tableConfig: DataTableConfig<Exam> = {
        columns,
        filters,
        searchPlaceholder,
        emptyState,
        emptySearchState,
        perPageOptions
    };

    return (
        <>
            <DataTable
                data={data}
                config={tableConfig}
            />

        </>
    );
};
export default ExamList;