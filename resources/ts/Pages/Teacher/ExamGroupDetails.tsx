import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import { Exam, Group, ExamAssignment } from '@/types';
import Section from '@/Components/Section';
import { DataTable } from '@/Components/DataTable';
import {
    UserGroupIcon,
    ArrowLeftIcon
} from '@heroicons/react/24/outline';
import { route } from 'ziggy-js';
import { PaginationType } from '@/types/datatable';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { getExamAssignmentColumns, examAssignmentFilters, ExamStatsCards } from '@/Components/exam';

interface Props {
    exam: Exam;
    group: Group;
    assignments: PaginationType<ExamAssignment>;
    stats: {
        total_students: number;
        completed: number;
        in_progress: number;
        not_started: number;
        average_score: number | null;
    };
}

export default function ExamGroupDetails({ exam, group, assignments, stats }: Props) {
    // Utiliser la configuration réutilisable pour les colonnes d'assignation
    const columns = getExamAssignmentColumns({
        exam,
        showActions: true,
        onRemove: undefined // Pas de suppression dans cette vue
    });

    const dataTableConfig = {
        columns,
        filters: examAssignmentFilters,
        searchPlaceholder: 'Rechercher par nom ou email...',
        emptyState: {
            title: 'Aucun étudiant dans ce groupe',
            subtitle: 'Ce groupe ne contient aucun étudiant actif',
        },
    };

    return (
        <AuthenticatedLayout
            title={`${group.display_name} - ${exam.title}`}
            breadcrumb={breadcrumbs.teacherExamGroupDetails(exam.title, exam.id, group.display_name)}
        >
            <div className="space-y-6">
                {/* En-tête avec bouton retour */}
                <Section
                    title={
                        <div className="flex items-center space-x-3">
                            <UserGroupIcon className="h-8 w-8 text-blue-600" />
                            <div>
                                <h2 className="text-2xl font-bold text-gray-900">{group.display_name}</h2>
                                <p className="text-sm text-gray-500">Détails de l'examen: {exam.title}</p>
                            </div>
                        </div>
                    }
                    actions={
                        <Button
                            onClick={() => router.visit(route('teacher.exams.assign', exam.id))}
                            color="secondary"
                            variant="outline"
                            size="sm"
                        >
                            <ArrowLeftIcon className="h-4 w-4 mr-2" />
                            Retour
                        </Button>
                    }
                >
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p className="text-sm font-medium text-gray-700">Niveau</p>
                                <p className="text-base text-gray-900">{group.level?.name || 'Non défini'}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-700">Étudiants actifs</p>
                                <p className="text-base text-gray-900">{group.active_students_count || 0}</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-700">Durée de l'examen</p>
                                <p className="text-base text-gray-900">{exam.duration} minutes</p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-700">Nombre de questions</p>
                                <p className="text-base text-gray-900">{exam.questions?.length || 0}</p>
                            </div>
                        </div>
                    </div>
                </Section>

                {/* Statistiques */}
                <Section title="Statistiques du groupe">
                    <ExamStatsCards stats={stats} />
                    {stats.average_score !== null && (
                        <div className="mt-4 bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div className="flex items-center justify-between">
                                <span className="text-sm font-medium text-purple-900">Note moyenne du groupe</span>
                                <span className="text-2xl font-bold text-purple-600">
                                    {Math.round(stats.average_score)}%
                                </span>
                            </div>
                        </div>
                    )}
                </Section>

                {/* Liste des étudiants */}
                <Section
                    title="Étudiants du groupe"
                    subtitle={`Liste complète des étudiants et leur progression`}
                >
                    <DataTable
                        data={assignments}
                        config={dataTableConfig}
                    />
                </Section>
            </div>
        </AuthenticatedLayout>
    );
}
