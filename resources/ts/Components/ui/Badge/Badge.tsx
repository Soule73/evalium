export type BadgeType = 'success' | 'error' | 'warning' | 'info' | 'gray';
interface BadgeProps {
    label: string;
    type: BadgeType;
}


const Badge: React.FC<BadgeProps> = ({ label, type = "info" }) => {
    const typeStyles: Record<BadgeType, string> = {
        success: "text-green-600 bg-green-600/10",
        error: "text-red-600 bg-red-600/10",
        warning: "text-yellow-600 bg-yellow-600/10",
        info: "text-blue-600 bg-blue-600/10",
        gray: "text-gray-600 bg-gray-600/10",
    };

    return <div className={`text-xs w-max font-medium rounded-lg px-2 py-1 ${typeStyles[type]}`}>{label}</div>;
};

export default Badge;