import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import { formatDate } from '@/utils/formatters';
import { Exam, User } from '@/types';
import { route } from 'ziggy-js';
import Section from '@/Components/Section';
import StatCard from '@/Components/StatCard';
import { ArrowTrendingUpIcon, DocumentTextIcon, QuestionMarkCircleIcon, UserGroupIcon } from '@heroicons/react/24/outline';


interface Stats {
    total_exams: number;
    total_questions: number;
    students_evaluated: number;
    average_score: number;
}

interface Props {
    user: User;
    stats: Stats;
    recent_exams: Exam[];
}

export default function TeacherDashboard({ user, stats, recent_exams }: Props) {
    const handleCreateExam = () => {
        router.visit(route('teacher.exams.create'));
    };

    const handleViewExams = () => {
        router.visit(route('teacher.exams.index'));
    };

    const handleViewReviews = () => {
        router.visit(route('teacher.exams.reviews'));
    };

    const handleViewExam = (examId: number) => {
        router.visit(route('teacher.exams.show', { exam: examId }));
    };

    return (
        <AuthenticatedLayout title="Tableau de bord enseignant">


            <Section title={`Bonjour, ${user.name} !`}
                subtitle="Gérez vos examens et suivez les performances de vos étudiants."
                actions={
                    <div className="flex flex-col md:flex-row space-y-2 md:space-x-3 md:space-y-0">
                        <Button
                            onClick={handleCreateExam}
                            color="secondary"
                            variant='outline'
                        >
                            Créer un examen
                        </Button>
                        <Button
                            onClick={handleViewExams}
                            variant='outline'
                            color="secondary"
                        >
                            Gérer mes examens
                        </Button>
                        <Button
                            onClick={handleViewReviews}
                            variant='outline'
                            color="secondary"
                        >
                            Corriger des examens
                        </Button>
                    </div>}
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


            </Section>
            <Section
                title="Examens récents"
                actions={
                    <Button
                        onClick={handleViewExams}
                        color="secondary"
                        variant='outline'
                    >
                        Voir tous les examens
                    </Button>
                }
            >

                {recent_exams.length === 0 ? (
                    <div className="text-center py-8">
                        <svg className="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p className="text-gray-600 mb-4">Aucun examen créé pour le moment</p>
                        <Button onClick={handleCreateExam} color="primary">
                            Créer votre premier examen
                        </Button>
                    </div>
                ) : (
                    <div className="space-y-4">
                        {recent_exams.map((exam) => (
                            <div
                                key={exam.id}
                                className="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 cursor-pointer transition-colors"
                                onClick={() => handleViewExam(exam.id)}
                            >
                                <div className="flex justify-between items-start">
                                    <div className="flex-1">
                                        <h4 className="font-medium text-gray-900 mb-1">
                                            {exam.title}
                                        </h4>
                                        {exam.description && (
                                            <p className="text-sm text-gray-600 mb-2">
                                                {exam.description.length > 100
                                                    ? `${exam.description.substring(0, 100)}...`
                                                    : exam.description
                                                }
                                            </p>
                                        )}
                                        <div className="flex items-center space-x-4 text-xs text-gray-500">
                                            {/* <span>{exam.questions_count} question{exam.questions_count !== 1 ? 's' : ''}</span> */}
                                            <span>Créé le {formatDate(exam.created_at)}</span>
                                        </div>
                                    </div>
                                    <svg className="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </Section>

        </AuthenticatedLayout >
    );
}