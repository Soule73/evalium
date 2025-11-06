import Section from '@/Components/Section';
import { ExamAssignment, User, Group, PageProps } from '@/types';
import { PaginationType } from '@/types/datatable';
import StudentExamAssignmentList from '@/Components/exam/StudentExamAssignmentList';
import ShowUser from './ShowUser';
import StudentGroupsManagement from '@/Components/admin/StudentGroupsManagement';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { trans } from '@/utils/translations';
import { hasPermission } from '@/utils/permissions';
import { usePage } from '@inertiajs/react';


interface Props {
    user: User;
    examsAssignments: PaginationType<ExamAssignment>;
    availableGroups: Group[];
}

export default function ShowStudent({ user, examsAssignments, availableGroups }: Props) {

    const { auth } = usePage<PageProps>().props;

    const canDeleteUsers = hasPermission(auth.permissions, 'delete users');
    const canToggleStatus = hasPermission(auth.permissions, 'update users');

    return (
        <ShowUser user={user} canDelete={canDeleteUsers} canToggleStatus={canToggleStatus}
            breadcrumb={breadcrumbs.studentShow(user)}
        >

            <Section
                title={trans('admin_pages.users.show_student_groups')}
                subtitle={trans('admin_pages.users.show_student_groups_subtitle')}
            >
                <StudentGroupsManagement
                    userId={user.id}
                    currentGroups={user.groups || []}
                    availableGroups={availableGroups}
                />
            </Section>

            <Section title={trans('admin_pages.users.show_student_exams')} subtitle={trans('admin_pages.users.show_student_exams_subtitle')}>
                <StudentExamAssignmentList
                    data={examsAssignments}
                    variant="admin"
                    showFilters={true}
                    showSearch={true}
                />
            </Section>
        </ShowUser >
    );
}