import React, { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { EyeIcon, PencilIcon, UserGroupIcon } from '@heroicons/react/24/outline';
import { DataTable } from '@/Components/DataTable';
import { route } from 'ziggy-js';
import { formatDate, formatDuration } from '@/utils/formatters';
import { Exam } from '@/types';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import type { FilterConfig } from '@/types/datatable';
import Toggle from '@/Components/form/Toggle';
interface TeacherExamListProps {
    data: PaginationType<Exam>;
    variant?: 'teacher' | 'admin';
    showFilters?: boolean;
    showSearch?: boolean;
}

/**
 * Composant de tableau commun pour afficher les examens aux étudiants
 * Utilisé dans ExamIndex et Dashboard Student pour assurer la cohérence
 */
const TeacherExamList: React.FC<TeacherExamListProps> = ({
    data,
    variant = 'teacher',
    showFilters = true,
    showSearch = true
}) => {
    const [togglingExams, setTogglingExams] = useState<Set<number>>(new Set());
    const handleToggleStatus = (examId: number) => {
        if (togglingExams.has(examId)) return;

        setTogglingExams(prev => new Set(prev).add(examId));
        router.patch(
            route('teacher.exams.toggle-active', examId),
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


    // Fonctions utilitaires pour les colonnes
    const renderTitle = (exam: Exam) => (
        <div>
            <div className="text-sm font-medium text-gray-900">{exam.title}</div>
            <div className="text-sm text-gray-500 truncate max-w-sm line-clamp-2">{exam.description}</div>
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
                disabled={togglingExams.has(exam.id)}
                color="green"
                size="sm"
                showLabel={false}
            />
        </div>
    );

    const renderCreatedAt = (exam: Exam) => (
        <span className="text-sm text-gray-500">{formatDate(exam.created_at, "datetime")}</span>
    );

    const renderActions = (exam: Exam, variant: string) => (
        <div className="flex items-center justify-end space-x-2">
            <Link
                href={route('teacher.exams.show', exam.id)}
                className="text-blue-600 hover:text-blue-900 p-1"
                title="Voir l'examen"
            >
                <EyeIcon className="h-4 w-4" />
            </Link>
            {variant === 'teacher' && (
                <>
                    <Link
                        href={route('teacher.exams.edit', exam.id)}
                        className="text-indigo-600 hover:text-indigo-900 p-1"
                        title="Modifier l'examen"
                    >
                        <PencilIcon className="h-4 w-4" />
                    </Link>
                    <Link
                        href={route('teacher.exams.assign', exam.id)}
                        className="text-green-600 hover:text-green-900 p-1"
                        title="Assigner l'examen"
                    >
                        <UserGroupIcon className="h-4 w-4" />
                    </Link>
                </>
            )}
        </div>
    );

    const columns: DataTableConfig<Exam>["columns"] =
        variant === 'admin' ? [
            { key: 'title', label: 'Examen', render: renderTitle },
            { key: 'duration', label: 'Durée', render: renderDuration },
            { key: 'is_active', label: 'Statut', render: renderStatus },
            { key: 'created_at', label: 'Créé le', render: renderCreatedAt },
        ] : [
            { key: 'title', label: 'Examen', render: renderTitle },
            { key: 'duration', label: 'Durée', render: renderDuration },
            { key: 'is_active', label: 'Statut', render: renderStatus },
            { key: 'created_at', label: 'Créé le', render: renderCreatedAt },
            { key: 'actions', label: 'Actions', className: 'text-right', render: (item) => renderActions(item, 'teacher') },
        ];

    const filters: FilterConfig[] = showFilters ? [
        {
            key: 'status',
            label: 'Statut',
            type: 'select',
            options: [
                { value: '1', label: 'Actif' },
                { value: '0', label: 'Inactif' }
            ]
        }
    ] : [];

    const searchPlaceholder = showSearch ? "Rechercher par titre ou description..." : undefined;

    const emptyState = {
        title: "Aucun examen créé",
        subtitle: "Commencez par créer votre premier examen pour vos étudiants."
    };

    const emptySearchState = {
        title: "Aucun examen trouvé",
        subtitle: "Essayez de modifier vos critères de recherche ou de filtrage.",
        resetLabel: "Réinitialiser les filtres"
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
export default TeacherExamList;