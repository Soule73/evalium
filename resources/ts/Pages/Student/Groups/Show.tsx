import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, ExamAssignment, Group, Level } from '@/types';
import Section from '@/Components/Section';
import StudentExamAssignmentList from '@/Components/exam/StudentExamAssignmentList';
import { PaginationType } from '@/types/datatable';
import { breadcrumbs } from '@/utils/breadcrumbs';
import { route } from 'ziggy-js';
import { Button } from '@/Components';
import Badge from '@/Components/Badge';
import TextEntry from '@/Components/TextEntry';
import AlertEntry from '@/Components/AlertEntry';

interface Props extends PageProps {
    group: Group & { level: Level };
    pagination: PaginationType<ExamAssignment>;
    isActiveGroup: boolean;
}

export default function Show({ group, pagination, isActiveGroup }: Props) {
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    };

    return (
        <AuthenticatedLayout
            title={`Examens - ${group.level.name}`}
            breadcrumb={breadcrumbs.studentGroupShow(group.level.name)}>
            <Section title="Informations sur le groupe"
                subtitle={
                    isActiveGroup ? (
                        <Badge label="Groupe actif" type="success" />

                    ) : (
                        <Badge label="Groupe inactif" type="error" />
                    )
                }
                actions={
                    <Button
                        color="secondary"
                        variant="outline"
                        size="sm"
                        onClick={() => route('student.exams.index')}
                    >
                        Retour aux groupes
                    </Button>
                }
            >
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <TextEntry
                        label="Niveau"
                        value={group.level.name}
                    />
                    <TextEntry
                        label="Année académique"
                        value={group.academic_year}
                    />
                    <TextEntry
                        label="Description"
                        value={group.description}
                    />
                    <TextEntry
                        label="Période"
                        value={`${formatDate(group.start_date)} - ${formatDate(group.end_date)}`}
                    />
                </div>

                {!isActiveGroup &&
                    <AlertEntry title="Vous ne faites plus partie de ce groupe" type="warning">
                        <p className="text-sm">
                            Vous ne pouvez voir que les examens auxquels vous avez participé.
                        </p>
                    </AlertEntry>
                }
            </Section>
            <Section
                title={`Examens (${pagination?.total || 0})`}
            >
                <StudentExamAssignmentList
                    data={pagination}
                    variant="full"
                    showFilters={true}
                    showSearch={true}
                />
            </Section>
        </AuthenticatedLayout>
    );
}
