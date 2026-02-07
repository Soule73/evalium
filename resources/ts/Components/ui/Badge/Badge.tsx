export type BadgeType = 'success' | 'error' | 'warning' | 'info' | 'gray';
export type BadgeSize = 'sm' | 'md' | 'lg';
interface BadgeProps {
    label: string;
    type: BadgeType;
    size?: BadgeSize;
}


const Badge: React.FC<BadgeProps> = ({ label, type = "info", size = "md" }) => {
    const typeStyles: Record<BadgeType, string> = {
        success: "text-green-600 bg-green-600/10",
        error: "text-red-600 bg-red-600/10",
        warning: "text-yellow-600 bg-yellow-600/10",
        info: "text-blue-600 bg-blue-600/10",
        gray: "text-gray-600 bg-gray-600/10",
    };

    const sizeStyles: Record<BadgeSize, string> = {
        sm: "text-xs px-1.5 py-0.5",
        md: "text-sm px-3 py-1.5",
        lg: "text-base px-4 py-2",
    };

    return <div className={`w-max font-medium rounded-lg ${typeStyles[type]} ${sizeStyles[size]}`}>{label}</div>;
};

export default Badge;