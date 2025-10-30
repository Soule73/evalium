import { useForm } from '@inertiajs/react';
import { Button } from '@/Components/Button';
import Input from '@/Components/form/Input';
import Select from '@/Components/Select';
import { route } from 'ziggy-js';
import Modal from '@/Components/Modal';

interface Group {
    id: number;
    display_name: string;
    academic_year: string;
    is_active: boolean;
}

interface Props {
    roles: string[];
    groups: Group[];
    isOpen: boolean;
    onClose: () => void;
}

export default function CreateUser({ roles, groups, isOpen, onClose }: Props) {
    const { data, setData, post, processing, errors } = useForm<{
        name: string;
        email: string;
        role: string;
        group_id: number | null;
    }>({
        name: '',
        email: '',
        role: 'student',
        group_id: null,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('admin.users.store'), {
            onSuccess: () => {
                onClose();
            },
            onError: (e) => {
                console.log('Erreur lors de la création de l\'utilisateur :', e);
            }
        });
    };

    const handleCancel = () => {
        onClose();
        setData({
            name: '',
            email: '',
            role: 'student',
            group_id: null,
        });
    };

    const getRoleLabel = (roleName: string) => {
        switch (roleName) {
            case 'admin':
                return 'Administrateur';
            case 'teacher':
                return 'Enseignant';
            case 'student':
                return 'Étudiant';
            case 'super_admin':
                return 'Super Administrateur';
            default:
                return roleName;
        }
    };

    // Filtrer les groupes actifs uniquement
    const activeGroups = groups.filter(group => group.is_active);

    return (
        <Modal isOpen={isOpen} size='2xl' onClose={onClose} isCloseableInside={false}>
            <div className="p-6">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">
                        Créer un nouvel utilisateur
                    </h1>
                    <p className="text-gray-600 mt-1">
                        Remplissez les informations pour créer un nouveau compte utilisateur
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <Input
                        label="Nom complet"
                        type="text"
                        value={data.name}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('name', e.target.value)}
                        placeholder="Entrez le nom complet"
                        required
                        error={errors.name}
                    />

                    <Input
                        label="Adresse email"
                        type="email"
                        value={data.email}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('email', e.target.value)}
                        placeholder="Entrez l'adresse email"
                        required
                        error={errors.email}
                    />

                    <Select
                        label="Rôle"
                        options={roles.map(role => ({
                            value: role,
                            label: getRoleLabel(role)
                        }))}
                        value={data.role}
                        onChange={(value) => setData('role', String(value))}
                        error={errors.role}
                        searchable={false}
                        placeholder="Sélectionner un rôle"
                    />

                    {data.role === 'student' && (
                        <Select
                            label="Groupe"
                            options={activeGroups.map(group => ({
                                value: group.id,
                                label: `${group.display_name} (${group.academic_year})`
                            }))}
                            value={data.group_id ?? ''}
                            onChange={(value) => setData('group_id', Number(value))}
                            error={errors.group_id}
                            searchable={true}
                            placeholder="Sélectionner un groupe"
                        />
                    )}

                    <div className="bg-blue-50 border-l-4 border-blue-400 p-4">
                        <div className="flex">
                            <div className="shrink-0">
                                <svg className="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="ml-3">
                                <p className="text-sm text-blue-700">
                                    Un mot de passe sera généré automatiquement et envoyé par email à l'utilisateur.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                        <Button
                            type="button"
                            color="secondary"
                            variant='outline'
                            onClick={handleCancel}
                        >
                            Annuler
                        </Button>
                        <Button
                            type="submit"
                            color="primary"
                            loading={processing}
                            disabled={processing}
                        >
                            {processing ? (
                                'Création...'
                            ) : (
                                "Créer l'utilisateur"
                            )}
                        </Button>
                    </div>
                </form>
            </div>
        </Modal>
    );
}