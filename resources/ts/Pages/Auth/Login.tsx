import { GuestLayout } from '@/Components/layout/GuestLayout';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button, Logo } from '@/Components';
import { Checkbox, Input } from '@evalium/ui';
import { useLogin } from '@/hooks/shared';

interface LoginProps {
    canResetPassword?: boolean;
    status?: string;
}

const Login = ({ canResetPassword = true, status }: LoginProps) => {
    const { data, setData, errors, processing, handleSubmit } = useLogin();
    const { t } = useTranslations();

    return (
        <GuestLayout title={t('auth_pages.login.title')}>
            <div className="w-full max-w-md mx-auto">
                <div className="lg:hidden flex justify-center mb-8">
                    <Logo showName width={56} height={56} variant="vertical" />
                </div>

                <div className="mb-8">
                    <h1 className="text-2xl font-bold text-gray-900">
                        {t('auth_pages.login.title')}
                    </h1>
                    <p className="text-gray-500 mt-2 text-sm">{t('auth_pages.login.subtitle')}</p>
                </div>

                {status && (
                    <div
                        className="mb-6 font-medium text-sm text-green-600 bg-green-50 border border-green-200 rounded-lg p-3"
                        data-e2e="status-message"
                    >
                        {status}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-5">
                    <Input
                        label={t('auth_pages.login.email_label')}
                        id="email"
                        type="email"
                        name="email"
                        className="mt-1 block w-full"
                        value={data.email}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setData('email', e.target.value)
                        }
                        placeholder={t('auth_pages.login.email_placeholder')}
                        required
                        autoComplete="username"
                        autoFocus
                        error={errors.email}
                    />

                    <Input
                        label={t('auth_pages.login.password_label')}
                        id="password"
                        name="password"
                        type="password"
                        className="mt-1 block w-full"
                        value={data.password}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            setData('password', e.target.value)
                        }
                        placeholder={t('auth_pages.login.password_placeholder')}
                        required
                        autoComplete="current-password"
                        error={errors.password}
                    />

                    <div className="flex items-center justify-between">
                        <Checkbox
                            id="remember"
                            label={t('auth_pages.login.remember_me')}
                            checked={data.remember}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                                setData('remember', e.target.checked)
                            }
                            data-e2e="remember-checkbox"
                        />
                        {canResetPassword && (
                            <span
                                className="text-sm text-indigo-600 hover:text-indigo-500 cursor-pointer"
                                data-e2e="forgot-password-link"
                            >
                                {t('auth_pages.login.forgot_password')}
                            </span>
                        )}
                    </div>

                    <Button
                        type="submit"
                        color="primary"
                        className="w-full"
                        disabled={processing}
                        loading={processing}
                        data-e2e="login-submit"
                    >
                        {processing
                            ? t('auth_pages.login.submitting')
                            : t('auth_pages.login.submit_button')}
                    </Button>
                </form>
            </div>
        </GuestLayout>
    );
};

export default Login;
