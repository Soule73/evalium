import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Section from '@/Components/Section';
import { Button } from '@/Components/Button';
import Input from '@/Components/form/Input';
import Toggle from '@/Components/form/Toggle';
import { route } from 'ziggy-js';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';

interface Level {
    id: number;
    name: string;
    code: string;
    description: string | null;
    order: number;
    is_active: boolean;
}

interface Props {
    level: Level;
}

export default function EditLevel({ level }: Props) {
    const [formData, setFormData] = useState({
        name: level.name,
        code: level.code,
        description: level.description || '',
        order: level.order,
        is_active: level.is_active,
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        router.put(route('levels.update', { level: level.id }), formData, {
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
        router.visit(route('levels.index'));
    };

    return (
        <AuthenticatedLayout title="Modifier un niveau">
            <Section
                title="Modifier le niveau"
                subtitle={`Modification du niveau : ${level.name}`}
            >
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Input
                            label="Nom du niveau"
                            type="text"
                            value={formData.name}
                            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                            error={errors.name}
                            required
                            placeholder="Ex: Licence 1"
                        />

                        <Input
                            label="Code"
                            type="text"
                            value={formData.code}
                            onChange={(e) => setFormData({ ...formData, code: e.target.value })}
                            error={errors.code}
                            required
                            placeholder="Ex: L1"
                        />
                    </div>

                    <div className="flex flex-col gap-2">
                        <label className="text-sm font-medium text-gray-700">
                            Description
                        </label>
                        <textarea
                            value={formData.description}
                            onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                            placeholder="Description du niveau (optionnel)"
                            rows={3}
                            className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                        {errors.description && (
                            <p className="mt-1 text-sm text-red-600">{errors.description}</p>
                        )}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <Input
                            label="Ordre d'affichage"
                            type="number"
                            value={formData.order}
                            onChange={(e) => setFormData({ ...formData, order: parseInt(e.target.value) || 0 })}
                            error={errors.order}
                            required
                            min={0}
                        />

                        <div className="flex flex-col gap-2">
                            <label className="text-sm font-medium text-gray-700">
                                Statut
                            </label>
                            <Toggle
                                checked={formData.is_active}
                                onChange={() => setFormData({ ...formData, is_active: !formData.is_active })}
                                activeLabel="Actif"
                                inactiveLabel="Inactif"
                                showLabel={true}
                            />
                        </div>
                    </div>

                    <div className="flex justify-end gap-3 pt-4 border-t">
                        <Button
                            type="button"
                            onClick={handleCancel}
                            color="secondary"
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
                </form>
            </Section>
        </AuthenticatedLayout>
    );
}
