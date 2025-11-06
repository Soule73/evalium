import { Button } from '@/Components/Button';
import Checkbox from '@/Components/form/Checkbox';
import { GroupedPermissions } from '@/types/role';
import { trans } from '@/utils/translations';

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
        <div className="space-y-3">
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
                    >
                        {trans('components.permission_selector.select_all')}
                    </Button>
                    <Button
                        type="button"
                        onClick={onDeselectAll}
                        size="sm"
                        variant='outline'
                        color="secondary"
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
                        >
                            {trans('components.permission_selector.sync')}
                        </Button>
                    )}
                </div>
            </div>

            <div className="space-y-6">
                {Object.entries(groupedPermissions).map(([category, categoryPermissions]) => (
                    <div key={category} className="border border-gray-300 rounded-lg overflow-hidden">
                        <div className="bg-gray-100 px-4 py-2 border-b border-gray-300">
                            <h3 className="text-sm font-semibold text-gray-800">{category}</h3>
                        </div>
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
                                    />
                                </div>
                            ))}
                        </div>
                    </div>
                ))}
            </div>
            {error && (
                <p className="text-sm text-red-600">{error}</p>
            )}
        </div>
    );
}
