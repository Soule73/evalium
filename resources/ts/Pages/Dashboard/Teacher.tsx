import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { route } from 'ziggy-js';
import { AcademicCapIcon, BookOpenIcon, ClipboardDocumentListIcon, CalendarDaysIcon } from '@heroicons/react/24/outline';
import { breadcrumbs } from '@/utils';
import { trans } from '@/utils';
import { Button, Section, Stat, DataTable } from '@/Components';
import { DataTableConfig, PaginationType } from '@/types/datatable';

interface ClassSubject {
    id: number;
    class: {
        id: number;
        name: string;
        level?: { name: string };
        academic_year?: { name: string };
    };
    subject: { id: number; name: string; code?: string };
}

interface Assessment {
    id: number;
    title: string;
    scheduled_at: string;
    classSubject?: {
        class: {
            name: string;
            level?: { name: string };
            academic_year?: { name: string };
        };
        subject: {
            name: string;
            code?: string;
        };
    };
}

interface Stats {
    total_classes: number;
    total_subjects: number;
    total_assessments: number;
    past_assessments: number;
    upcoming_assessments: number;
}

interface Props {
    activeAssignments: PaginationType<ClassSubject>;
    pastAssessments: PaginationType<Assessment>;
    upcomingAssessments: PaginationType<Assessment>;
    stats: Stats;
    filters: { search?: string };
}


export default function TeacherDashboard({ stats, activeAssignments, pastAssessments, upcomingAssessments }: Props) {
    const handleViewAssessments = () => {
        router.visit(route('teacher.assessments.index'));
    };

    const handleViewClasses = () => {
        router.visit(route('teacher.classes.index'));
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const activeAssignmentsTableConfig: DataTableConfig<ClassSubject> = {
        columns: [
            {
                key: 'class',
                label: trans('dashboard.teacher.class'),
                render: (assignment) => (
                    <div>
                        <div className="font-medium text-gray-900">
                            {assignment.class.name}
                        </div>
                        {(assignment.class.level?.name || assignment.class.academic_year?.name) && (
                            <div className="text-sm text-gray-500">
                                {assignment.class.level?.name}
                                {assignment.class.level?.name && assignment.class.academic_year?.name && ' - '}
                                {assignment.class.academic_year?.name}
                            </div>
                        )}
                    </div>
                ),
            },
            {
                key: 'subject',
                label: trans('dashboard.teacher.subject'),
                render: (assignment) => (
                    <div>
                        <div className="font-medium text-gray-900">
                            {assignment.subject.name}
                        </div>
                        {assignment.subject.code && (
                            <div className="text-sm text-gray-500">
                                {assignment.subject.code}
                            </div>
                        )}
                    </div>
                ),
            },
        ],
        filters: [],
        emptyState: {
            title: trans('dashboard.teacher.no_active_assignments'),
            subtitle: '',
        },
    };

    const pastAssessmentsTableConfig: DataTableConfig<Assessment> = {
        columns: [
            {
                key: 'title',
                label: trans('dashboard.teacher.assessment_title'),
                render: (assessment) => (
                    <div className="font-medium text-gray-900">
                        {assessment.title}
                    </div>
                ),
            },
            {
                key: 'class',
                label: trans('dashboard.teacher.class'),
                render: (assessment) => (
                    <div>
                        <div className="text-sm text-gray-900">
                            {assessment.classSubject?.class?.name || '-'}
                        </div>
                        {assessment.classSubject?.class && (assessment.classSubject.class.level?.name || assessment.classSubject.class.academic_year?.name) && (
                            <div className="text-xs text-gray-500">
                                {assessment.classSubject.class.level?.name}
                                {assessment.classSubject.class.level?.name && assessment.classSubject.class.academic_year?.name && ' - '}
                                {assessment.classSubject.class.academic_year?.name}
                            </div>
                        )}
                    </div>
                ),
            },
            {
                key: 'subject',
                label: trans('dashboard.teacher.subject'),
                render: (assessment) => (
                    <div>
                        <div className="text-sm text-gray-900">
                            {assessment.classSubject?.subject?.name || '-'}
                        </div>
                        {assessment.classSubject?.subject?.code && (
                            <div className="text-xs text-gray-500">
                                {assessment.classSubject.subject.code}
                            </div>
                        )}
                    </div>
                ),
            },
            {
                key: 'scheduled_at',
                label: trans('dashboard.teacher.scheduled_at'),
                render: (assessment) => (
                    <div className="text-sm text-gray-600">
                        {formatDate(assessment.scheduled_at)}
                    </div>
                ),
            },
        ],
        filters: [],
        emptyState: {
            title: trans('dashboard.teacher.no_past_assessments'),
            subtitle: '',
        },
    };

    const upcomingAssessmentsTableConfig: DataTableConfig<Assessment> = {
        columns: [
            {
                key: 'title',
                label: trans('dashboard.teacher.assessment_title'),
                render: (assessment) => (
                    <div className="font-medium text-gray-900">
                        {assessment.title}
                    </div>
                ),
            },
            {
                key: 'class',
                label: trans('dashboard.teacher.class'),
                render: (assessment) => (
                    <div>
                        <div className="text-sm text-gray-900">
                            {assessment.classSubject?.class?.name || '-'}
                        </div>
                        {assessment.classSubject?.class && (assessment.classSubject.class.level?.name || assessment.classSubject.class.academic_year?.name) && (
                            <div className="text-xs text-gray-500">
                                {assessment.classSubject.class.level?.name}
                                {assessment.classSubject.class.level?.name && assessment.classSubject.class.academic_year?.name && ' - '}
                                {assessment.classSubject.class.academic_year?.name}
                            </div>
                        )}
                    </div>
                ),
            },
            {
                key: 'subject',
                label: trans('dashboard.teacher.subject'),
                render: (assessment) => (
                    <div>
                        <div className="text-sm text-gray-900">
                            {assessment.classSubject?.subject?.name || '-'}
                        </div>
                        {assessment.classSubject?.subject?.code && (
                            <div className="text-xs text-gray-500">
                                {assessment.classSubject.subject.code}
                            </div>
                        )}
                    </div>
                ),
            },
            {
                key: 'scheduled_at',
                label: trans('dashboard.teacher.scheduled_at'),
                render: (assessment) => (
                    <div className="text-sm text-gray-600">
                        {formatDate(assessment.scheduled_at)}
                    </div>
                ),
            },
        ],
        filters: [],
        emptyState: {
            title: trans('dashboard.teacher.no_upcoming_assessments'),
            subtitle: '',
        },
    };

    return (
        <AuthenticatedLayout title={trans('dashboard.title.teacher')}
            breadcrumb={breadcrumbs.dashboard()}
        >
            {/* Statistiques principales */}
            <Stat.Group columns={4} className="mb-8" data-e2e="dashboard-content">
                <Stat.Item
                    title={trans('dashboard.teacher.total_classes')}
                    value={stats.total_classes}
                    icon={AcademicCapIcon}
                />
                <Stat.Item
                    title={trans('dashboard.teacher.total_subjects')}
                    value={stats.total_subjects}
                    icon={BookOpenIcon}
                />
                <Stat.Item
                    title={trans('dashboard.teacher.total_assessments')}
                    value={stats.total_assessments}
                    icon={ClipboardDocumentListIcon}
                />
                <Stat.Item
                    title={trans('dashboard.teacher.upcoming_assessments')}
                    value={stats.upcoming_assessments}
                    icon={CalendarDaysIcon}
                />
            </Stat.Group>

            {/* Affectations actives */}
            <Section
                title={trans('dashboard.teacher.active_assignments')}
                subtitle={trans('dashboard.teacher.active_assignments_subtitle')}
                actions={
                    <Button
                        onClick={handleViewClasses}
                        color="secondary"
                        variant='outline'
                        size='sm'
                    >
                        {trans('dashboard.teacher.view_all_classes')}
                    </Button>
                }
            >
                <DataTable data={activeAssignments} config={activeAssignmentsTableConfig} />
            </Section>

            {/* Évaluations passées */}
            <Section
                title={trans('dashboard.teacher.past_assessments')}
                subtitle={trans('dashboard.teacher.past_assessments_subtitle')}
                actions={
                    <Button
                        onClick={handleViewAssessments}
                        color="secondary"
                        variant='outline'
                        size='sm'
                    >
                        {trans('dashboard.teacher.view_all_assessments')}
                    </Button>
                }
            >
                <DataTable data={pastAssessments} config={pastAssessmentsTableConfig} />
            </Section>

            {/* Évaluations à venir */}
            <Section
                title={trans('dashboard.teacher.upcoming_assessments_section')}
                subtitle={trans('dashboard.teacher.upcoming_assessments_subtitle')}
            >
                <DataTable data={upcomingAssessments} config={upcomingAssessmentsTableConfig} />
            </Section>

        </AuthenticatedLayout >
    );
}