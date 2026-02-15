import { type ReactNode, useState, useRef, useEffect } from 'react';
import { EllipsisVerticalIcon } from '@heroicons/react/24/outline';

type ActionColor = 'primary' | 'secondary' | 'danger' | 'success' | 'warning';

interface Action {
  label: string;
  onClick: () => void;
  icon?: React.ComponentType<{ className?: string }>;
  color?: ActionColor;
  disabled?: boolean;
  hidden?: boolean;
}

interface ActionGroupProps {
  /**
   * Primary actions displayed as buttons (max recommended: 2-3)
   */
  actions?: Action[];
  /**
   * Additional actions shown in dropdown menu
   */
  dropdownActions?: (Action | 'divider')[];
  /**
   * Size of the buttons
   */
  size?: 'sm' | 'md';
  /**
   * Custom dropdown trigger button content
   */
  dropdownTrigger?: ReactNode;
  /**
   * Dropdown trigger label for accessibility
   */
  dropdownLabel?: string;
  className?: string;
}

const colorClasses: Record<ActionColor, { button: string; dropdown: string }> = {
  primary: {
    button: 'bg-indigo-600 hover:bg-indigo-700 text-white focus:ring-indigo-500',
    dropdown: 'text-indigo-600 hover:bg-indigo-50',
  },
  secondary: {
    button: 'bg-gray-100 hover:bg-gray-200 text-gray-700 focus:ring-gray-500',
    dropdown: 'text-gray-700 hover:bg-gray-50',
  },
  danger: {
    button: 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500',
    dropdown: 'text-red-600 hover:bg-red-50',
  },
  success: {
    button: 'bg-green-600 hover:bg-green-700 text-white focus:ring-green-500',
    dropdown: 'text-green-600 hover:bg-green-50',
  },
  warning: {
    button: 'bg-yellow-500 hover:bg-yellow-600 text-white focus:ring-yellow-500',
    dropdown: 'text-yellow-600 hover:bg-yellow-50',
  },
};

const sizeClasses = {
  sm: {
    button: 'px-3 py-1.5 text-sm',
    icon: 'w-4 h-4',
    dropdown: 'text-sm',
  },
  md: {
    button: 'px-4 py-2 text-sm',
    icon: 'w-5 h-5',
    dropdown: 'text-sm',
  },
};

/**
 * ActionGroup component for grouping related actions together.
 * Similar to FilamentPHP's ActionGroup.
 *
 * @example
 * <ActionGroup
 *   actions={[
 *     { label: 'Edit', onClick: handleEdit, color: 'primary' },
 *     { label: 'Save', onClick: handleSave, color: 'success' },
 *   ]}
 *   dropdownActions={[
 *     { label: 'Duplicate', onClick: handleDuplicate, icon: DocumentDuplicateIcon },
 *     'divider',
 *     { label: 'Delete', onClick: handleDelete, color: 'danger', icon: TrashIcon },
 *   ]}
 * />
 */
function ActionGroup({
  actions = [],
  dropdownActions = [],
  size = 'sm',
  dropdownTrigger,
  dropdownLabel = 'More actions',
  className = '',
}: ActionGroupProps) {
  const [isOpen, setIsOpen] = useState(false);
  const dropdownRef = useRef<HTMLDivElement>(null);
  const triggerRef = useRef<HTMLButtonElement>(null);

  const visibleActions = actions.filter((action) => !action.hidden);
  const visibleDropdownActions = dropdownActions.filter(
    (action) => action === 'divider' || !action.hidden
  );

  useEffect(() => {
    function handleClickOutside(event: MouseEvent) {
      if (
        dropdownRef.current &&
        !dropdownRef.current.contains(event.target as Node) &&
        triggerRef.current &&
        !triggerRef.current.contains(event.target as Node)
      ) {
        setIsOpen(false);
      }
    }

    function handleEscape(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        setIsOpen(false);
        triggerRef.current?.focus();
      }
    }

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
      document.addEventListener('keydown', handleEscape);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('keydown', handleEscape);
    };
  }, [isOpen]);

  const renderActionButton = (action: Action, index: number) => {
    const color = action.color || 'secondary';
    const Icon = action.icon;

    return (
      <button
        key={index}
        type="button"
        onClick={action.onClick}
        disabled={action.disabled}
        className={`
                    inline-flex items-center justify-center font-medium rounded-md
                    focus:outline-none focus:ring-2 focus:ring-offset-2
                    transition-colors duration-200
                    disabled:opacity-50 disabled:cursor-not-allowed
                    ${colorClasses[color].button}
                    ${sizeClasses[size].button}
                `}
      >
        {Icon && <Icon className={`${sizeClasses[size].icon} ${action.label ? 'mr-1.5' : ''}`} />}
        {action.label}
      </button>
    );
  };

  const renderDropdownItem = (action: Action | 'divider', index: number) => {
    if (action === 'divider') {
      return <div key={`divider-${index}`} className="border-t border-gray-100 my-1" />;
    }

    const color = action.color || 'secondary';
    const Icon = action.icon;

    return (
      <button
        key={index}
        type="button"
        onClick={() => {
          action.onClick();
          setIsOpen(false);
        }}
        disabled={action.disabled}
        className={`
                    w-full flex items-center px-4 py-2
                    ${sizeClasses[size].dropdown}
                    ${colorClasses[color].dropdown}
                    disabled:opacity-50 disabled:cursor-not-allowed
                    transition-colors duration-150
                `}
      >
        {Icon && <Icon className={`${sizeClasses[size].icon} mr-2 shrink-0`} />}
        <span className="truncate">{action.label}</span>
      </button>
    );
  };

  const hasDropdownActions = visibleDropdownActions.length > 0;

  if (visibleActions.length === 0 && !hasDropdownActions) {
    return null;
  }

  return (
    <div className={`flex items-center gap-2 ${className}`}>
      {visibleActions.map((action, index) => renderActionButton(action, index))}

      {hasDropdownActions && (
        <div className="relative">
          <button
            ref={triggerRef}
            type="button"
            onClick={() => setIsOpen(!isOpen)}
            aria-expanded={isOpen}
            aria-haspopup="true"
            aria-label={dropdownLabel}
            className={`
                            inline-flex items-center justify-center rounded-md
                            bg-gray-100 hover:bg-gray-200 text-gray-600
                            focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500
                            transition-colors duration-200
                            ${sizeClasses[size].button}
                        `}
          >
            {dropdownTrigger || (
              <EllipsisVerticalIcon className={sizeClasses[size].icon} />
            )}
          </button>

          {isOpen && (
            <div
              ref={dropdownRef}
              className="
                                absolute right-0 mt-2 w-48 z-50
                                bg-white rounded-md shadow-lg
                                ring-1 ring-black ring-opacity-5
                                py-1
                                origin-top-right
                                animate-in fade-in-0 zoom-in-95
                            "
              role="menu"
              aria-orientation="vertical"
            >
              {visibleDropdownActions.map((action, index) =>
                renderDropdownItem(action, index)
              )}
            </div>
          )}
        </div>
      )}
    </div>
  );
}

export default ActionGroup;
export type { ActionGroupProps, Action as ActionItem };
