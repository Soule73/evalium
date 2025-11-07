import { trans } from '@/utils';
import { Badge } from '../ui';
import { BadgeType } from '../ui/Badge';

interface RoleBadgeProps {
    role: 'super_admin' | 'admin' | 'teacher' | 'student' | undefined;
    className?: string;
}

export const RoleBadge = ({ role }: RoleBadgeProps) => {
    const badgeConfig: {
        [key: string]: {
            type: BadgeType;
            label: string;
        };
    } = {
        admin: {
            type: "info",
            label: trans('users.admin')
        },
        teacher: {
            type: "warning",
            label: trans('users.teacher')
        },
        student: {
            type: "success",
            label: trans('users.student')
        },
        super_admin: {
            type: "info",
            label: trans('users.super_admin')
        }
    };

    const config = role && badgeConfig[role]
        ? badgeConfig[role]
        : { type: "gray" as BadgeType, label: trans('users.unknown') };

    return (
        <Badge label={config.label}
            type={config.type}
        />
    );
};