import { ExamAssignment, User, Group, PageProps } from '@/types';
import { PaginationType } from '@/types/datatable';
import ShowUser from './ShowUser';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';
import { hasPermission } from '@/utils';
import { usePage } from '@inertiajs/react';
import { Section, StudentGroups, StudentExamAssignmentList } from '@/Components';


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
                <StudentGroups
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