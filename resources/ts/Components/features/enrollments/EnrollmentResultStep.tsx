import { useCallback, useState } from 'react';
import { Link } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { Button, Section } from '@evalium/ui';
import { Badge } from '@evalium/ui';
import { useTranslations } from '@/hooks';
import { useEnrollmentWizard, type BulkEnrolledStudent } from '@/contexts/EnrollmentWizardContext';
import { DataTable } from '@/Components/shared/datatable/DataTable';
import { type ColumnConfig } from '@/types/datatable';
import {
    CheckCircleIcon,
    ExclamationCircleIcon,
    EyeIcon,
    EyeSlashIcon,
} from '@heroicons/react/24/outline';

interface PasswordCellProps {
    password?: string;
}

function PasswordCell({ password }: PasswordCellProps) {
    const [visible, setVisible] = useState(false);

    if (!password) {
        return <span className="text-gray-400">—</span>;
    }

    return (
        <div className="flex items-center gap-2">
            <span className="font-mono text-sm text-gray-900">
                {visible ? password : '••••••••'}
            </span>
            <Button
                size="sm"
                variant="ghost"
                type="button"
                onClick={() => setVisible((v) => !v)}
                className="text-gray-400 hover:text-gray-600"
                aria-label={visible ? 'Hide password' : 'Show password'}
            >
                {visible ? <EyeSlashIcon className="h-4 w-4" /> : <EyeIcon className="h-4 w-4" />}
            </Button>
        </div>
    );
}

type EnrolledRow = BulkEnrolledStudent & { id: number };

/**
 * Final step of the enrollment wizard: displays the result of the bulk enrollment request.
 */
export function EnrollmentResultStep() {
    const { t } = useTranslations();
    const { state, actions } = useEnrollmentWizard();

    const result = state.bulkResult;

    const handleEnrollMore = useCallback(() => {
        actions.reset();
    }, [actions]);

    if (!result) {
        return null;
    }

    const enrolledCount = result.enrolled.length;
    const failedCount = result.failed.length;
    const hasPasswords = result.enrolled.some((e) => e.password);

    const enrolledData: EnrolledRow[] = result.enrolled.map((e) => ({
        ...e,
        id: e.enrollment_id,
    }));

    const baseColumns: ColumnConfig<EnrolledRow>[] = [
        {
            key: 'student_name',
            label: t('admin_pages.users.name'),
            render: (item) => (
                <div className="flex items-center gap-2">
                    <span className="text-sm font-medium text-gray-900">{item.student_name}</span>
                    {item.password && (
                        <span className="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">
                            {t('admin_pages.enrollments.new_account')}
                        </span>
                    )}
                </div>
            ),
        },
        {
            key: 'student_email',
            label: t('admin_pages.users.email'),
            render: (item) => <span className="text-sm text-gray-500">{item.student_email}</span>,
        },
        {
            key: 'status',
            label: t('admin_pages.enrollments.status'),
            render: (item) => (
                <Badge
                    label={t(`admin_pages.enrollments.status_${item.status}`)}
                    type={item.status === 'active' ? 'success' : 'info'}
                    size="sm"
                />
            ),
        },
    ];

    const enrolledColumns: ColumnConfig<EnrolledRow>[] = hasPasswords
        ? [
              ...baseColumns,
              {
                  key: 'password',
                  label: t('admin_pages.users.password'),
                  render: (item) => <PasswordCell password={item.password} />,
              },
          ]
        : baseColumns;

    return (
        <div className="space-y-6">
            <Section
                title={result.class_name}
                actions={
                    <div className="flex items-center gap-3">
                        <Button
                            size="sm"
                            type="button"
                            color="primary"
                            variant="solid"
                            onClick={handleEnrollMore}
                        >
                            {t('admin_pages.enrollments.enroll_more')}
                        </Button>
                        <Link
                            href={route('admin.enrollments.index')}
                            className="text-sm font-medium text-indigo-600 hover:text-indigo-500"
                        >
                            {t('admin_pages.enrollments.view_all_enrollments')}
                        </Link>
                    </div>
                }
            >
                <div className="flex items-center gap-3">
                    {enrolledCount > 0 && (
                        <div className="flex items-center gap-2 text-green-700">
                            <CheckCircleIcon className="h-5 w-5" />
                            <span className="text-sm font-medium">
                                {t('admin_pages.enrollments.result_enrolled', {
                                    count: enrolledCount,
                                    class: result.class_name,
                                })}
                            </span>
                        </div>
                    )}
                    {failedCount > 0 && (
                        <div className="flex items-center gap-2 text-amber-700">
                            <ExclamationCircleIcon className="h-5 w-5" />
                            <span className="text-sm font-medium">
                                {t('admin_pages.enrollments.result_failed', { count: failedCount })}
                            </span>
                        </div>
                    )}
                </div>
            </Section>

            {enrolledCount > 0 && (
                <Section title={t('admin_pages.enrollments.result_enrolled_list')}>
                    <DataTable
                        data={enrolledData}
                        config={{
                            columns: enrolledColumns,
                            emptyState: {
                                title: t('admin_pages.enrollments.no_students_title'),
                                subtitle: '',
                            },
                        }}
                        className="border border-gray-200 rounded-lg"
                    />
                </Section>
            )}

            {failedCount > 0 && (
                <Section title={t('admin_pages.enrollments.result_failed_list')} variant="flat">
                    <ul className="space-y-2">
                        {result.failed.map((failure) => (
                            <li
                                key={failure.student_id}
                                className="flex items-center gap-3 text-sm"
                            >
                                <ExclamationCircleIcon className="h-4 w-4 shrink-0 text-amber-500" />
                                <span className="font-medium text-gray-900">
                                    {failure.student_name}
                                </span>
                                <span className="text-gray-500">—</span>
                                <span className="text-gray-600">{failure.reason}</span>
                            </li>
                        ))}
                    </ul>
                </Section>
            )}
        </div>
    );
}
