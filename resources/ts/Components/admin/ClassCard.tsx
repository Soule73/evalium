import { type ClassModel } from '@/types';
import { Badge } from '@/Components/ui';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { AcademicCapIcon, UserGroupIcon } from '@heroicons/react/24/outline';

interface ClassCardProps {
  classItem: ClassModel;
  onClick?: (classItem: ClassModel) => void;
  className?: string;
}

/**
 * Card component for displaying Class information
 */
const ClassCard: React.FC<ClassCardProps> = ({
  classItem,
  onClick,
  className = ''
}) => {
  const handleClick = () => {
    if (onClick) {
      onClick(classItem);
    } else {
      router.get(route('admin.classes.show', classItem.id));
    }
  };

  const activeEnrollments = classItem.active_enrollments_count || 0;
  const maxStudents = classItem.max_students;
  const enrollmentPercentage = maxStudents > 0 ? (activeEnrollments / maxStudents) * 100 : 0;

  return (
    <div
      onClick={handleClick}
      className={`
                border border-gray-200 rounded-lg p-4 bg-white
                cursor-pointer transition-all duration-200
                hover:shadow-md hover:border-indigo-400
                ${className}
            `}
    >
      <div className="flex items-start justify-between mb-3">
        <div className="flex items-center space-x-2">
          <AcademicCapIcon className="w-5 h-5 text-gray-400" />
          <h3 className="text-lg font-semibold text-gray-900">
            {classItem.name}
          </h3>
        </div>
        {enrollmentPercentage >= 90 && (
          <Badge label="Full" type="warning" size="sm" />
        )}
      </div>

      <div className="space-y-2 text-sm text-gray-600 mb-3">
        <div className="flex items-center space-x-2">
          <span className="text-gray-500">Level:</span>
          <span className="font-medium">{classItem.level?.name || '-'}</span>
        </div>
        <div className="flex items-center space-x-2">
          <span className="text-gray-500">Year:</span>
          <span className="font-medium">{classItem.academic_year?.name || '-'}</span>
        </div>
      </div>

      <div className="flex items-center justify-between pt-3 border-t border-gray-100">
        <div className="flex items-center space-x-1 text-xs text-gray-500">
          <UserGroupIcon className="w-4 h-4" />
          <span>
            {activeEnrollments} / {maxStudents} students
          </span>
        </div>
        <div className="text-xs text-gray-500">
          {classItem.subjects_count || 0} subjects
        </div>
      </div>
    </div>
  );
};

export { ClassCard };
