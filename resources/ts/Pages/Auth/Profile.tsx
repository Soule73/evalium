import AuthenticatedLayout from "@/Components/layout/AuthenticatedLayout";
import { type User } from "@/types";
import { formatDate } from "@/utils";
import { useFormatters } from '@/hooks/shared/useFormatters';
import EditUser from "../Admin/Users/Edit";
import { Button, LanguageSelector, Section, TextEntry, UserAvatar } from "@/Components";
import { route } from "ziggy-js";
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useProfile } from "@/hooks/shared";

interface Props {
    user: User;
}

export default function Profile({ user }: Props) {

    const { isShowUpdateModal, setIsShowUpdateModal, locale, handleEdit, userRole } = useProfile({ user });
    const { t } = useTranslations();
    const { getRoleLabel } = useFormatters();


    return (
        <AuthenticatedLayout title={t('auth_pages.profile.page_title')}>
            {user && (
                <EditUser
                    title={t('auth_pages.profile.edit_modal_title')}
                    description={t('auth_pages.profile.edit_modal_subtitle')}
                    route={route('profile.update', { user: user.id })}
                    isOpen={isShowUpdateModal}
                    onClose={() => {
                        setIsShowUpdateModal(false);
                    }}
                    user={user}
                    userRole={userRole || null}
                />
            )}
            <Section title={t('auth_pages.profile.title')} subtitle={t('auth_pages.profile.subtitle')}

                actions={
                    <Button
                        onClick={handleEdit}
                        size='sm'
                        className="w-max"
                        color="primary">
                        {t('auth_pages.profile.edit_button')}
                    </Button>
                }
            >
                <div className="flex items-center space-x-4">
                    <UserAvatar
                        avatar={user.avatar}
                        name={user.name}
                        size="large"
                    />
                    <TextEntry
                        label={user.name}
                        value={user.email}
                    />
                </div>
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <TextEntry
                        label={t('auth_pages.profile.role_label')}
                        value={getRoleLabel(userRole ?? '')} />
                    <TextEntry
                        label={t('auth_pages.profile.account_active')}
                        value={user.active ? t('auth_pages.profile.yes') : t('auth_pages.profile.no')} />
                    <TextEntry
                        label={t('auth_pages.profile.email_verified')}
                        value={user.email_verified_at ? t('auth_pages.profile.yes') : t('auth_pages.profile.no')} />
                    <TextEntry
                        label={t('auth_pages.profile.created_at')}
                        value={formatDate(user.created_at, "long")} />
                    <TextEntry
                        label={t('auth_pages.profile.updated_at')}
                        value={formatDate(user.updated_at, "long")} />
                </div>
            </Section>

            <Section
                title={t('auth_pages.profile.language_section_title')}
                subtitle={t('auth_pages.profile.language_section_subtitle')}
            >
                <LanguageSelector currentLocale={locale} />
            </Section>
        </AuthenticatedLayout>
    );
}
