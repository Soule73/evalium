import { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Section from '@/Components/Section';
import { Button } from '@/Components/Button';
import Input from '@/Components/form/Input';
import Toggle from '@/Components/form/Toggle';
import { route } from 'ziggy-js';
import { Textarea } from '@/Components';
import { breadcrumbs } from '@/utils/breadcrumbs';

export default function CreateLevel() {
    const [formData, setFormData] = useState({
        name: '',
        code: '',
        description: '',
        order: 0,
        is_active: true,
    });

    const [errors, setErrors] = useState<Record<string, string>>({});
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        router.post(route('levels.store'), formData, {
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
        <AuthenticatedLayout breadcrumb={breadcrumbs.levelCreate()}>
            <Section
                title="Nouveau niveau"
                subtitle="Créer un nouveau niveau d'enseignement"

                actions={
                    <div className="flex justify-end gap-3">
                        <Button
                            type="button"
                            onClick={handleCancel}
                            color="secondary"
                            variant="outline"
                            size="sm"
                            disabled={isSubmitting}
                        >
                            Annuler
                        </Button>
                        <Button
                            type="submit"
                            color="primary"
                            disabled={isSubmitting}
                            size="sm"
                        >
                            {isSubmitting ? 'Création...' : 'Créer le niveau'}
                        </Button>
                    </div>
                }
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
                        <Textarea
                            label="Description"
                            value={formData.description}
                            onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                            error={errors.description}
                            placeholder="Description du niveau (optionnel)"
                            rows={3}
                        />
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
                </form>
            </Section>
        </AuthenticatedLayout>
    );
}
