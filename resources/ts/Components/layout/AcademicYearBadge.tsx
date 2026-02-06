import { CheckIcon } from '@heroicons/react/24/outline';
import { trans } from '@/utils';
import type { AcademicYear } from '@/types/models/mcd/academicYear';

interface AcademicYearBadgeProps {
  year: AcademicYear;
  isSelected?: boolean;
  size?: 'sm' | 'md';
}

export function AcademicYearBadge({
  year,
  isSelected = false,
  size = 'md',
}: AcademicYearBadgeProps) {
  const sizeClasses = {
    sm: 'text-xs px-2 py-0.5',
    md: 'text-sm px-2.5 py-0.5',
  };

  return (
    <div className="flex items-center gap-2">
      <span className="font-medium">{year.name}</span>

      {year.is_current && (
        <span
          className={`inline-flex items-center rounded-full bg-green-100 ${sizeClasses[size]} font-medium text-green-800`}
        >
          {trans('admin_pages.academic_years.current')}
        </span>
      )}

      {isSelected && (
        <CheckIcon className="h-5 w-5 text-green-600" />
      )}
    </div>
  );
}
