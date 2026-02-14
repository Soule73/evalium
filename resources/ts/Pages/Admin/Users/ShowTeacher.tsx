import { Assessment, PageProps, User } from '@/types';
import { PaginationType } from '@/types/datatable';
import ShowUser from './ShowUser';
import { DocumentTextIcon, CheckCircleIcon, ClockIcon } from '@heroicons/react/24/outline';
import { breadcrumbs, trans, hasPermission } from '@/utils';
import { usePage } from '@inertiajs/react';
import { Section, Stat } from '@/Components';
import { AssessmentList } from '@/Components/shared/lists';

interface TeacherStats {
    total: number;
    published: number;
    unpublished: number;
}

interface Props {
    user: User;
    assessments: PaginationType<Assessment>;
    stats: TeacherStats;
}

export default function ShowTeacher({ user, assessments, stats }: Props) {
    const { auth } = usePage<PageProps>().props;

    const canDeleteUsers = hasPermission(auth.permissions, 'delete users');
    const canToggleStatus = hasPermission(auth.permissions, 'update users');

    return (
        <ShowUser user={user} canDelete={canDeleteUsers} canToggleStatus={canToggleStatus}
            breadcrumb={breadcrumbs.teacherShow(user)}
        >
            <Section title={trans('admin_pages.users.show_teacher_stats')} subtitle={trans('admin_pages.users.show_teacher_stats_subtitle')}>
                <Stat.Group columns={3}>
                    <Stat.Item
                        title={trans('admin_pages.users.total_assessments')}
                        value={stats.total}
                        icon={DocumentTextIcon}
                    />
                    <Stat.Item
                        title={trans('admin_pages.users.active_assessments')}
                        value={stats.published}
                        icon={CheckCircleIcon}
                    />
                    <Stat.Item
                        title={trans('admin_pages.users.inactive_assessments')}
                        value={stats.unpublished}
                        icon={ClockIcon}
                    />
                </Stat.Group>
            </Section>

            <Section title={trans('admin_pages.users.show_teacher_assessments')} subtitle={trans('admin_pages.users.show_teacher_assessments_subtitle')}>
                <AssessmentList
                    data={assessments}
                    variant="admin"
                />
            </Section>
        </ShowUser>
    );
}