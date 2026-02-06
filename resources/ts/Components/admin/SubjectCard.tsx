import { Subject } from '@/types';
import { Badge } from '@/Components/ui';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { BookOpenIcon } from '@heroicons/react/24/outline';

interface SubjectCardProps {
  subject: Subject;
  onClick?: (subject: Subject) => void;
  className?: string;
}

/**
 * Card component for displaying Subject information
 */
const SubjectCard: React.FC<SubjectCardProps> = ({
  subject,
  onClick,
  className = ''
}) => {
  const handleClick = () => {
    if (onClick) {
      onClick(subject);
    } else {
      router.get(route('admin.subjects.show', subject.id));
    }
  };

  return (
    <div
      onClick={handleClick}
      className={`
                border border-gray-200 rounded-lg p-4 bg-white
                cursor-pointer transition-all duration-200
                hover:shadow-md hover:border-blue-400
                ${className}
            `}
    >
      <div className="flex items-start justify-between mb-3">
        <div className="flex items-center space-x-2">
          <BookOpenIcon className="w-5 h-5 text-gray-400" />
          <h3 className="text-lg font-semibold text-gray-900">
            {subject.name}
          </h3>
        </div>
        <Badge label={subject.code} type="info" size="sm" />
      </div>

      {subject.description && (
        <p className="text-sm text-gray-600 mb-3 line-clamp-2">
          {subject.description}
        </p>
      )}

      <div className="flex items-center justify-between pt-3 border-t border-gray-100">
        <div className="text-xs text-gray-500">
          {subject.level?.name || '-'}
        </div>
        <div className="text-xs text-gray-500">
          {subject.class_subjects_count || 0} classes
        </div>
      </div>
    </div>
  );
};

export { SubjectCard };
