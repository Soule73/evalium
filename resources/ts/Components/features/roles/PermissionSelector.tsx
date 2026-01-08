import { Checkbox, Section } from '@examena/ui';
import { Button } from '@examena/ui';
import { GroupedPermissions } from '@/types/role';
import { trans } from '@/utils';

interface Props {
    groupedPermissions: GroupedPermissions;
    selectedPermissions: number[];
    onPermissionToggle: (permissionId: number) => void;
    onSelectAll: () => void;
    onDeselectAll: () => void;
    error?: string;
    showSyncButton?: boolean;
    onSync?: () => void;
    isSubmitting?: boolean;
}

export default function PermissionSelector({
    groupedPermissions,
    selectedPermissions,
    onPermissionToggle,
    onSelectAll,
    onDeselectAll,
    error,
    showSyncButton = false,
    onSync,
    isSubmitting = false,
}: Props) {
    return (
        <div className="space-y-3" data-e2e="permission-selector">
            <div className="flex items-center justify-between">
                <label className="text-sm font-medium text-gray-700">
                    {trans('components.permission_selector.label', { count: selectedPermissions.length })}
                </label>
                <div className="flex gap-2">
                    <Button
                        type="button"
                        onClick={onSelectAll}
                        size="sm"
                        variant='outline'
                        color="secondary"
                        data-e2e="permission-select-all"
                    >
                        {trans('components.permission_selector.select_all')}
                    </Button>
                    <Button
                        type="button"
                        onClick={onDeselectAll}
                        size="sm"
                        variant='outline'
                        color="secondary"
                        data-e2e="permission-deselect-all"
                    >
                        {trans('components.permission_selector.deselect_all')}
                    </Button>
                    {showSyncButton && onSync && (
                        <Button
                            type="button"
                            onClick={onSync}
                            size="sm"
                            color="primary"
                            disabled={isSubmitting}
                            data-e2e="permission-sync-button"
                        >
                            {trans('components.permission_selector.sync')}
                        </Button>
                    )}
                </div>
            </div>

            {error && (
                <p className="text-sm text-red-600" data-e2e="permission-selector-error">{error}</p>
            )}
            <div className="space-y-6">
                {Object.entries(groupedPermissions).map(([category, categoryPermissions]) => (
                    <Section title={category} key={category}
                        className="border border-gray-200 rounded-lg overflow-hidden"
                    >
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-4">
                            {categoryPermissions.map((permission) => (
                                <div
                                    key={permission.id}
                                    className="p-2 hover:bg-gray-50 rounded transition-colors"
                                >
                                    <Checkbox
                                        label={trans(`permissions.${permission.name}`)}
                                        checked={selectedPermissions.includes(permission.id)}
                                        onChange={() => onPermissionToggle(permission.id)}
                                        data-e2e={`permission-checkbox-${permission.id}`}
                                    />
                                </div>
                            ))}
                        </div>
                    </Section>
                ))}
            </div>

        </div>
    );
}
