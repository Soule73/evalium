import { useEffect, useMemo, useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { CheckIcon, ClipboardDocumentIcon, UserCircleIcon } from '@heroicons/react/24/outline';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Button, Checkbox, Modal } from '@/Components';
import UserFormFields from './UserFormFields';
import type { CreatedUserCredentials, PageProps } from '@/types';

interface CreateUserModalProps {
    roles?: string[];
    isOpen: boolean;
    onClose: () => void;
    /**
     * When provided, the role select is hidden and translations adapt to this role context.
     * The form will POST to the corresponding store route.
     */
    forcedRole?: string;
    storeRoute?: string;
    /**
     * When true, the "send credentials" checkbox is hidden.
     * Used in contexts where credentials are managed separately (e.g. enrollment flow).
     */
    hideSendCredentials?: boolean;
    /**
     * Called with the newly created user's credentials when creation succeeds in
     * a context where credentials are not shown on-screen (hideSendCredentials=true).
     */
    onStudentCreated?: (credentials: CreatedUserCredentials) => void;
}

type CopiedField = 'name' | 'email' | 'password' | null;

export default function CreateUserModal({
    roles = [],
    isOpen,
    onClose,
    forcedRole,
    storeRoute = 'admin.users.store',
    hideSendCredentials = false,
    onStudentCreated,
}: CreateUserModalProps) {
    const { t } = useTranslations();
    const { flash } = usePage<PageProps>().props;

    const [createdUser, setCreatedUser] = useState<CreatedUserCredentials | null>(null);
    const [copiedField, setCopiedField] = useState<CopiedField>(null);

    const { data, setData, post, processing, errors, reset } = useForm<{
        name: string;
        email: string;
        role: string;
        send_credentials: boolean;
    }>({
        name: '',
        email: '',
        role: forcedRole ?? 'student',
        send_credentials: false,
    });

    useEffect(() => {
        if (!flash.has_new_user || !isOpen) {
            return;
        }

        fetch(route('admin.users.pending-credentials'), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        })
            .then((r) => (r.ok ? r.json() : null))
            .then((credentials: CreatedUserCredentials | null) => {
                if (!credentials) {
                    return;
                }

                if (hideSendCredentials && onStudentCreated) {
                    onStudentCreated(credentials);
                    handleClose();
                    return;
                }

                setCreatedUser(credentials);
            })
            .catch(() => undefined);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [flash.has_new_user, isOpen]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route(storeRoute));
    };

    const handleClose = () => {
        setCreatedUser(null);
        setCopiedField(null);
        reset();
        onClose();
    };

    const copyToClipboard = (text: string, field: CopiedField) => {
        navigator.clipboard.writeText(text).then(() => {
            setCopiedField(field);
            setTimeout(() => setCopiedField(null), 2000);
        });
    };

    const isTeacherContext = forcedRole === 'teacher';

    const translations = useMemo(
        () => ({
            createTitle: isTeacherContext
                ? t('admin_pages.teachers.create_title')
                : t('admin_pages.users.create_title'),
            createSubtitle: isTeacherContext
                ? t('admin_pages.teachers.create_subtitle')
                : t('admin_pages.users.create_subtitle'),
            createButton: isTeacherContext
                ? t('admin_pages.teachers.create_button')
                : t('admin_pages.users.create_button'),
            sendCredentials: t('admin_pages.users.send_credentials'),
            credentialsTitle: t('admin_pages.users.credentials_title'),
            credentialsSubtitle: t('admin_pages.users.credentials_subtitle'),
            credentialsName: t('admin_pages.users.credentials_name'),
            credentialsEmail: t('admin_pages.users.credentials_email'),
            credentialsPassword: t('admin_pages.users.credentials_password'),
            copy: t('admin_pages.users.copy'),
            copied: t('admin_pages.users.copied'),
            closeCredentials: t('admin_pages.users.close_credentials'),
            cancel: t('admin_pages.common.cancel'),
            loading: t('admin_pages.common.loading'),
        }),
        [t, isTeacherContext],
    );

    if (createdUser) {
        return (
            <Modal isOpen={isOpen} size="2xl" onClose={handleClose} isCloseableInside={false}>
                <div className="p-6">
                    <div className="flex flex-col items-center text-center mb-6">
                        <div className="flex items-center justify-center w-16 h-16 rounded-full bg-green-100 mb-4">
                            <UserCircleIcon className="w-9 h-9 text-green-600" />
                        </div>
                        <h1 className="text-2xl font-bold text-gray-900">
                            {translations.credentialsTitle}
                        </h1>
                        <p className="text-gray-600 mt-1 max-w-sm">
                            {translations.credentialsSubtitle}
                        </p>
                    </div>

                    <div className="space-y-3">
                        <CredentialRow
                            label={translations.credentialsName}
                            value={createdUser.name}
                            isCopied={copiedField === 'name'}
                            copyLabel={translations.copy}
                            copiedLabel={translations.copied}
                            onCopy={() => copyToClipboard(createdUser.name, 'name')}
                        />
                        <CredentialRow
                            label={translations.credentialsEmail}
                            value={createdUser.email}
                            isCopied={copiedField === 'email'}
                            copyLabel={translations.copy}
                            copiedLabel={translations.copied}
                            onCopy={() => copyToClipboard(createdUser.email, 'email')}
                        />
                        <CredentialRow
                            label={translations.credentialsPassword}
                            value={createdUser.password}
                            isCopied={copiedField === 'password'}
                            copyLabel={translations.copy}
                            copiedLabel={translations.copied}
                            onCopy={() => copyToClipboard(createdUser.password, 'password')}
                            isPassword
                        />
                    </div>

                    <div className="flex justify-end pt-6 border-t border-gray-200 mt-6">
                        <Button type="button" color="primary" onClick={handleClose}>
                            {translations.closeCredentials}
                        </Button>
                    </div>
                </div>
            </Modal>
        );
    }

    return (
        <Modal isOpen={isOpen} size="2xl" onClose={handleClose} isCloseableInside={false}>
            <div className="p-6">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold text-gray-900">{translations.createTitle}</h1>
                    <p className="text-gray-600 mt-1">{translations.createSubtitle}</p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <UserFormFields
                        data={{ name: data.name, email: data.email, role: data.role }}
                        errors={errors}
                        onChange={(field, value) => setData(field, value)}
                        roles={roles}
                        hideRoleSelect={!!forcedRole}
                    />

                    {!hideSendCredentials && (
                        <Checkbox
                            label={translations.sendCredentials}
                            checked={data.send_credentials}
                            onChange={(e) => setData('send_credentials', e.target.checked)}
                        />
                    )}

                    <div className="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                        <Button
                            type="button"
                            color="secondary"
                            variant="outline"
                            onClick={handleClose}
                        >
                            {translations.cancel}
                        </Button>
                        <Button
                            type="submit"
                            color="primary"
                            loading={processing}
                            disabled={processing}
                        >
                            {processing ? translations.loading : translations.createButton}
                        </Button>
                    </div>
                </form>
            </div>
        </Modal>
    );
}

interface CredentialRowProps {
    label: string;
    value: string;
    isCopied: boolean;
    copyLabel: string;
    copiedLabel: string;
    onCopy: () => void;
    isPassword?: boolean;
}

/**
 * Displays a single credential field with a copy-to-clipboard button.
 */
function CredentialRow({
    label,
    value,
    isCopied,
    copyLabel,
    copiedLabel,
    onCopy,
    isPassword = false,
}: CredentialRowProps) {
    return (
        <div className="flex items-center justify-between gap-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
            <div className="min-w-0">
                <p className="text-xs font-medium text-gray-500 uppercase tracking-wide mb-0.5">
                    {label}
                </p>
                <p
                    className={`text-sm font-mono text-gray-900 break-all ${isPassword ? 'tracking-wider' : ''}`}
                >
                    {value}
                </p>
            </div>
            <button
                type="button"
                onClick={onCopy}
                className="shrink-0 flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-1"
                style={{
                    backgroundColor: isCopied ? '#dcfce7' : '#e0e7ff',
                    color: isCopied ? '#16a34a' : '#4338ca',
                }}
            >
                {isCopied ? (
                    <CheckIcon className="w-4 h-4" />
                ) : (
                    <ClipboardDocumentIcon className="w-4 h-4" />
                )}
                {isCopied ? copiedLabel : copyLabel}
            </button>
        </div>
    );
}
