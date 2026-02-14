import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type ClassSubject, type PageProps, type User } from '@/types';
import { type PaginationType } from '@/types/datatable';
import { breadcrumbs, trans, formatDate, hasPermission } from '@/utils';
import { Section, Badge, Stat, ActionGroup } from '@/Components';
import { ClassSubjectHistoryList } from '@/Components/shared/lists';
import {
  ReplaceTeacherModal,
  UpdateCoefficientModal,
  TerminateClassSubjectModal,
} from '@/Components/features';
import { route } from 'ziggy-js';
import { AcademicCapIcon, UserIcon, HashtagIcon, CalendarIcon, ClockIcon, BookOpenIcon, ArrowLeftIcon, XMarkIcon } from '@heroicons/react/24/outline';

interface Props extends PageProps {
  classSubject: ClassSubject;
  history: PaginationType<ClassSubject>;
  teachers?: User[];
}

export default function ClassSubjectShow({ classSubject, history, teachers = [], auth }: Props) {
  const canUpdate = hasPermission(auth.permissions, 'update class subjects');

  const [replaceTeacherModal, setReplaceTeacherModal] = useState(false);
  const [coefficientModal, setCoefficientModal] = useState(false);
  const [terminateModal, setTerminateModal] = useState(false);

  const handleBack = () => {
    router.visit(route('admin.class-subjects.index'));
  };

  const isActive = !classSubject.valid_to;

  const levelInfo = useMemo(() =>
    classSubject.class?.level
      ? `${classSubject.class.level.name} (${classSubject.class.level.description})`
      : '',
    [classSubject.class?.level]
  );

  return (
    <AuthenticatedLayout
      title={trans('admin_pages.class_subjects.show_title')}
      breadcrumb={breadcrumbs.admin.showClassSubject(classSubject)}
    >
      <div className="space-y-6">
        <Section
          title={trans('admin_pages.class_subjects.show_title')}
          subtitle={trans('admin_pages.class_subjects.show_subtitle')}
          actions={
            <ActionGroup
              actions={[
                { label: trans('admin_pages.common.back'), onClick: handleBack, color: 'secondary', icon: ArrowLeftIcon },
                ...(canUpdate && isActive ? [{ label: trans('admin_pages.class_subjects.replace_teacher'), onClick: () => setReplaceTeacherModal(true), color: 'primary' as const, icon: UserIcon }] : []),
              ]}
              dropdownActions={canUpdate && isActive ? [
                { label: trans('admin_pages.class_subjects.update_coefficient'), onClick: () => setCoefficientModal(true), icon: HashtagIcon, color: 'warning' as const },
                'divider',
                { label: trans('admin_pages.class_subjects.terminate'), onClick: () => setTerminateModal(true), icon: XMarkIcon, color: 'danger' as const },
              ] : []}
            />
          }
        >
          <Stat.Group columns={2}>
            <Stat.Item
              icon={AcademicCapIcon}
              title={trans('admin_pages.class_subjects.class')}
              value={
                <div>
                  <div className="text-sm font-semibold text-gray-900">{classSubject.class?.name}</div>
                  <div className="text-xs text-gray-500">{levelInfo}</div>
                </div>
              }
            />
            <Stat.Item
              icon={BookOpenIcon}
              title={trans('admin_pages.class_subjects.subject')}
              value={
                <div>
                  <span className="text-sm font-semibold text-gray-900">{classSubject.subject?.name}</span>
                  <Badge label={classSubject.subject?.code || ''} type="info" size="sm" />
                </div>
              }
            />
            <Stat.Item
              icon={UserIcon}
              title={trans('admin_pages.class_subjects.teacher')}
              value={
                <div>
                  <div className="text-sm font-semibold text-gray-900">{classSubject.teacher?.name}</div>
                  <div className="text-xs text-gray-500">{classSubject.teacher?.email}</div>
                </div>
              }
            />
            <Stat.Item
              icon={HashtagIcon}
              title={trans('admin_pages.class_subjects.coefficient')}
              value={<Badge label={classSubject.coefficient.toString()} type="info" size="sm" />}
            />
            <Stat.Item
              icon={ClockIcon}
              title={trans('admin_pages.common.status')}
              value={
                <Badge
                  label={isActive ? trans('admin_pages.class_subjects.active') : trans('admin_pages.class_subjects.archived')}
                  type={isActive ? 'success' : 'gray'}
                  size="sm"
                />
              }
            />
          </Stat.Group>

          <div className="mt-6 pt-6 border-t border-gray-200">
            <Stat.Group columns={2}>
              <Stat.Item
                icon={CalendarIcon}
                title={trans('admin_pages.class_subjects.validity_period')}
                value={
                  <span className="text-sm text-gray-700">
                    {formatDate(classSubject.valid_from)}
                    {classSubject.valid_to && ` - ${formatDate(classSubject.valid_to)}`}
                  </span>
                }
              />
              {classSubject.semester && (
                <Stat.Item
                  icon={CalendarIcon}
                  title={trans('admin_pages.class_subjects.semester')}
                  value={<Badge label={`S${classSubject.semester.order_number}`} type="info" size="sm" />}
                />
              )}
            </Stat.Group>
          </div>
        </Section>

        {history.data.length > 0 && (
          <Section
            title={trans('admin_pages.class_subjects.history_title')}
            subtitle={trans('admin_pages.class_subjects.history_subtitle')}
          >
            <ClassSubjectHistoryList data={history} />
          </Section>
        )}
      </div>

      <ReplaceTeacherModal
        isOpen={replaceTeacherModal}
        onClose={() => setReplaceTeacherModal(false)}
        classSubject={classSubject}
        teachers={teachers}
      />

      <UpdateCoefficientModal
        isOpen={coefficientModal}
        onClose={() => setCoefficientModal(false)}
        classSubject={classSubject}
      />

      <TerminateClassSubjectModal
        isOpen={terminateModal}
        onClose={() => setTerminateModal(false)}
        classSubject={classSubject}
      />
    </AuthenticatedLayout>
  );
}
