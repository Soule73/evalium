import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { BaseEntityList } from './BaseEntityList';
import { ClassSubject } from '@/types';
import { Badge } from '@examena/ui';
import { useTranslations } from '@/hooks';
import type { EntityListConfig } from './types/listConfig';
import type { PaginationType } from '@/types/datatable';

interface ClassSubjectListProps {
  data: PaginationType<ClassSubject>;
  variant?: 'admin' | 'teacher';
  showClassColumn?: boolean;
  showTeacherColumn?: boolean;
  showAssessmentsColumn?: boolean;
  onView?: (classSubject: ClassSubject) => void;
  onCreateAssessment?: (classSubject: ClassSubject) => void;
}

/**
 * Unified ClassSubjectList component for displaying class-subject assignments
 *
 * Supports variants:
 * - admin: View assignments with link to detail page
 * - teacher: View with create assessment action
 */
export function ClassSubjectList({
  data,
  variant = 'admin',
  showClassColumn = true,
  showTeacherColumn = true,
  showAssessmentsColumn = true,
  onView,
  onCreateAssessment,
}: ClassSubjectListProps) {
  const { t } = useTranslations();

  const config: EntityListConfig<ClassSubject> = useMemo(() => ({
    entity: 'class-subject',

    columns: [
      {
        key: 'class',
        labelKey: 'admin_pages.class_subjects.class',
        render: (classSubject) => {
          const levelInfo = classSubject.class?.level
            ? `${classSubject.class.level.name} (${classSubject.class.level.description})`
            : '';
          return (
            <>
              <div className="font-medium text-gray-900">
                {classSubject.class?.name}
              </div>
              {levelInfo && (
                <div className="text-sm text-gray-500">{levelInfo}</div>
              )}
            </>
          );
        },
        conditional: () => showClassColumn,
      },

      {
        key: 'subject',
        labelKey: 'admin_pages.class_subjects.subject',
        render: (classSubject) => (
          <>
            <span className="text-sm text-gray-900">{classSubject.subject?.name}</span>
            <Badge label={classSubject.subject?.code || ''} type="info" size="sm" />
          </>
        ),
      },

      {
        key: 'teacher',
        labelKey: 'admin_pages.class_subjects.teacher',
        render: (classSubject) => (
          <>
            <div className="text-sm font-medium text-gray-900">
              {classSubject.teacher?.name || '-'}
            </div>
            {classSubject.teacher?.email && (
              <div className="text-xs text-gray-500">
                {classSubject.teacher.email}
              </div>
            )}
          </>
        ),
        conditional: () => showTeacherColumn,
      },

      {
        key: 'coefficient',
        labelKey: 'admin_pages.class_subjects.coefficient',
        render: (classSubject) => (
          <Badge label={classSubject.coefficient.toString()} type="info" size="sm" />
        ),
      },

      {
        key: 'semester',
        labelKey: 'admin_pages.class_subjects.semester',
        render: (classSubject) => (
          <div className="text-sm text-gray-600">
            {classSubject.semester
              ? `S${classSubject.semester.order_number}`
              : t('admin_pages.class_subjects.all_year')}
          </div>
        ),
      },

      {
        key: 'status',
        labelKey: 'admin_pages.common.status',
        render: (classSubject) => {
          const isActive = !classSubject.valid_to;
          return (
            <Badge
              label={isActive ? t('admin_pages.class_subjects.active') : t('admin_pages.class_subjects.archived')}
              type={isActive ? 'success' : 'gray'}
              size="sm"
            />
          );
        },
      },

      {
        key: 'assessments',
        labelKey: 'admin_pages.classes.assessments',
        render: (classSubject) => (
          <div className="text-sm text-gray-600">
            {classSubject.assessments_count || 0}
          </div>
        ),
        conditional: () => showAssessmentsColumn,
      },
    ],

    actions: [
      {
        labelKey: 'admin_pages.common.view',
        onClick: (classSubject: ClassSubject) => {
          onView?.(classSubject) || router.visit(route('admin.class-subjects.show', classSubject.id));
        },
        color: 'secondary' as const,
        variant: 'outline' as const,
        conditional: (_item: ClassSubject, v) => v === 'admin',
      },
      {
        labelKey: 'teacher_class_pages.show.create_assessment',
        onClick: (classSubject: ClassSubject) => {
          onCreateAssessment?.(classSubject);
        },
        color: 'primary' as const,
        variant: 'solid' as const,
        conditional: (_item: ClassSubject, v) => v === 'teacher' && !!onCreateAssessment,
      },
    ],
  }), [showClassColumn, showTeacherColumn, showAssessmentsColumn, onView, onCreateAssessment, t]);

  return <BaseEntityList data={data} config={config} variant={variant} />;
}
