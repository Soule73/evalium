import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Section from '@/Components/Section';
import { Button } from '@/Components/Button';
import Input from '@/Components/form/Input';
import Checkbox from '@/Components/form/Checkbox';
import { route } from 'ziggy-js';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import Badge from '@/Components/Badge';
import { breadcrumbs } from '@/utils/breadcrumbs';

interface Permission {
    id: number;
    name: string;
}

interface Role {
    id: number;
    name: string;
    permissions: Permission[];
}

interface Props {
    role: Role;
    allPermissions: Permission[];
}

export default function EditRole({ role, allPermissions }: Props) {
    const isSystemRole = ['super_admin', 'admin', 'teacher', 'student'].includes(role.name);

    const [formData, setFormData] = useState({
        name: role.name,
        permissions: role.permissions.map(p => p.id),
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        router.put(route('admin.roles.update', { role: role.id }), formData, {
            onError: (errors) => {
                setErrors(errors);
                setIsSubmitting(false);
            },
            onSuccess: () => {
                setIsSubmitting(false);
            },
        });
    };

    const handleSyncPermissions = () => {
        setIsSubmitting(true);

        router.post(route('admin.roles.sync-permissions', { role: role.id }),
            { permissions: formData.permissions },
            {
                onError: (errors) => {
                    setErrors(errors);
                    setIsSubmitting(false);
                },
                onSuccess: () => {
                    setIsSubmitting(false);
                },
                preserveScroll: true,
            }
        );
    };

    const handleCancel = () => {
        router.visit(route('admin.roles.index'));
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

    return (
        <AuthenticatedLayout title="Modifier un rôle"
            breadcrumb={breadcrumbs.adminRoleEdit(role.name)}
        >
            <Section
                title="Modifier le rôle"
                subtitle={`Modification du rôle : ${role.name}`}

                actions={
                    <Button
                        type="button"
                        onClick={handleCancel}
                        variant='outline'
                        color="secondary"
                    >
                        Retour
                    </Button>
                }
            >
                <form onSubmit={handleSubmit} className="space-y-6">
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
                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                        error={errors.name}
                        required
                        disabled={isSystemRole}
                        placeholder="Ex: moderator, editor..."
                    />

                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <label className="text-sm font-medium text-gray-700">
                                Permissions ({formData.permissions.length} sélectionnées)
                            </label>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    onClick={selectAll}
                                    size="sm"
                                    variant='outline'
                                    color="secondary"
                                >
                                    Tout sélectionner
                                </Button>
                                <Button
                                    type="button"
                                    onClick={deselectAll}
                                    size="sm"
                                    variant='outline'
                                    color="secondary"
                                >
                                    Tout désélectionner
                                </Button>
                                {isSystemRole && (
                                    <Button
                                        type="button"
                                        onClick={handleSyncPermissions}
                                        size="sm"
                                        color="primary"
                                        disabled={isSubmitting}
                                    >
                                        Synchroniser
                                    </Button>
                                )}
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-4 border border-gray-300 rounded-lg">
                            {allPermissions.map((permission) => (
                                <div
                                    key={permission.id}
                                    className="p-2 hover:bg-white rounded transition-colors"
                                >
                                    <Checkbox
                                        label={permission.name}
                                        checked={formData.permissions.includes(permission.id)}
                                        onChange={() => handlePermissionToggle(permission.id)}
                                    />
                                </div>
                            ))}
                        </div>
                        {errors.permissions && (
                            <p className="text-sm text-red-600">{errors.permissions}</p>
                        )}
                    </div>

                    {!isSystemRole && (
                        <div className="flex justify-end gap-3 pt-4 border-t">
                            <Button
                                type="button"
                                onClick={handleCancel}
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
                                {isSubmitting ? 'Enregistrement...' : 'Enregistrer les modifications'}
                            </Button>
                        </div>
                    )}
                </form>
            </Section>
        </AuthenticatedLayout>
    );
}
