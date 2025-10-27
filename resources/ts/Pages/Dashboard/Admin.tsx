import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import { User } from '@/types';
import StatCard from '@/Components/StatCard';
import Section from '@/Components/Section';
import { BookOpenIcon, DocumentTextIcon, UserGroupIcon } from '@heroicons/react/24/outline';
import { route } from 'ziggy-js';


interface Stats {
    total_users: number;
    students_count: number;
    teachers_count: number;
    total_exams: number;
}

interface Props {
    user: User;
    stats: Stats;
}

export default function AdminDashboard({ user, stats }: Props) {
    const handleManageUsers = () => {
        router.visit(route('admin.users.index'));
    };

    return (
        <AuthenticatedLayout title="Tableau de bord administrateur">
            <Section title={`Bonjour, ${user.name} !`}
                subtitle="Gérez la plateforme et supervisez les activités."
                actions={
                    <div className="flex flex-col md:flex-row space-y-2 md:space-x-3 md:space-y-0">
                        <Button
                            onClick={handleManageUsers}
                            variant='outline'
                            color="secondary"
                            size='sm'
                        >
                            Gérer les utilisateurs
                        </Button>
                    </div>}
            >
                {/* Statistiques principales */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <StatCard
                        title="Total utilisateurs"
                        value={stats.total_users}
                        icon={
                            UserGroupIcon
                        }
                        color="blue"
                    />
                    <StatCard
                        title="Étudiants"
                        value={stats.students_count}
                        color='green'
                        icon={
                            BookOpenIcon
                        }
                    />

                    <StatCard
                        title="Enseignants"
                        value={stats.teachers_count}
                        color='purple'
                        icon={
                            UserGroupIcon
                        }
                    />

                    <StatCard
                        title="Total examens"
                        value={stats.total_exams}
                        color='yellow'
                        icon={
                            DocumentTextIcon
                        }
                    />
                </div>


            </Section>

        </AuthenticatedLayout >
    );
}