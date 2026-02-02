import { AcademicYear } from '@/types';
import { Badge } from '@/Components/ui';
import { formatDate } from '@/utils';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';

interface AcademicYearCardProps {
  academicYear: AcademicYear;
  onClick?: (academicYear: AcademicYear) => void;
  className?: string;
}

/**
 * Card component for displaying Academic Year information
 */
const AcademicYearCard: React.FC<AcademicYearCardProps> = ({
  academicYear,
  onClick,
  className = ''
}) => {
  const handleClick = () => {
    if (onClick) {
      onClick(academicYear);
    } else {
      router.get(route('admin.academic-years.show', academicYear.id));
    }
  };

  return (
    <div
      onClick={handleClick}
      className={`
                border rounded-lg p-4 cursor-pointer
                transition-all duration-200
                hover:shadow-md hover:border-blue-400
                ${academicYear.is_current ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white'}
                ${className}
            `}
    >
      <div className="flex items-start justify-between mb-3">
        <div>
          <h3 className="text-lg font-semibold text-gray-900">
            {academicYear.name}
          </h3>
          <p className="text-sm text-gray-500 mt-1">
            {formatDate(academicYear.start_date)} - {formatDate(academicYear.end_date)}
          </p>
        </div>
        {academicYear.is_current && (
          <Badge label="Current" type="success" size="sm" />
        )}
      </div>

      <div className="grid grid-cols-2 gap-4 mt-4">
        <div className="flex flex-col">
          <span className="text-xs text-gray-500 uppercase">Semesters</span>
          <span className="text-lg font-semibold text-gray-900">
            {academicYear.semesters?.length || 0}
          </span>
        </div>
        <div className="flex flex-col">
          <span className="text-xs text-gray-500 uppercase">Classes</span>
          <span className="text-lg font-semibold text-gray-900">
            {academicYear.classes?.length || 0}
          </span>
        </div>
      </div>
    </div>
  );
};

export default AcademicYearCard;
