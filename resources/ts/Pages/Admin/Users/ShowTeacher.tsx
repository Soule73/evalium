import { useMemo } from 'react';
import { type Assessment, type PageProps, type User } from '@/types';
import { type PaginationType } from '@/types/datatable';
import ShowUser from './ShowUser';
import { DocumentTextIcon, CheckCircleIcon, ClockIcon } from '@heroicons/react/24/outline';
import { breadcrumbs, hasPermission } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
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
    const { t } = useTranslations();

    const canDeleteUsers = hasPermission(auth.permissions, 'delete users');
    const canToggleStatus = hasPermission(auth.permissions, 'update users');

    const translations = useMemo(() => ({
        statsTitle: t('admin_pages.users.show_teacher_stats'),
        statsSubtitle: t('admin_pages.users.show_teacher_stats_subtitle'),
        totalAssessments: t('admin_pages.users.total_assessments'),
        activeAssessments: t('admin_pages.users.active_assessments'),
        inactiveAssessments: t('admin_pages.users.inactive_assessments'),
        assessmentsTitle: t('admin_pages.users.show_teacher_assessments'),
        assessmentsSubtitle: t('admin_pages.users.show_teacher_assessments_subtitle'),
    }), [t]);

    return (
        <ShowUser user={user} canDelete={canDeleteUsers} canToggleStatus={canToggleStatus}
            breadcrumb={breadcrumbs.teacherShow(user)}
        >
            <Section title={translations.statsTitle} subtitle={translations.statsSubtitle}>
                <Stat.Group columns={3}>
                    <Stat.Item
                        title={translations.totalAssessments}
                        value={stats.total}
                        icon={DocumentTextIcon}
                    />
                    <Stat.Item
                        title={translations.activeAssessments}
                        value={stats.published}
                        icon={CheckCircleIcon}
                    />
                    <Stat.Item
                        title={translations.inactiveAssessments}
                        value={stats.unpublished}
                        icon={ClockIcon}
                    />
                </Stat.Group>
            </Section>

            <Section title={translations.assessmentsTitle} subtitle={translations.assessmentsSubtitle}>
                <AssessmentList
                    data={assessments}
                    variant="admin"
                />
            </Section>
        </ShowUser>
    );
}