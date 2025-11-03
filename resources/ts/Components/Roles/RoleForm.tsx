import { FormEvent } from 'react';
import { Button } from '@/Components/Button';
import Input from '@/Components/form/Input';
import Badge from '@/Components/Badge';
import PermissionSelector from './PermissionSelector';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import { GroupedPermissions, RoleFormData } from '@/types/role';

interface Props {
    formData: RoleFormData;
    errors: Record<string, string>;
    isSubmitting: boolean;
    groupedPermissions: GroupedPermissions;
    onSubmit: (e: FormEvent) => void;
    onCancel: () => void;
    onFieldChange: (field: keyof RoleFormData, value: any) => void;
    onPermissionToggle: (permissionId: number) => void;
    onSelectAll: () => void;
    onDeselectAll: () => void;
    isSystemRole?: boolean;
    onSync?: () => void;
    submitButtonText?: string;
    submittingText?: string;
}

export default function RoleForm({
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
    submitButtonText = 'Créer le rôle',
    submittingText = 'Création...',
}: Props) {
    return (
        <form onSubmit={onSubmit} className="space-y-6">
            {isSystemRole && (
                <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div className="flex items-center gap-2">
                        <Badge label="Rôle système" type="info" />
                        <span className="text-sm text-blue-800">
                            Ce rôle est un rôle système. Vous pouvez uniquement modifier ses permissions.
                        </span>
                    </div>
                </div>
            )}

            <Input
                label="Nom du rôle"
                type="text"
                value={formData.name}
                onChange={(e) => onFieldChange('name', e.target.value)}
                error={errors.name}
                required
                disabled={isSystemRole}
                placeholder="Ex: moderator, editor..."
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

            {!isSystemRole && (
                <div className="flex justify-end gap-3 pt-4 border-t">
                    <Button
                        type="button"
                        onClick={onCancel}
                        color="secondary"
                        variant='outline'
                        disabled={isSubmitting}
                    >
                        <ArrowLeftIcon className="w-4 h-4 mr-2" />
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        color="primary"
                        disabled={isSubmitting}
                    >
                        {isSubmitting ? submittingText : submitButtonText}
                    </Button>
                </div>
            )}
        </form>
    );
}
