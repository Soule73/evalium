import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { route } from 'ziggy-js';
import { ChartBarIcon, CheckIcon, ClockIcon, DocumentTextIcon } from '@heroicons/react/24/outline';
import StatCard from '@/Components/StatCard';
import Section from '@/Components/Section';
import { Button } from '@/Components';
import StudentExamAssignmentList from '@/Components/exam/StudentExamAssignmentList';
import { ExamAssignment, User } from '@/types';
import { PaginationType } from '@/types/datatable';
import { breadcrumbs } from '@/utils/breadcrumbs';

interface Stats {
    totalExams: number;
    completedExams: number;
    pendingExams: number;
    averageScore: number;
}

interface Props {
    user: User;
    stats: Stats;
    examAssignments: PaginationType<ExamAssignment>;
}

export default function StudentDashboard({ user, stats, examAssignments }: Props) {
    return (
        <AuthenticatedLayout title='Tableau de bord étudiant'
            breadcrumb={breadcrumbs.studentDashboard()}
        >

            <Section title={`Bonjour, ${user.name} !`}
                actions={
                    <Button
                        size='sm'
                        variant='outline'
                        className=' w-max'
                        onClick={() => router.visit(route('student.exams.index'))}>
                        Voir mes examens
                    </Button>
                }
            >
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <StatCard
                        title="Total Examens"
                        value={`${stats.totalExams}`}
                        icon={DocumentTextIcon}
                        color="blue"
                    />

                    <StatCard
                        title="Examens en attente"
                        value={`${stats.pendingExams}`}
                        icon={ClockIcon}
                        color="yellow"
                    />

                    <StatCard
                        title="Examens terminés"
                        value={`${stats.completedExams}`}
                        icon={CheckIcon}
                        color="green"
                    />

                    <StatCard
                        title="Note moyen"
                        value={`${stats.averageScore} / 20`}
                        icon={ChartBarIcon}
                        color="red"
                    />
                </div>

            </Section>

            <Section title="Examens assignés"
                actions={
                    <Button
                        size='sm'
                        variant='outline'
                        className=' w-max'
                        onClick={() => router.visit(route('student.exams.index'))}>
                        Voir tous les examens
                    </Button>
                }
            >
                <StudentExamAssignmentList
                    data={examAssignments}
                    variant="dashboard"
                    showFilters={false}
                    showSearch={true}
                />
            </Section>
        </AuthenticatedLayout>
    );
}