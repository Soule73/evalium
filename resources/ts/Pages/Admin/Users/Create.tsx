import { useForm } from '@inertiajs/react';
import { Button } from '@/Components/Button';
import Input from '@/Components/form/Input';
import Select from '@/Components/Select';
import { route } from 'ziggy-js';
import Modal from '@/Components/Modal';

interface Props {
    roles: string[];
    isOpen: boolean;
    onClose: () => void;
}

export default function CreateUser({ roles, isOpen, onClose }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role: 'student',
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
            password: '',
            password_confirmation: '',
            role: 'student',
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
            default:
                return roleName;
        }
    };

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
                    <Input
                        label="Mot de passe"
                        type="password"
                        value={data.password}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('password', e.target.value)}
                        placeholder="Entrez le mot de passe"
                        required
                        error={errors.password}
                        helperText="Le mot de passe doit contenir au moins 8 caractères"
                    />
                    <Input
                        label="Confirmer le mot de passe"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('password_confirmation', e.target.value)}
                        placeholder="Confirmez le mot de passe"
                        required
                        error={errors.password_confirmation}
                    />
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