import { useMemo } from 'react';
import { type AcademicYear } from '@/types';
import { formatDate } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Badge, Modal, Stat } from '@/Components/ui';
import { CalendarIcon, CheckCircleIcon, AcademicCapIcon } from '@heroicons/react/24/outline';

interface AcademicYearDetailsModalProps {
  isOpen: boolean;
  onClose: () => void;
  academicYear: AcademicYear | null;
}

/**
 * Modal displaying detailed information about an academic year and its semesters.
 */
export function AcademicYearDetailsModal({ isOpen, onClose, academicYear }: AcademicYearDetailsModalProps) {
  const { t } = useTranslations();

  const translations = useMemo(() => ({
    detailsTitle: t('admin_pages.academic_years.details_title'),
    period: t('admin_pages.academic_years.period'),
    status: t('admin_pages.academic_years.status'),
    current: t('admin_pages.academic_years.current'),
    archived: t('admin_pages.academic_years.archived'),
    future: t('admin_pages.academic_years.future'),
    semestersTitle: t('admin_pages.academic_years.semesters_title'),
    noSemesters: t('admin_pages.academic_years.no_semesters'),
    classesCount: t('admin_pages.academic_years.classes_count'),
  }), [t]);

  if (!academicYear) return null;

  const endDate = new Date(academicYear.end_date);
  const now = new Date();
  const isArchived = !academicYear.is_current && endDate < now;
  const statusType = academicYear.is_current ? 'success' : isArchived ? 'warning' : 'info';
  const statusLabel = academicYear.is_current
    ? translations.current
    : isArchived
      ? translations.archived
      : translations.future;

  return (
    <Modal isOpen={isOpen} onClose={onClose} title={academicYear.name} size='xl'>
      <div className="space-y-6">
        <Stat.Group columns={3}>
          <Stat.Item
            icon={CalendarIcon}
            title={translations.period}
            value={
              <span className="text-sm text-gray-900">
                {formatDate(academicYear.start_date)} - {formatDate(academicYear.end_date)}
              </span>
            }
          />
          <Stat.Item
            icon={CheckCircleIcon}
            title={translations.status}
            value={<Badge label={statusLabel} type={statusType} />}
          />
          <Stat.Item
            icon={AcademicCapIcon}
            title={translations.classesCount}
            value={
              <span className="text-sm font-medium text-gray-900">
                {academicYear.classes_count ?? 0}
              </span>
            }
          />
        </Stat.Group>

        <div>
          <h4 className="text-sm font-semibold text-gray-700 mb-3">{translations.semestersTitle}</h4>
          {academicYear.semesters && academicYear.semesters.length > 0 ? (
            <div className="space-y-2">
              {academicYear.semesters.map((semester, index) => (
                <div
                  key={semester.id}
                  className="flex items-center justify-between border border-gray-200 rounded-lg px-4 py-3 bg-gray-50"
                >
                  <div className="flex items-center space-x-3">
                    <Badge label={`S${index + 1}`} type={index === 0 ? 'info' : 'success'} size="sm" />
                    <span className="text-sm font-medium text-gray-900">{semester.name}</span>
                  </div>
                  <span className="text-sm text-gray-500">
                    {formatDate(semester.start_date)} - {formatDate(semester.end_date)}
                  </span>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-sm text-gray-500 text-center py-4">{translations.noSemesters}</p>
          )}
        </div>
      </div>
    </Modal>
  );
}
