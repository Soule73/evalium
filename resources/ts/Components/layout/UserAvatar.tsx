interface UserAvatarProps {
    avatar?: string;
    name: string;
    size?: 'sm' | 'md' | 'lg' | 'large';
    className?: string;
}

export const UserAvatar = ({ avatar, name, size = 'md', className = '' }: UserAvatarProps) => {
    const sizeClasses = {
        sm: 'w-6 h-6 text-xs',
        md: 'w-8 h-8 text-sm',
        lg: 'w-10 h-10 text-base',
        large: 'w-30 h-30 text-7xl',
    };

    const sizeClass = sizeClasses[size];

    if (avatar) {
        return (<img src={avatar} alt={name} className={`rounded-full object-cover ${sizeClass} ${className}`} />);
    }

    return (
        <div className={`bg-linear-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center ${sizeClass} ${className}`}>
            <span className="text-white font-medium">
                {name.charAt(0).toUpperCase()}
            </span>
        </div>
    );
};