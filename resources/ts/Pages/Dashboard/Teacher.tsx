import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import { Exam, User } from '@/types';
import { route } from 'ziggy-js';
import Section from '@/Components/Section';
import StatCard from '@/Components/StatCard';
import { ArrowTrendingUpIcon, DocumentTextIcon, QuestionMarkCircleIcon, UserGroupIcon } from '@heroicons/react/24/outline';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { PaginationType } from '@/types/datatable';
import ExamList from '@/Components/exam/ExamList';

interface Stats {
    total_exams: number;
    total_questions: number;
    students_evaluated: number;
    average_score: number;
}

interface Props {
    user: User;
    stats: Stats;
    recent_exams: PaginationType<Exam>;
}

export default function TeacherDashboard({ stats, recent_exams }: Props) {
    const handleCreateExam = () => {
        router.visit(route('exams.create'));
    };

    const handleViewExams = () => {
        router.visit(route('exams.index'));
    };

    return (
        <AuthenticatedLayout title="Tableau de bord enseignant"
            breadcrumb={breadcrumbs.dashboard()}
        >
            {/* Statistiques principales */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <StatCard
                    title="Examens créés"
                    value={stats.total_exams}
                    icon={
                        DocumentTextIcon
                    }
                    color="blue"
                />
                <StatCard
                    title="Questions créées"
                    value={stats.total_questions}
                    color='green'
                    icon={
                        // QuestionMarkCircleIcon
                        QuestionMarkCircleIcon
                    }
                />

                <StatCard
                    title="Étudiants évalués"
                    value={stats.students_evaluated}
                    color='purple'
                    icon={
                        UserGroupIcon
                    }
                />

                <StatCard
                    title="Note moyen"
                    value={stats.average_score}
                    color='yellow'
                    icon={
                        ArrowTrendingUpIcon
                    }
                />
            </div>
            <Section
                title="Examens récents"
                subtitle="Gérez vos examens et suivez les performances de vos étudiants."
                actions={
                    <div className='flex justify-end space-x-4 items-center'>
                        <Button
                            onClick={handleCreateExam}
                            color="secondary"
                            variant='outline'
                            size='sm'
                        >
                            Créer un examen
                        </Button>
                        <Button
                            onClick={handleViewExams}
                            color="secondary"
                            variant='outline'
                            size='sm'
                        >
                            Voir tous les examens
                        </Button>
                    </div>
                }
            >
                <ExamList data={recent_exams} />
            </Section>

        </AuthenticatedLayout >
    );
}