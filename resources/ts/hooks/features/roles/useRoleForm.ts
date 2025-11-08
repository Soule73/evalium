import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Permission, RoleFormData } from '@/types/role';

interface UseRoleFormOptions {
    initialData?: RoleFormData;
    allPermissions: Permission[];
    onSuccessRoute: string;
}

export function useRoleForm({ initialData, allPermissions, onSuccessRoute }: UseRoleFormOptions) {
    const [formData, setFormData] = useState<RoleFormData>(
        initialData || {
            name: '',
            permissions: [],
        }
    );

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleFieldChange = (field: keyof RoleFormData, value: string | number[]) => {
        setFormData(prev => ({ ...prev, [field]: value }));
    };

    const handlePermissionToggle = (permissionId: number) => {
        setFormData(prev => ({
            ...prev,
            permissions: prev.permissions.includes(permissionId)
                ? prev.permissions.filter(id => id !== permissionId)
                : [...prev.permissions, permissionId]
        }));
    };

    const selectAll = () => {
        setFormData(prev => ({
            ...prev,
            permissions: allPermissions.map(p => p.id)
        }));
    };

    const deselectAll = () => {
        setFormData(prev => ({
            ...prev,
            permissions: []
        }));
    };

    const handleCancel = () => {
        router.visit(onSuccessRoute);
    };

    const handleError = (errors: Record<string, string>) => {
        setErrors(errors);
        setIsSubmitting(false);
    };

    const handleSuccess = () => {
        setIsSubmitting(false);
    };

    return {
        formData,
        errors,
        isSubmitting,
        setIsSubmitting,
        handleFieldChange,
        handlePermissionToggle,
        selectAll,
        deselectAll,
        handleCancel,
        handleError,
        handleSuccess,
    };
}
