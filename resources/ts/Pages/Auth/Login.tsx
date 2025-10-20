import { useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import { Button } from '@/Components/Button';
import Input, { Checkbox } from '@/Components/form/Input';
import { Logo } from '@/Components/Navigation';
import { route } from 'ziggy-js';

interface LoginProps {
    canResetPassword?: boolean;
    status?: string;
}

const Login = ({ canResetPassword = true, status }: LoginProps) => {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('login.attempt'));
    };

    return (
        <GuestLayout title="Connexion">
            <div className="min-h-screen flex flex-col sm:justify-center items-center ">
                <div className="w-full max-w-lg mx-auto bg-white p-8 border border-gray-300 rounded-lg ">
                    <div className="flex justify-center mb-6">
                        <Logo />
                    </div>
                    <div className="text-center mb-8">

                        <h1 className="text-3xl font-bold text-gray-900">
                            Connexion
                        </h1>
                        <p className="text-gray-600 mt-2">
                            Connectez-vous à votre compte
                        </p>
                    </div>

                    {status && (
                        <div className="mb-4 font-medium text-sm text-green-600 bg-green-50 border border-green-200 rounded-lg p-3">
                            {status}
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <Input
                            label="Adresse email"
                            id="email"
                            type="email"
                            name='email'
                            className="mt-1 block w-full"
                            value={data.email}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('email', e.target.value)}
                            placeholder="Entrez votre email"
                            required
                            autoComplete="username"
                            autoFocus
                            error={errors.email}
                        />

                        <Input
                            label="Mot de passe"
                            id="password"
                            name='password'
                            type="password"
                            className="mt-1 block w-full"
                            value={data.password}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('password', e.target.value)}
                            placeholder="Entrez votre mot de passe"
                            required
                            autoComplete="current-password"
                            error={errors.password}
                        />

                        <Checkbox
                            id="remember"
                            label="Se souvenir de moi"
                            checked={data.remember}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('remember', e.target.checked)}
                        />


                        <div>
                            <Button
                                type="submit"
                                color="primary"
                                className="w-full"
                                disabled={processing}
                                loading={processing}

                            >
                                {processing ? 'Connexion...' : 'Se connecter'}
                            </Button>
                        </div>

                        <div className="flex items-center justify-between">
                            {canResetPassword && (
                                <span
                                    className="text-sm text-gray-500 "
                                >
                                    Mot de passe oublié ? Contactez l'administrateur.
                                </span>
                            )}

                        </div>
                    </form>
                </div>
            </div>
        </GuestLayout>
    );
};

export default Login;