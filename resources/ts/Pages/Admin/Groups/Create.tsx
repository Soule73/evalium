import { router } from '@inertiajs/react';
import { useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/Button';
import Section from '@/Components/Section';
import Input from '@/Components/form/Input';
import LevelSelect from '@/Components/form/LevelSelect';
import { route } from 'ziggy-js';

interface Props {
    levels: Record<number, string>;
    available_students: Array<{ id: number, name: string, email: string }>;
}

interface GroupFormData {
    level_id: string;
    start_date: string;
    end_date: string;
    max_students: string;
    academic_year: string;
    is_active: boolean;
}

export default function CreateGroup({ levels }: Props) {
    const { data, setData, post, processing, errors } = useForm<GroupFormData>({
        level_id: '',
        start_date: '',
        end_date: '',
        max_students: '30',
        academic_year: new Date().getFullYear() + '-' + (new Date().getFullYear() + 1),
        is_active: true,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.groups.store'), {
            onSuccess: () => {
                router.visit(route('admin.groups.index'));
            }
        });
    };

    const handleCancel = () => {
        router.visit(route('admin.groups.index'));
    };



    return (
        <AuthenticatedLayout title="Créer un groupe">
            <Section
                title="Créer un nouveau groupe"
                subtitle="Ajoutez un nouveau groupe de classe avec ses informations."
            >
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <div className="space-y-6">
                            <LevelSelect
                                value={data.level_id}
                                onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setData('level_id', e.target.value)}
                                levels={levels}
                                error={errors.level_id}
                                required
                            />

                            <Input
                                label="Année académique"
                                type="text"
                                value={data.academic_year}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('academic_year', e.target.value)}
                                error={errors.academic_year}
                                placeholder="Ex: 2024-2025"
                                required
                            />
                        </div>

                        <div className="space-y-6">
                            <Input
                                label="Date de début"
                                type="date"
                                value={data.start_date}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('start_date', e.target.value)}
                                error={errors.start_date}
                                required
                            />

                            <Input
                                label="Date de fin"
                                type="date"
                                value={data.end_date}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('end_date', e.target.value)}
                                error={errors.end_date}
                                required
                            />

                            <Input
                                label="Nombre maximum d'étudiants"
                                type="number"
                                value={data.max_students}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('max_students', e.target.value)}
                                error={errors.max_students}
                                min="1"
                                max="100"
                                required
                            />

                            <div className="flex items-center">
                                <input
                                    id="is_active"
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('is_active', e.target.checked)}
                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <label htmlFor="is_active" className="ml-2 block text-sm text-gray-900">
                                    Groupe actif
                                </label>
                            </div>
                        </div>
                    </div>

                    <div className="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                        <Button
                            type="button"
                            onClick={handleCancel}
                            color="secondary"
                            variant="outline"
                            disabled={processing}
                        >
                            Annuler
                        </Button>
                        <Button
                            type="submit"
                            color="primary"
                            variant="solid"
                            disabled={processing}
                            loading={processing}
                        >
                            Créer le groupe
                        </Button>
                    </div>
                </form>
            </Section>
        </AuthenticatedLayout>
    );
}