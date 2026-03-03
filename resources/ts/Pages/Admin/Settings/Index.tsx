import { useState, useMemo, useRef, type ChangeEvent, type FormEvent } from 'react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Input, Section, Select, Toggle } from '@/Components';
import { router, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { TrashIcon, PhotoIcon } from '@heroicons/react/24/outline';
import { type PageProps } from '@evalium/utils/types';

interface GeneralSettings {
    school_name: string;
    logo_path: string | null;
    logo_url: string | null;
    default_locale: string;
}

interface BulletinSettings {
    show_ranking: boolean;
    show_class_average: boolean;
    show_min_max: boolean;
}

interface Props extends PageProps {
    general: GeneralSettings;
    bulletin: BulletinSettings;
}

export default function SettingsIndex({ general, bulletin }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();
    const { errors } = usePage<PageProps>().props;

    const [generalForm, setGeneralForm] = useState({
        school_name: general.school_name,
        default_locale: general.default_locale,
    });
    const [generalProcessing, setGeneralProcessing] = useState(false);

    const [bulletinForm, setBulletinForm] = useState({
        show_ranking: bulletin.show_ranking,
        show_class_average: bulletin.show_class_average,
        show_min_max: bulletin.show_min_max,
    });
    const [bulletinProcessing, setBulletinProcessing] = useState(false);

    const [logoPreview, setLogoPreview] = useState<string | null>(null);
    const [logoProcessing, setLogoProcessing] = useState(false);
    const [deleteLogoProcessing, setDeleteLogoProcessing] = useState(false);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const translations = useMemo(
        () => ({
            title: t('admin_pages.settings.title'),
            subtitle: t('admin_pages.settings.subtitle'),
            generalTitle: t('admin_pages.settings.general_title'),
            generalSubtitle: t('admin_pages.settings.general_subtitle'),
            bulletinTitle: t('admin_pages.settings.bulletin_title'),
            bulletinSubtitle: t('admin_pages.settings.bulletin_subtitle'),
            schoolName: t('admin_pages.settings.school_name'),
            defaultLocale: t('admin_pages.settings.default_locale'),
            logo: t('admin_pages.settings.logo'),
            logoHelp: t('admin_pages.settings.logo_help'),
            uploadLogo: t('admin_pages.settings.upload_logo'),
            deleteLogo: t('admin_pages.settings.delete_logo'),
            logoAlt: t('admin_pages.settings.logo'),
            changeLogo: t('admin_pages.settings.change_logo'),
            showRanking: t('admin_pages.settings.show_ranking'),
            showRankingDesc: t('admin_pages.settings.show_ranking_desc'),
            showClassAverage: t('admin_pages.settings.show_class_average'),
            showClassAverageDesc: t('admin_pages.settings.show_class_average_desc'),
            showMinMax: t('admin_pages.settings.show_min_max'),
            showMinMaxDesc: t('admin_pages.settings.show_min_max_desc'),
            save: t('commons/ui.save'),
        }),
        [t],
    );

    const localeOptions = useMemo(
        () => [
            { value: 'fr', label: t('admin_pages.settings.locale_fr') },
            { value: 'en', label: t('admin_pages.settings.locale_en') },
        ],
        [t],
    );

    const handleGeneralSubmit = (e: FormEvent) => {
        e.preventDefault();
        router.put(route('admin.settings.update-general'), generalForm, {
            preserveScroll: true,
            onStart: () => setGeneralProcessing(true),
            onFinish: () => setGeneralProcessing(false),
        });
    };

    const handleBulletinSubmit = (e: FormEvent) => {
        e.preventDefault();
        router.put(route('admin.settings.update-bulletin'), bulletinForm, {
            preserveScroll: true,
            onStart: () => setBulletinProcessing(true),
            onFinish: () => setBulletinProcessing(false),
        });
    };

    const handleLogoChange = (e: ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = () => setLogoPreview(reader.result as string);
        reader.readAsDataURL(file);
    };

    const handleLogoUpload = () => {
        const file = fileInputRef.current?.files?.[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('logo', file);

        router.post(route('admin.settings.upload-logo'), formData, {
            preserveScroll: true,
            onStart: () => setLogoProcessing(true),
            onFinish: () => {
                setLogoProcessing(false);
                setLogoPreview(null);
                if (fileInputRef.current) fileInputRef.current.value = '';
            },
        });
    };

    const handleLogoDelete = () => {
        router.delete(route('admin.settings.delete-logo'), {
            preserveScroll: true,
            onStart: () => setDeleteLogoProcessing(true),
            onFinish: () => setDeleteLogoProcessing(false),
        });
    };

    const currentLogoUrl = logoPreview || general.logo_url;

    return (
        <AuthenticatedLayout title={translations.title} breadcrumb={breadcrumbs.admin.settings()}>
            <div className="space-y-6">
                <Section
                    variant="flat"
                    title={translations.generalTitle}
                    subtitle={translations.generalSubtitle}
                >
                    <form onSubmit={handleGeneralSubmit} className="space-y-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <Input
                                label={translations.schoolName}
                                value={generalForm.school_name}
                                onChange={(e) =>
                                    setGeneralForm((prev) => ({
                                        ...prev,
                                        school_name: e.target.value,
                                    }))
                                }
                                error={errors.school_name}
                                required
                            />

                            <Select
                                label={translations.defaultLocale}
                                options={localeOptions}
                                value={generalForm.default_locale}
                                onChange={(value) =>
                                    setGeneralForm((prev) => ({
                                        ...prev,
                                        default_locale: String(value),
                                    }))
                                }
                                error={errors.default_locale}
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                {translations.logo}
                            </label>
                            <div className="flex items-start gap-6">
                                <div className="shrink-0">
                                    {currentLogoUrl ? (
                                        <img
                                            src={currentLogoUrl}
                                            alt={translations.logoAlt}
                                            className="h-20 w-20 object-contain rounded-lg border border-gray-200 bg-white p-1"
                                        />
                                    ) : (
                                        <div className="h-20 w-20 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center bg-gray-50">
                                            <PhotoIcon className="h-8 w-8 text-gray-400" />
                                        </div>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <div className="flex items-center gap-3">
                                        <input
                                            ref={fileInputRef}
                                            type="file"
                                            accept="image/png,image/jpeg,image/jpg,image/svg+xml"
                                            onChange={handleLogoChange}
                                            className="text-sm text-gray-500 file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 file:cursor-pointer"
                                        />
                                    </div>

                                    {logoPreview && (
                                        <Button
                                            onClick={handleLogoUpload}
                                            size="sm"
                                            disabled={logoProcessing}
                                        >
                                            {logoProcessing
                                                ? translations.uploadLogo + '...'
                                                : translations.uploadLogo}
                                        </Button>
                                    )}

                                    {general.logo_path && !logoPreview && (
                                        <Button
                                            onClick={handleLogoDelete}
                                            size="sm"
                                            variant="ghost"
                                            color="danger"
                                            disabled={deleteLogoProcessing}
                                        >
                                            <TrashIcon className="w-4 h-4 mr-1" />
                                            {translations.deleteLogo}
                                        </Button>
                                    )}

                                    <p className="text-xs text-gray-500">{translations.logoHelp}</p>
                                    {errors.logo && (
                                        <p className="text-sm text-red-600" role="alert">
                                            {errors.logo}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <Button type="submit" disabled={generalProcessing} size="sm">
                                {translations.save}
                            </Button>
                        </div>
                    </form>
                </Section>

                <Section
                    variant="flat"
                    title={translations.bulletinTitle}
                    subtitle={translations.bulletinSubtitle}
                >
                    <form onSubmit={handleBulletinSubmit} className="space-y-6">
                        <div className="space-y-4">
                            <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <p className="text-sm font-medium text-gray-900">
                                        {translations.showRanking}
                                    </p>
                                    <p className="text-xs text-gray-500 mt-0.5">
                                        {translations.showRankingDesc}
                                    </p>
                                </div>
                                <Toggle
                                    checked={bulletinForm.show_ranking}
                                    onChange={(checked) =>
                                        setBulletinForm((prev) => ({
                                            ...prev,
                                            show_ranking: checked,
                                        }))
                                    }
                                />
                            </div>

                            <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <p className="text-sm font-medium text-gray-900">
                                        {translations.showClassAverage}
                                    </p>
                                    <p className="text-xs text-gray-500 mt-0.5">
                                        {translations.showClassAverageDesc}
                                    </p>
                                </div>
                                <Toggle
                                    checked={bulletinForm.show_class_average}
                                    onChange={(checked) =>
                                        setBulletinForm((prev) => ({
                                            ...prev,
                                            show_class_average: checked,
                                        }))
                                    }
                                />
                            </div>

                            <div className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                <div>
                                    <p className="text-sm font-medium text-gray-900">
                                        {translations.showMinMax}
                                    </p>
                                    <p className="text-xs text-gray-500 mt-0.5">
                                        {translations.showMinMaxDesc}
                                    </p>
                                </div>
                                <Toggle
                                    checked={bulletinForm.show_min_max}
                                    onChange={(checked) =>
                                        setBulletinForm((prev) => ({
                                            ...prev,
                                            show_min_max: checked,
                                        }))
                                    }
                                />
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <Button type="submit" disabled={bulletinProcessing} size="sm">
                                {translations.save}
                            </Button>
                        </div>
                    </form>
                </Section>
            </div>
        </AuthenticatedLayout>
    );
}
