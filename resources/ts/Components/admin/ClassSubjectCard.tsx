import { Badge } from '@/Components';
import { type ClassSubject } from '@/types';
import { formatDate } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { AcademicCapIcon, UserIcon, HashtagIcon, CalendarIcon } from '@heroicons/react/24/outline';

interface ClassSubjectCardProps {
  classSubject: ClassSubject;
  onClick?: () => void;
}

export function ClassSubjectCard({ classSubject, onClick }: ClassSubjectCardProps) {
  const { t } = useTranslations();
  const isActive = !classSubject.valid_to;

  return (
    <div
      onClick={onClick}
      className={`bg-white rounded-lg border border-gray-200 p-5 shadow-sm transition-all ${onClick ? 'hover:border-primary-300 hover:shadow-md cursor-pointer' : ''
        }`}
    >
      <div className="flex items-start justify-between mb-4">
        <div className="flex items-center space-x-2">
          <Badge label={classSubject.subject?.code || ''} type="info" size="sm" />
          <h3 className="text-lg font-semibold text-gray-900">
            {classSubject.subject?.name}
          </h3>
        </div>
        <Badge
          label={isActive ? t('admin_pages.class_subjects.active') : t('admin_pages.class_subjects.archived')}
          type={isActive ? 'success' : 'gray'}
          size="sm"
        />
      </div>

      <div className="space-y-3">
        <div className="flex items-start space-x-2">
          <AcademicCapIcon className="w-4 h-4 text-gray-400 mt-0.5" />
          <div>
            <div className="text-sm font-medium text-gray-900">
              {classSubject.class?.name}
            </div>
            <div className="text-xs text-gray-500">
              {classSubject.class?.level?.name}
            </div>
          </div>
        </div>

        <div className="flex items-center space-x-2">
          <UserIcon className="w-4 h-4 text-gray-400" />
          <div className="text-sm text-gray-600">
            {classSubject.teacher?.name}
          </div>
        </div>

        <div className="flex items-center justify-between">
          <div className="flex items-center space-x-2">
            <HashtagIcon className="w-4 h-4 text-gray-400" />
            <span className="text-sm text-gray-600">
              {t('admin_pages.class_subjects.coef')}:
            </span>
            <Badge label={classSubject.coefficient.toString()} type="info" size="sm" />
          </div>

          {classSubject.semester && (
            <Badge label={`S${classSubject.semester.order_number}`} type="info" size="sm" />
          )}
        </div>

        <div className="pt-3 border-t border-gray-100">
          <div className="flex items-center space-x-2 text-xs text-gray-500">
            <CalendarIcon className="w-4 h-4 text-gray-400" />
            <span>
              {formatDate(classSubject.valid_from)}
              {classSubject.valid_to && ` - ${formatDate(classSubject.valid_to)}`}
            </span>
          </div>
        </div>

        {classSubject.assessments_count !== undefined && (
          <div className="text-xs text-gray-500">
            {classSubject.assessments_count} {t('admin_pages.class_subjects.assessments')}
          </div>
        )}
      </div>
    </div>
  );
}
