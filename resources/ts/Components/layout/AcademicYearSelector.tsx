import { Fragment } from 'react';
import { Menu, Transition } from '@headlessui/react';
import { ChevronDownIcon, ArchiveBoxIcon } from '@heroicons/react/24/outline';
import { Link, router, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { AcademicYearBadge } from './AcademicYearBadge';
import { trans } from '@/utils';
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

  const selectedYear = academic_year.selected;
  const availableYears = academic_year.recent;

  const handleYearChange = (yearId: number) => {
    router.post('/academic-years/set-current', {
      academic_year_id: yearId,
    }, {
      preserveState: false,
      preserveScroll: false,
    });
  };

  const canViewArchives = user.permissions?.includes('view academic years');

  if (!selectedYear || !availableYears.length) {
    return null;
  }

  return (
    <Menu as="div" className="relative inline-block text-left">
      <Menu.Button
        className="inline-flex w-full items-center justify-center gap-x-1.5 rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 disabled:opacity-50"
      >
        <span className="truncate">{selectedYear.name}</span>
        {selectedYear.is_current && (
          <span className="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
            {trans('admin_pages.academic_years.current')}
          </span>
        )}
        <ChevronDownIcon className="-mr-1 h-5 w-5 text-gray-400" aria-hidden="true" />
      </Menu.Button>

      <Transition
        as={Fragment}
        enter="transition ease-out duration-100"
        enterFrom="transform opacity-0 scale-95"
        enterTo="transform opacity-100 scale-100"
        leave="transition ease-in duration-75"
        leaveFrom="transform opacity-100 scale-100"
        leaveTo="transform opacity-0 scale-95"
      >
        <Menu.Items className="absolute right-0 z-10 mt-2 w-72 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
          <div className="py-1">
            {availableYears.map((year) => (
              <Menu.Item key={year.id}>
                {({ active }) => (
                  <button
                    onClick={() => handleYearChange(year.id)}
                    disabled={year.id === selectedYear.id}
                    className={`${active ? 'bg-gray-100' : ''
                      } ${year.id === selectedYear.id
                        ? 'bg-gray-50'
                        : ''
                      } group flex w-full items-center px-4 py-2 text-sm text-gray-700 disabled:cursor-not-allowed disabled:opacity-50`}
                  >
                    <AcademicYearBadge
                      year={year}
                      isSelected={year.id === selectedYear.id}
                      size="sm"
                    />
                  </button>
                )}
              </Menu.Item>
            ))}

            {canViewArchives && (
              <>
                <div className="my-1 border-t border-gray-200" />
                <Menu.Item>
                  {({ active }) => (
                    <Link
                      href={route('admin.academic-years.archives')}
                      className={`${active ? 'bg-gray-100' : ''
                        } group flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700`}
                    >
                      <ArchiveBoxIcon className="h-5 w-5 text-gray-400" />
                      <span>{trans('admin_pages.academic_years.view_archives')}</span>
                    </Link>
                  )}
                </Menu.Item>
              </>
            )}
          </div>
        </Menu.Items>
      </Transition>
    </Menu>
  );
}
