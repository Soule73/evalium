import { useTranslations } from '@/hooks/shared/useTranslations';
import { Badge } from '../ui';
import { type BadgeType } from '../ui/Badge/Badge';

interface RoleBadgeProps {
    role: 'super_admin' | 'admin' | 'teacher' | 'student' | undefined;
    className?: string;
}

export const RoleBadge = ({ role }: RoleBadgeProps) => {
    const { t } = useTranslations();
    const badgeConfig: {
        [key: string]: {
            type: BadgeType;
            label: string;
        };
    } = {
        admin: {
            type: "info",
            label: t('users.admin')
        },
        teacher: {
            type: "warning",
            label: t('users.teacher')
        },
        student: {
            type: "success",
            label: t('users.student')
        },
        super_admin: {
            type: "info",
            label: t('users.super_admin')
        }
    };

    const config = role && badgeConfig[role]
        ? badgeConfig[role]
        : { type: "gray" as BadgeType, label: t('users.unknown') };

    return (
        <Badge label={config.label}
            type={config.type}
        />
    );
};