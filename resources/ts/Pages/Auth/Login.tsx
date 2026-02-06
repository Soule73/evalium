import { GuestLayout } from '@/Components/layout/GuestLayout';
import { trans } from '@/utils';
import { Button, Logo } from '@/Components';
import { Checkbox, Input } from '@examena/ui';
import { useLogin } from '@/hooks/shared';

interface LoginProps {
    canResetPassword?: boolean;
    status?: string;
}

const Login = ({ canResetPassword = true, status }: LoginProps) => {
    const { data, setData, errors, processing, handleSubmit } = useLogin();

    return (
        <GuestLayout title={trans('auth_pages.login.title')}>
            <div className="min-h-screen flex flex-col sm:justify-center items-center ">
                <div className="w-full max-w-lg mx-auto bg-white p-8 border border-gray-300 rounded-lg ">
                    <div className="flex justify-center mb-6">
                        <Logo />
                    </div>
                    <div className="text-center mb-8">

                        <h1 className="text-3xl font-bold text-gray-900">
                            {trans('auth_pages.login.title')}
                        </h1>
                        <p className="text-gray-600 mt-2">
                            {trans('auth_pages.login.subtitle')}
                        </p>
                    </div>

                    {status && (
                        <div className="mb-4 font-medium text-sm text-green-600 bg-green-50 border border-green-200 rounded-lg p-3" data-e2e="status-message">
                            {status}
                        </div>
                    )}

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <Input
                            label={trans('auth_pages.login.email_label')}
                            id="email"
                            type="email"
                            name='email'
                            className="mt-1 block w-full"
                            value={data.email}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('email', e.target.value)}
                            placeholder={trans('auth_pages.login.email_placeholder')}
                            required
                            autoComplete="username"
                            autoFocus
                            error={errors.email}
                        // data-e2e="email-input"
                        />

                        <Input
                            label={trans('auth_pages.login.password_label')}
                            id="password"
                            name='password'
                            type="password"
                            className="mt-1 block w-full"
                            value={data.password}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('password', e.target.value)}
                            placeholder={trans('auth_pages.login.password_placeholder')}
                            required
                            autoComplete="current-password"
                            error={errors.password}
                        // data-e2e="password-input"
                        />

                        <Checkbox
                            id="remember"
                            label={trans('auth_pages.login.remember_me')}
                            checked={data.remember}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('remember', e.target.checked)}
                            data-e2e="remember-checkbox"
                        />


                        <div>
                            <Button
                                type="submit"
                                color="primary"
                                className="w-full"
                                disabled={processing}
                                loading={processing}
                                data-e2e="login-submit"
                            >
                                {processing ? trans('auth_pages.login.submitting') : trans('auth_pages.login.submit_button')}
                            </Button>
                        </div>

                        <div className="flex items-center justify-between">
                            {canResetPassword && (
                                <span
                                    className="text-sm text-gray-500"
                                    data-e2e="forgot-password-link"
                                >
                                    {trans('auth_pages.login.forgot_password')}
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