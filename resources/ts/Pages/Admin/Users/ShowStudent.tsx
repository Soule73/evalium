import Section from '@/Components/Section';
import { ExamAssignment, User, Group } from '@/types';
import { PaginationType } from '@/types/datatable';
import StudentExamAssignmentList from '@/Components/exam/StudentExamAssignmentList';
import ShowUser from './ShowUser';
import StudentGroupsManagement from '@/Components/admin/StudentGroupsManagement';
import { breadcrumbs } from '@/utils/breadcrumbs';


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
            breadcrumb={breadcrumbs.adminStudentShow(user)}
        >

            <Section
                title="Groupes"
                subtitle="Gestion des groupes de l'étudiant"
            >
                <StudentGroupsManagement
                    userId={user.id}
                    currentGroups={user.groups || []}
                    availableGroups={availableGroups}
                />
            </Section>

            <Section title="Examens assignés" subtitle="Liste des examens auxquels l'étudiant est inscrit">
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