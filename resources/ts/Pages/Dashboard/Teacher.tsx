import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Exam, User } from '@/types';
import { route } from 'ziggy-js';
import { ArrowTrendingUpIcon, DocumentTextIcon, QuestionMarkCircleIcon, UserGroupIcon } from '@heroicons/react/24/outline';
import { breadcrumbs } from '@/utils';
import { PaginationType } from '@/types/datatable';
import { trans } from '@/utils';
import { Button, ExamList, Section, StatCard } from '@/Components';

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
        <AuthenticatedLayout title={trans('dashboard.title.teacher')}
            breadcrumb={breadcrumbs.dashboard()}
        >
            {/* Statistiques principales */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <StatCard
                    title={trans('dashboard.teacher.exams_created')}
                    value={stats.total_exams}
                    icon={
                        DocumentTextIcon
                    }
                    color="blue"
                />
                <StatCard
                    title={trans('dashboard.teacher.questions_created')}
                    value={stats.total_questions}
                    color='green'
                    icon={
                        // QuestionMarkCircleIcon
                        QuestionMarkCircleIcon
                    }
                />

                <StatCard
                    title={trans('dashboard.teacher.students_evaluated')}
                    value={stats.students_evaluated}
                    color='purple'
                    icon={
                        UserGroupIcon
                    }
                />

                <StatCard
                    title={trans('dashboard.teacher.average_score')}
                    value={stats.average_score}
                    color='yellow'
                    icon={
                        ArrowTrendingUpIcon
                    }
                />
            </div>
            <Section
                title={trans('dashboard.teacher.recent_exams')}
                subtitle={trans('dashboard.teacher.recent_exams_subtitle')}
                actions={
                    <div className='flex justify-end space-x-4 items-center'>
                        <Button
                            onClick={handleCreateExam}
                            color="secondary"
                            variant='outline'
                            size='sm'
                        >
                            {trans('dashboard.teacher.create_exam')}
                        </Button>
                        <Button
                            onClick={handleViewExams}
                            color="secondary"
                            variant='outline'
                            size='sm'
                        >
                            {trans('dashboard.teacher.view_all_exams')}
                        </Button>
                    </div>
                }
            >
                <ExamList data={recent_exams} />
            </Section>

        </AuthenticatedLayout >
    );
}