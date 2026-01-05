export type BadgeType = 'success' | 'error' | 'warning' | 'info' | 'gray';
interface BadgeProps {
    label: string;
    type: BadgeType;
}


const Badge: React.FC<BadgeProps> = ({ label, type = "info" }) => {
    const typeStyles: Record<BadgeType, string> = {
        success: "text-green-600 bg-green-600/10 dark:text-[--color-dark-success] dark:bg-[--color-dark-success]/20",
        error: "text-red-600 bg-red-600/10 dark:text-[--color-dark-danger] dark:bg-[--color-dark-danger]/20",
        warning: "text-yellow-600 bg-yellow-600/10 dark:text-[--color-dark-warning] dark:bg-[--color-dark-warning]/20",
        info: "text-blue-600 bg-blue-600/10 dark:text-[--color-dark-primary] dark:bg-[--color-dark-primary]/20",
        gray: "text-gray-600 bg-gray-600/10 dark:text-[--color-dark-text-secondary] dark:bg-[--color-dark-text-secondary]/20",
    };

    return <div className={`text-xs w-max font-medium rounded-lg px-2 py-1 ${typeStyles[type]}`}>{label}</div>;
};

export default Badge;