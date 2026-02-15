import { type Semester } from '@/types';
import { Badge } from '@/Components/ui';
import { formatDate } from '@/utils';

interface SemesterCardProps {
  semester: Semester;
  onClick?: (semester: Semester) => void;
  showClassSubjects?: boolean;
  className?: string;
}

/**
 * Card component for displaying Semester information
 */
const SemesterCard: React.FC<SemesterCardProps> = ({
  semester,
  onClick,
  showClassSubjects = true,
  className = ''
}) => {
  const handleClick = () => {
    if (onClick) {
      onClick(semester);
    }
  };

  const getSemesterBadgeVariant = (orderNumber: 1 | 2): 'info' | 'success' => {
    return orderNumber === 1 ? 'info' : 'success';
  };

  return (
    <div
      onClick={handleClick}
      className={`
                border border-gray-200 rounded-lg p-4 bg-white
                transition-all duration-200
                ${onClick ? 'cursor-pointer hover:shadow-md hover:border-indigo-400' : ''}
                ${className}
            `}
    >
      <div className="flex items-start justify-between mb-3">
        <div>
          <h4 className="text-md font-semibold text-gray-900">
            {semester.name}
          </h4>
          <p className="text-sm text-gray-500 mt-1">
            {formatDate(semester.start_date)} - {formatDate(semester.end_date)}
          </p>
        </div>
        <Badge
          label={`S${semester.order_number}`}
          type={getSemesterBadgeVariant(semester.order_number)}
          size="sm"
        />
      </div>

      {showClassSubjects && (
        <div className="mt-3 pt-3 border-t border-gray-100">
          <div className="flex items-center justify-between">
            <span className="text-xs text-gray-500 uppercase">Class Subjects</span>
            <span className="text-sm font-semibold text-gray-900">
              {semester.class_subjects?.length || 0}
            </span>
          </div>
        </div>
      )}
    </div>
  );
};

export { SemesterCard };
