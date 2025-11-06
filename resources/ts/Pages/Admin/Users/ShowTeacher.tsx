import { Exam, PageProps, User } from '@/types';
import { PaginationType } from '@/types/datatable';
import ShowUser from './ShowUser';
import Section from '@/Components/Section';
import ExamList from '@/Components/exam/ExamList';
import StatCard from '@/Components/StatCard';
import { DocumentTextIcon, CheckCircleIcon, ClockIcon } from '@heroicons/react/24/outline';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { trans } from '@/utils/translations';
import { hasPermission } from '@/utils/permissions';
import { usePage } from '@inertiajs/react';


interface Props {
    user: User;
    exams: PaginationType<Exam>;
}

export default function ShowTeacher({ user, exams }: Props) {
    const { auth } = usePage<PageProps>().props;

    const totalExams = exams.total || 0;
    const activeExams = exams.data.filter(exam => exam.is_active).length;
    const inactiveExams = totalExams - activeExams;

    const canDeleteUsers = hasPermission(auth.permissions, 'delete users');
    const canToggleStatus = hasPermission(auth.permissions, 'update users');


    return (
        <ShowUser user={user} canDelete={canDeleteUsers} canToggleStatus={canToggleStatus}
            breadcrumb={breadcrumbs.teacherShow(user)}
        >
            <Section title={trans('admin_pages.users.show_teacher_stats')} subtitle={trans('admin_pages.users.show_teacher_stats_subtitle')}>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <StatCard
                        title={trans('admin_pages.users.total_exams')}
                        value={totalExams}
                        icon={DocumentTextIcon}
                        color="blue"
                    />
                    <StatCard
                        title={trans('admin_pages.users.active_exams')}
                        value={activeExams}
                        icon={CheckCircleIcon}
                        color="green"
                    />
                    <StatCard
                        title={trans('admin_pages.users.inactive_exams')}
                        value={inactiveExams}
                        icon={ClockIcon}
                        color="yellow"
                    />
                </div>
            </Section>

            <Section title={trans('admin_pages.users.show_teacher_exams')} subtitle={trans('admin_pages.users.show_teacher_exams_subtitle')}>
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