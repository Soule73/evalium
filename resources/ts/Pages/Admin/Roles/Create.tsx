import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Section from '@/Components/Section';
import { Button } from '@/Components/Button';
import Input from '@/Components/form/Input';
import Checkbox from '@/Components/form/Checkbox';
import { route } from 'ziggy-js';
import { breadcrumbs } from '@/utils/breadcrumbs';

interface Permission {
    id: number;
    name: string;
}

interface Props {
    permissions: Permission[];
}

export default function CreateRole({ permissions }: Props) {
    const [formData, setFormData] = useState({
        name: '',
        permissions: [] as number[],
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        router.post(route('admin.roles.store'), formData, {
            onError: (errors) => {
                setErrors(errors);
                setIsSubmitting(false);
            },
            onSuccess: () => {
                setIsSubmitting(false);
            },
        });
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
            permissions: permissions.map(p => p.id)
        }));
    };

    const deselectAll = () => {
        setFormData(prev => ({
            ...prev,
            permissions: []
        }));
    };

    return (
        <AuthenticatedLayout title="Créer un rôle"
            breadcrumb={breadcrumbs.adminRoleCreate()}
        >
            <Section
                title="Nouveau rôle"
                subtitle="Créer un nouveau rôle et assigner des permissions"

                actions={<div className="flex justify-end gap-3">
                    <Button
                        type="button"
                        onClick={handleCancel}
                        color="secondary"
                        variant='outline'
                        disabled={isSubmitting}
                    >
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        color="primary"
                        disabled={isSubmitting}
                    >
                        {isSubmitting ? 'Création...' : 'Créer le rôle'}
                    </Button>
                </div>
                }
            >
                <form onSubmit={handleSubmit} className="space-y-6">
                    <Input
                        label="Nom du rôle"
                        type="text"
                        value={formData.name}
                        onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                        error={errors.name}
                        required
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
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 p-4 border border-gray-300 rounded-lg">
                            {permissions.map((permission) => (
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
                </form>
            </Section>
        </AuthenticatedLayout>
    );
}
