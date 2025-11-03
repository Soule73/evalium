import { Exam, User } from '@/types';
import { PaginationType } from '@/types/datatable';
import ShowUser from './ShowUser';
import Section from '@/Components/Section';
import ExamList from '@/Components/exam/ExamList';
import StatCard from '@/Components/StatCard';
import { DocumentTextIcon, CheckCircleIcon, ClockIcon } from '@heroicons/react/24/outline';
import { breadcrumbs } from '@/utils/breadcrumbs';


interface Props {
    user: User;
    exams: PaginationType<Exam>;
    canDelete?: boolean;
    canToggleStatus?: boolean;
}

export default function ShowTeacher({ user, exams, canDelete, canToggleStatus }: Props) {
    const totalExams = exams.total || 0;
    const activeExams = exams.data.filter(exam => exam.is_active).length;
    const inactiveExams = totalExams - activeExams;

    return (
        <ShowUser user={user} canDelete={canDelete} canToggleStatus={canToggleStatus}
            breadcrumb={breadcrumbs.teacherShow(user)}
        >
            <Section title="Statistiques" subtitle="Aperçu de l'activité de l'enseignant">
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <StatCard
                        title="Total d'examens"
                        value={totalExams}
                        icon={DocumentTextIcon}
                        color="blue"
                    />
                    <StatCard
                        title="Examens actifs"
                        value={activeExams}
                        icon={CheckCircleIcon}
                        color="green"
                    />
                    <StatCard
                        title="Examens inactifs"
                        value={inactiveExams}
                        icon={ClockIcon}
                        color="yellow"
                    />
                </div>
            </Section>

            <Section title="Examens créés" subtitle="Liste des examens créés par l'enseignant">
                <ExamList
                    data={exams}
                    variant="admin"
                    showFilters={true}
                    showSearch={true}
                />
            </Section>
        </ShowUser >
    );
}