import { useMemo } from 'react';
import { type Assessment, type User } from '@/types';
import { type PaginationType } from '@/types/datatable';
import { DocumentTextIcon, CheckCircleIcon, ClockIcon } from '@heroicons/react/24/outline';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Section, Stat } from '@/Components';
import { AssessmentList } from '@/Components/shared/lists';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import UserBaseInfo from '@/Components/features/users/UserBaseInfo';

interface TeacherStats {
  total: number;
  published: number;
  unpublished: number;
}

interface Props {
  user: User;
  assessments: PaginationType<Assessment>;
  stats: TeacherStats;
  canDelete?: boolean;
  canToggleStatus?: boolean;
}

export default function ShowTeacher({
  user,
  assessments,
  stats,
  canDelete,
  canToggleStatus,
}: Props) {
  const { t } = useTranslations();
  const breadcrumbs = useBreadcrumbs();

  const translations = useMemo(
    () => ({
      statsTitle: t('admin_pages.users.show_teacher_stats'),
      statsSubtitle: t('admin_pages.users.show_teacher_stats_subtitle'),
      totalAssessments: t('admin_pages.users.total_assessments'),
      activeAssessments: t('admin_pages.users.active_assessments'),
      inactiveAssessments: t('admin_pages.users.inactive_assessments'),
      assessmentsTitle: t('admin_pages.users.show_teacher_assessments'),
      assessmentsSubtitle: t('admin_pages.users.show_teacher_assessments_subtitle'),
    }),
    [t],
  );

  return (
    <AuthenticatedLayout

      breadcrumb={breadcrumbs.admin.teacherShow(user)}
    >

      <UserBaseInfo
        user={user}
        canDelete={canDelete}
        canToggleStatus={canToggleStatus}
        backRoute="admin.teachers.index"
      />
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

      <Section
        title={translations.assessmentsTitle}
        subtitle={translations.assessmentsSubtitle}
      >
        <AssessmentList data={assessments} variant="admin" />
      </Section>
    </AuthenticatedLayout>
  );
}
