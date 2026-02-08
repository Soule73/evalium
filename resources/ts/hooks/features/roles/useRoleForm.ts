import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import { Permission, RoleFormData } from '@/types/role';
import { route } from 'ziggy-js';
import { Role } from '@/types';

interface UseRoleFormOptions {
    role?: Role;
    initialData?: RoleFormData;
    allPermissions: Permission[];
    onSuccessRoute: string;
    nameValidationMessage?: string;
    persmissionsValidationMessage?: string;
}

export function useRoleForm({ role, initialData, allPermissions, onSuccessRoute, nameValidationMessage, persmissionsValidationMessage }: UseRoleFormOptions) {
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

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        const method = role ? 'put' : 'post';
        const url = role
            ? route('admin.roles.update', { role: role.id })
            : route('admin.roles.store');
        setIsSubmitting(true);

        //validate before submit
        if (!formData.name.trim()) {
            // handleError({ name: 'The name field is required.' });
            handleError({ name: nameValidationMessage || 'The name field is required.' });
            return;
        }

        if (formData.permissions.length === 0) {
            // handleError({ permissions: 'At least one permission must be selected.' });
            handleError({ permissions: persmissionsValidationMessage || 'At least one permission must be selected.' });
            return;
        }

        if (method === 'put') {
            router.put(url, formData as any, {
                onError: handleError,
                onSuccess: handleSuccess,
            });
        } else {
            router.post(url, formData as any, {
                onError: handleError,
                onSuccess: handleSuccess,
            });
        }
    };

    const handleSyncPermissions = () => {
        setIsSubmitting(true);

        router.post(
            route('admin.roles.sync-permissions', { role: role?.id }),
            { permissions: formData.permissions } as any,
            {
                onError: handleError,
                onSuccess: handleSuccess,
                preserveScroll: true,
            }
        );
    };


    return {
        handleSyncPermissions,
        handleSubmit,
        formData,
        errors,
        isSubmitting,
        handleFieldChange,
        handlePermissionToggle,
        selectAll,
        deselectAll,
        handleCancel,
    };
}
