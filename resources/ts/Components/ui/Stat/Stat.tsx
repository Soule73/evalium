import React from 'react';

type IconComponent = React.ComponentType<React.SVGProps<SVGSVGElement>>;

interface StatItemProps {
    icon?: IconComponent | React.ReactNode;
    title: string;
    value: React.ReactNode;
    description?: string;
    className?: string;
}

interface StatGroupProps extends React.HTMLAttributes<HTMLDivElement> {
    children: React.ReactNode;
    columns?: 1 | 2 | 3 | 4;
    className?: string;
}

/**
 * Individual stat item displaying an icon, title, and value
 */
function StatItem({ icon, title, value, description, className = '' }: StatItemProps) {
    const renderIcon = () => {
        if (!icon) return null;

        if (React.isValidElement(icon)) {
            return icon;
        }

        const IconComponent = icon as IconComponent;
        return <IconComponent className="w-5 h-5 text-gray-400 mt-1 shrink-0" />;
    };

    return (
        <div className={`flex items-start space-x-3 ${className}`}>
            {icon && renderIcon()}
            <div>
                <div className="text-sm font-medium text-gray-500">{title}</div>
                <div className="mt-1">{value}</div>
                {description && <div className="mt-1 text-xs text-gray-400">{description}</div>}
            </div>
        </div>
    );
}

/**
 * Container for grouping multiple StatItem components in a grid layout
 */
function StatGroup({ children, columns = 3, className = '', ...rest }: StatGroupProps) {
    const columnClasses: Record<number, string> = {
        1: 'grid-cols-1',
        2: 'grid-cols-1 md:grid-cols-2',
        3: 'grid-cols-1 md:grid-cols-3',
        4: 'grid-cols-1 md:grid-cols-2 lg:grid-cols-4',
    };

    return (
        <div className={`grid ${columnClasses[columns]} gap-6 ${className}`} {...rest}>
            {children}
        </div>
    );
}

/**
 * Stat component for displaying statistics with icon, title, and value
 *
 * @example
 * <Stat.Group columns={3}>
 *   <Stat.Item
 *     icon={BookOpenIcon}
 *     title="Code"
 *     value={<Badge label="SUB001" type="info" size="sm" />}
 *   />
 *   <Stat.Item
 *     icon={AcademicCapIcon}
 *     title="Level"
 *     value="Mathematics"
 *   />
 * </Stat.Group>
 */
const Stat = {
    Item: StatItem,
    Group: StatGroup,
};

export default Stat;
export { StatItem, StatGroup };
export type { StatItemProps, StatGroupProps };
