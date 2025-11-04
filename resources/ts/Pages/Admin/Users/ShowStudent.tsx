import Section from '@/Components/Section';
import { ExamAssignment, User, Group } from '@/types';
import { PaginationType } from '@/types/datatable';
import StudentExamAssignmentList from '@/Components/exam/StudentExamAssignmentList';
import ShowUser from './ShowUser';
import StudentGroupsManagement from '@/Components/admin/StudentGroupsManagement';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { trans } from '@/utils/translations';


interface Props {
    user: User;
    examsAssignments: PaginationType<ExamAssignment>;
    availableGroups: Group[];
    canDelete?: boolean;
    canToggleStatus?: boolean;
}

export default function ShowStudent({ user, examsAssignments, availableGroups, canDelete, canToggleStatus }: Props) {

    return (
        <ShowUser user={user} canDelete={canDelete} canToggleStatus={canToggleStatus}
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