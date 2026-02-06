import { FormEvent } from 'react';
import { PermissionSelector } from './PermissionSelector';
import { GroupedPermissions, RoleFormData } from '@/types/role';
import { trans } from '@/utils';
import { Input, Section } from '@examena/ui';
import { Badge, Button } from '@examena/ui';

interface Props {
    title: string;
    subtitle: string;
    formData: RoleFormData;
    errors: Record<string, string>;
    isSubmitting: boolean;
    groupedPermissions: GroupedPermissions;
    onSubmit: (e: FormEvent) => void;
    onCancel: () => void;
    onFieldChange: (field: keyof RoleFormData, value: string | number[]) => void;
    onPermissionToggle: (permissionId: number) => void;
    onSelectAll: () => void;
    onDeselectAll: () => void;
    isSystemRole?: boolean;
    onSync?: () => void;
    submitButtonText?: string;
    submittingText?: string;
}

export function RoleForm({
    title,
    subtitle,
    formData,
    errors,
    isSubmitting,
    groupedPermissions,
    onSubmit,
    onCancel,
    onFieldChange,
    onPermissionToggle,
    onSelectAll,
    onDeselectAll,
    isSystemRole = false,
    onSync,
    submitButtonText = trans('components.role_form.create_button'),
    submittingText = trans('components.role_form.creating'),
}: Props) {
    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <Section title={title} subtitle={subtitle}
                actions={
                    !isSystemRole && (
                        <div className="flex justify-end gap-3">
                            <Button
                                type="button"
                                onClick={onCancel}
                                color="secondary"
                                variant='outline'
                                disabled={isSubmitting}
                                data-e2e="role-cancel-button"
                            >
                                {trans('components.role_form.cancel')}
                            </Button>
                            <Button
                                type="submit"
                                color="primary"
                                disabled={isSubmitting}
                                data-e2e="role-submit-button"
                            >
                                {isSubmitting ? submittingText : submitButtonText}
                            </Button>
                        </div>
                    )
                }
            >
                {isSystemRole && (
                    <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div className="flex items-center gap-2">
                            <Badge label={trans('components.role_form.system_role_badge')} type="info" />
                            <span className="text-sm text-blue-800">
                                {trans('components.role_form.system_role_notice')}
                            </span>
                        </div>
                    </div>
                )}

                <Input
                    label={trans('components.role_form.role_name_label')}
                    type="text"
                    value={formData.name}
                    onChange={(e) => onFieldChange('name', e.target.value)}
                    error={errors.name}
                    // required
                    disabled={isSystemRole}
                    placeholder={trans('components.role_form.role_name_placeholder')}
                    // data-e2e="role-name-input"
                    id='role-name-input'
                />

                <PermissionSelector
                    groupedPermissions={groupedPermissions}
                    selectedPermissions={formData.permissions}
                    onPermissionToggle={onPermissionToggle}
                    onSelectAll={onSelectAll}
                    onDeselectAll={onDeselectAll}
                    error={errors.permissions}
                    showSyncButton={isSystemRole}
                    onSync={onSync}
                    isSubmitting={isSubmitting}
                />


            </Section>
        </form>
    );
}
