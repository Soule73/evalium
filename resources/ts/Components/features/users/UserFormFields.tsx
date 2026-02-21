import { useMemo } from 'react';
import { Input } from '@evalium/ui';
import { Select } from '@/Components';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useFormatters } from '@/hooks/shared/useFormatters';

type UserFieldKey = 'name' | 'email' | 'role';

interface UserFormFieldsProps {
    data: Record<UserFieldKey, string>;
    errors: Partial<Record<UserFieldKey, string>>;
    onChange: (field: UserFieldKey, value: string) => void;
    roles?: string[];
    hideRoleSelect?: boolean;
}

/**
 * Shared user base form fields (name, email, optional role select).
 *
 * Used in both CreateUserModal and EditUserModal to avoid duplication.
 */
export default function UserFormFields({
    data,
    errors,
    onChange,
    roles,
    hideRoleSelect = false,
}: UserFormFieldsProps) {
    const { t } = useTranslations();
    const { getRoleLabel } = useFormatters();

    const translations = useMemo(
        () => ({
            nameLabel: t('admin_pages.users.name_label'),
            namePlaceholder: t('admin_pages.users.name_placeholder'),
            emailLabel: t('admin_pages.users.email_label'),
            emailPlaceholder: t('admin_pages.users.email_placeholder'),
            roleLabel: t('admin_pages.users.role'),
            selectRole: t('admin_pages.users.select_role'),
            searchPlaceholder: t('components.select.search_placeholder'),
            noOptionFound: t('components.select.no_option_found'),
        }),
        [t],
    );

    return (
        <>
            <Input
                label={translations.nameLabel}
                type="text"
                value={data.name}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                    onChange('name', e.target.value)
                }
                placeholder={translations.namePlaceholder}
                required
                error={errors.name}
            />

            <Input
                label={translations.emailLabel}
                type="email"
                value={data.email}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                    onChange('email', e.target.value)
                }
                placeholder={translations.emailPlaceholder}
                required
                error={errors.email}
            />

            {!hideRoleSelect && roles && roles.length > 0 && (
                <Select
                    label={translations.roleLabel}
                    noOptionFound={translations.noOptionFound}
                    searchPlaceholder={translations.searchPlaceholder}
                    options={roles.map((role) => ({
                        value: role,
                        label: getRoleLabel(role),
                    }))}
                    value={data.role}
                    onChange={(value) => onChange('role', String(value))}
                    error={errors.role}
                    searchable={false}
                    placeholder={translations.selectRole}
                />
            )}
        </>
    );
}
