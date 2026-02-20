import { ArchiveBoxIcon } from '@heroicons/react/24/outline';
import { Link, router, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { Select } from '@/Components/ui';
import { useTranslations } from '@/hooks/shared/useTranslations';
import type { User } from '@/types/models/shared/user';
import type { AcademicYear } from '@/types/models/academicYear';
import type { PageProps as InertiaPageProps } from '@inertiajs/core';

interface AcademicYearSelectorProps {
    user: User;
}

interface AcademicYearPageProps extends InertiaPageProps {
    academic_year: {
        selected: AcademicYear | null;
        recent: AcademicYear[];
    };
}

export function AcademicYearSelector({ user }: AcademicYearSelectorProps) {
    const { academic_year } = usePage<AcademicYearPageProps>().props;
    const { t } = useTranslations();

    const selectedYear = academic_year.selected;
    const availableYears = academic_year.recent;

    const handleYearChange = (value: string | number) => {
        const yearId = Number(value);
        if (yearId === selectedYear?.id) return;

        router.post(
            '/academic-years/set-current',
            {
                academic_year_id: yearId,
            },
            {
                preserveState: false,
                preserveScroll: false,
            },
        );
    };

    const canViewArchives = user.permissions?.includes('view academic years');

    if (!selectedYear || !availableYears.length) {
        return null;
    }

    const options = availableYears.map((year) => ({
        value: year.id,
        label: year.is_current
            ? `${year.name} (${t('admin_pages.academic_years.current')})`
            : year.name,
    }));

    return (
        <div className="flex items-center gap-2">
            <Select
                size="sm"
                id="academic-year-selector"
                options={options}
                value={selectedYear.id}
                onChange={handleYearChange}
                searchable={false}
                className="w-56"
            />
            {canViewArchives && (
                <Link
                    href={route('admin.academic-years.archives')}
                    className="inline-flex items-center gap-1 rounded-md px-2 py-2 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                    title={t('admin_pages.academic_years.view_archives')}
                >
                    <ArchiveBoxIcon className="h-5 w-5" />
                </Link>
            )}
        </div>
    );
}
