import AuthenticatedLayout from "@/Components/layout/AuthenticatedLayout";
import { User } from "@/types";
import { formatDate, getRoleLabel } from "@/utils";
import EditUser from "../Admin/Users/Edit";
import { useMemo, useState } from "react";
import { Button, LanguageSelector, Section, TextEntry, UserAvatar } from "@/Components";
import { route } from "ziggy-js";
import { trans } from '@/utils';
import { usePage } from "@inertiajs/react";

interface Props {
    user: User;
}

export default function Profile({ user }: Props) {
    const [isShowUpdateModal, setIsShowUpdateModal] = useState(false);
    const { locale } = usePage<{ locale: string }>().props;

    const handleEdit = () => {
        setIsShowUpdateModal(true);
    };

    const userRole = useMemo(() => (user.roles?.length ?? 0) > 0 ? user.roles![0].name : null, [user.roles]);

    return (
        <AuthenticatedLayout title={trans('auth_pages.profile.page_title')}>
            {user && (
                <EditUser
                    title={trans('auth_pages.profile.edit_modal_title')}
                    description={trans('auth_pages.profile.edit_modal_subtitle')}
                    route={route('profile.update', { user: user.id })}
                    isOpen={isShowUpdateModal}
                    onClose={() => {
                        setIsShowUpdateModal(false);
                    }}
                    user={user}
                    userRole={userRole || null}
                />
            )}
            <Section title={trans('auth_pages.profile.title')} subtitle={trans('auth_pages.profile.subtitle')}

                actions={
                    <Button
                        onClick={handleEdit}
                        size='sm'
                        className="w-max"
                        color="primary">
                        {trans('auth_pages.profile.edit_button')}
                    </Button>
                }
            >
                <div className="flex items-center space-x-4">
                    <UserAvatar avatar={user.avatar} name={user.name} size="large" />
                    <TextEntry label={user.name} value={user.email} />
                </div>
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <TextEntry label={trans('auth_pages.profile.role_label')} value={getRoleLabel(userRole ?? '')} />
                    <TextEntry label={trans('auth_pages.profile.account_active')} value={user.active ? trans('auth_pages.profile.yes') : trans('auth_pages.profile.no')} />
                    <TextEntry label={trans('auth_pages.profile.email_verified')} value={user.email_verified_at ? trans('auth_pages.profile.yes') : trans('auth_pages.profile.no')} />
                    <TextEntry label={trans('auth_pages.profile.created_at')} value={formatDate(user.created_at, "long")} />
                    <TextEntry label={trans('auth_pages.profile.updated_at')} value={formatDate(user.updated_at, "long")} />
                </div>
            </Section>

            <Section
                title={trans('auth_pages.profile.language_section_title')}
                subtitle={trans('auth_pages.profile.language_section_subtitle')}
            >
                <LanguageSelector currentLocale={locale} />
            </Section>
        </AuthenticatedLayout>
    );
}
