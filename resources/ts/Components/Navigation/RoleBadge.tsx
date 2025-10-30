interface RoleBadgeProps {
    role: 'super_admin' | 'admin' | 'teacher' | 'student' | undefined;
    className?: string;
}

export const RoleBadge = ({ role, className = '' }: RoleBadgeProps) => {
    const badgeConfig = {
        admin: {
            className: 'bg-red-100 text-red-800',
            label: 'Admin'
        },
        teacher: {
            className: 'bg-green-100 text-green-800',
            label: 'Enseignant'
        },
        student: {
            className: 'bg-blue-100 text-blue-800',
            label: 'Étudiant'
        },
        super_admin: {
            className: 'bg-purple-100 text-purple-800',
            label: 'Super Admin'
        }
    };

    const config = role ? badgeConfig[role] : { className: 'bg-gray-100 text-gray-800', label: 'Inconnu' };

    return (
        <span className={`px-2 py-1 text-xs font-medium rounded-full ${config.className} ${className}`}>
            {config.label}
        </span>
    );
};