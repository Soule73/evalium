import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { Enrollment, User, PageProps } from '@/types';
import { Button, DataTable, EmptyState, Input, Section } from '@/Components';
import { trans } from '@/utils';
import { UserGroupIcon } from '@heroicons/react/24/outline';

interface StudentEnrollmentClassmatesProps extends PageProps {
  enrollment: Enrollment;
  classmates: User[];
}

export default function Classmates({ enrollment, classmates }: StudentEnrollmentClassmatesProps) {
  const [searchTerm, setSearchTerm] = useState('');

  const translations = useMemo(
    () => ({
      title: trans('student_enrollment_pages.classmates.title'),
      subtitle: trans('student_enrollment_pages.classmates.subtitle', {
        class: enrollment.class?.name || '-',
      }),
      backToEnrollment: trans('student_enrollment_pages.classmates.back_to_enrollment'),
      searchPlaceholder: trans('student_enrollment_pages.classmates.search_placeholder'),
      studentName: trans('student_enrollment_pages.classmates.student_name'),
      email: trans('student_enrollment_pages.classmates.email'),
      enrolledOn: trans('student_enrollment_pages.classmates.enrolled_on'),
      emptyTitle: trans('student_enrollment_pages.classmates.empty_title'),
      emptySubtitle: trans('student_enrollment_pages.classmates.empty_subtitle'),
      classmatesCount: trans('student_enrollment_pages.classmates.classmates_count', {
        count: classmates.length,
      }),
    }),
    [enrollment.class?.name, classmates.length]
  );

  const filteredClassmates = useMemo(() => {
    if (!searchTerm) return classmates;
    const lowerSearch = searchTerm.toLowerCase();
    return classmates.filter(
      (student) =>
        student.name.toLowerCase().includes(lowerSearch) ||
        (student.email && student.email.toLowerCase().includes(lowerSearch))
    );
  }, [classmates, searchTerm]);

  const columns = [
    {
      key: 'name',
      label: translations.studentName,
      render: (student: User) => (
        <div className="flex items-center">
          <div className="shrink-0 h-10 w-10">
            <div className="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
              <span className="text-gray-600 font-medium text-sm">
                {student.name
                  .split(' ')
                  .map((n) => n[0])
                  .join('')
                  .toUpperCase()
                  .slice(0, 2)}
              </span>
            </div>
          </div>
          <div className="ml-4">
            <div className="text-sm font-medium text-gray-900">{student.name}</div>
          </div>
        </div>
      ),
    },
    {
      key: 'email',
      label: translations.email,
      render: (student: User) => <span className="text-gray-700">{student.email || '-'}</span>,
    },
  ];

  return (
    <AuthenticatedLayout title={translations.title}>
      <Section
        title={translations.classmatesCount}
        subtitle={translations.subtitle}
        actions={
          <div className="flex items-center space-x-4">
            <Input
              value={searchTerm}
              onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearchTerm(e.target.value)}
              placeholder={translations.searchPlaceholder}
            />
            <Button
              variant="outline"
              size="sm"
              onClick={() => router.visit(route('student.mcd.enrollment.show'))}
            >
              {translations.backToEnrollment}
            </Button>
          </div>
        }
      >
        {filteredClassmates.length === 0 ? (
          <EmptyState
            icon={<UserGroupIcon className="w-12 h-12" />}
            title={translations.emptyTitle}
            subtitle={searchTerm ? 'No classmates match your search.' : translations.emptySubtitle}
          />
        ) : (
          <DataTable
            data={{ data: filteredClassmates, current_page: 1, last_page: 1, per_page: filteredClassmates.length, total: filteredClassmates.length, first_page_url: '', from: 1, last_page_url: '', next_page_url: null, path: '', prev_page_url: null, to: filteredClassmates.length, links: [] }}
            config={{
              columns,
              emptyState: {
                title: translations.emptyTitle,
                subtitle: translations.emptySubtitle
              }
            }}
          />
        )}
      </Section>
    </AuthenticatedLayout>
  );
}
