import { Assessment, PageProps, User } from '@/types';
import { PaginationType } from '@/types/datatable';
import ShowUser from './ShowUser';
import { DocumentTextIcon, CheckCircleIcon, ClockIcon } from '@heroicons/react/24/outline';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';
import { hasPermission } from '@/utils';
import { usePage } from '@inertiajs/react';
import { Section, StatCard } from '@/Components';
import { AssessmentList } from '@/Components/shared/lists';


interface Props {
    user: User;
    assessments: PaginationType<Assessment>;
}

export default function ShowTeacher({ user, assessments }: Props) {
    const { auth } = usePage<PageProps>().props;

    const totalAssessments = assessments.total || 0;
    const activeAssessments = assessments.data.filter(assessment => assessment.is_published).length;
    const inactiveAssessments = totalAssessments - activeAssessments;

    const canDeleteUsers = hasPermission(auth.permissions, 'delete users');
    const canToggleStatus = hasPermission(auth.permissions, 'update users');


    return (
        <ShowUser user={user} canDelete={canDeleteUsers} canToggleStatus={canToggleStatus}
            breadcrumb={breadcrumbs.teacherShow(user)}
        >
            <Section title={trans('admin_pages.users.show_teacher_stats')} subtitle={trans('admin_pages.users.show_teacher_stats_subtitle')}>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <StatCard
                        title={trans('admin_pages.users.total_assessments')}
                        value={totalAssessments}
                        icon={DocumentTextIcon}
                        color="blue"
                    />
                    <StatCard
                        title={trans('admin_pages.users.active_assessments')}
                        value={activeAssessments}
                        icon={CheckCircleIcon}
                        color="green"
                    />
                    <StatCard
                        title={trans('admin_pages.users.inactive_assessments')}
                        value={inactiveAssessments}
                        icon={ClockIcon}
                        color="yellow"
                    />
                </div>
            </Section>

            <Section title={trans('admin_pages.users.show_teacher_assessments')} subtitle={trans('admin_pages.users.show_teacher_assessments_subtitle')}>
                <AssessmentList
                    data={assessments}
                    variant="admin"
                />
            </Section>
        </ShowUser >
    );
}