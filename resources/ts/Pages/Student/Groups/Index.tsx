import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, GroupWithPivot } from '@/types';
import { DataTable } from '@/Components/DataTable';
import { DataTableConfig, PaginationType } from '@/types/datatable';
import Section from '@/Components/Section';
import { BookOpenIcon, CalendarIcon, UserGroupIcon } from '@heroicons/react/24/outline';
import Badge from '@/Components/Badge';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { route } from 'ziggy-js';

interface StudentGroup extends GroupWithPivot {
    is_current: boolean;
    exams_count: number;
    completed_exams_count?: number;
}

interface Props extends PageProps {
    groups: PaginationType<StudentGroup>;
}

export default function Index({ groups }: Props) {
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    };

    const dataTableConfig: DataTableConfig<StudentGroup> = {
        columns: [
            {
                key: 'name',
                label: 'Groupe',
                render: (group) => (
                    <div>
                        <div className="text-sm font-medium text-gray-900">
                            {group.level?.name || 'Non défini'}
                        </div>
                        {group.description && (
                            <div className="text-sm text-gray-500 line-clamp-1">
                                {group.description}
                            </div>
                        )}
                    </div>
                )
            },
            {
                key: 'academic_year',
                label: 'Année scolaire',
                render: (group) => (
                    <span className="text-sm text-gray-900">
                        {group.academic_year || '-'}
                    </span>
                )
            },
            {
                key: 'period',
                label: 'Période',
                render: (group) => (
                    <div className="flex items-center gap-2 text-sm text-gray-600">
                        <CalendarIcon className="h-4 w-4 shrink-0 text-gray-400" />
                        <div>
                            <div>{formatDate(group.start_date)}</div>
                            <div className="text-xs text-gray-400">au {formatDate(group.end_date)}</div>
                        </div>
                    </div>
                )
            },
            {
                key: 'exams',
                label: 'Examens',
                render: (group) => (
                    <div className="flex items-center gap-2 text-sm text-gray-600">
                        <BookOpenIcon className="h-4 w-4 shrink-0 text-gray-400" />
                        <div>
                            <span>{group.exams_count} examen{group.exams_count > 1 ? 's' : ''}</span>
                            {group.is_current && group.completed_exams_count !== undefined && (
                                <div className="text-xs text-gray-500">
                                    {group.completed_exams_count} complété{group.completed_exams_count > 1 ? 's' : ''}
                                </div>
                            )}
                        </div>
                    </div>
                )
            },
            {
                key: 'status',
                label: 'Statut',
                render: (group) => (
                    group.is_current ? (
                        <Badge label="Actif" type="success" />
                    ) : (
                        <Badge label="Inactif" type="gray" />
                    )
                )
            },
            {
                key: 'actions',
                label: 'Actions',
                render: (group) => (
                    <Link
                        href={route('student.exams.group.show', { group: group.id })}
                        className={`inline-flex items-center px-3 py-2 rounded-md text-sm font-medium transition-colors ${group.is_current
                            ? 'bg-blue-600 text-white hover:bg-blue-700'
                            : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                            }`}
                    >
                        Voir
                    </Link>
                )
            }
        ],
        emptyState: {
            title: 'Aucun groupe',
            subtitle: "Vous n'êtes inscrit dans aucun groupe pour le moment.",
            icon: <UserGroupIcon className="mx-auto h-12 w-12 text-gray-400" />
        },
        searchPlaceholder: 'Rechercher un groupe...',
        perPageOptions: [10, 20, 30, 50]
    };

    return (
        <AuthenticatedLayout
            title="Mes groupes"
            breadcrumb={breadcrumbs.studentExams()}
        >
            <Head title="Mes groupes" />

            <div className="space-y-8">
                <Section
                    title={`Mes groupes (${groups.total})`}
                    subtitle="Accédez aux examens de vos différents groupes"
                >
                    <DataTable
                        data={groups}
                        config={dataTableConfig}
                    />
                </Section>
            </div>
        </AuthenticatedLayout>
    );
}
