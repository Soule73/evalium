import { useCallback, useMemo, useState } from 'react';
import axios from 'axios';
import { route } from 'ziggy-js';
import { Button, Checkbox, Section } from '@evalium/ui';
import { useTranslations } from '@/hooks';
import { useEnrollmentWizard, type BulkEnrollmentResult } from '@/contexts/EnrollmentWizardContext';
import { UserAvatar } from '@/Components/layout/UserAvatar';
import { DataTable } from '@/Components/shared/datatable/DataTable';
import { type ColumnConfig } from '@/types/datatable';
import { type User } from '@/types';
import { ArrowLeftIcon, CheckIcon } from '@heroicons/react/24/outline';

/**
 * Step 3 of the enrollment wizard: confirm class and students, then submit bulk enrollment.
 */
export function EnrollmentSummaryStep() {
    const { t } = useTranslations();
    const { state, actions } = useEnrollmentWizard();
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [submitError, setSubmitError] = useState<string | null>(null);

    const handleBack = useCallback(() => {
        actions.goToStep(2);
    }, [actions]);

    const handleConfirm = useCallback(async () => {
        if (!state.selectedClass) {
            return;
        }

        setIsSubmitting(true);
        setSubmitError(null);

        try {
            const newStudentIds = state.newlyCreatedStudents.map((s) => s.id);
            const response = await axios.post<BulkEnrollmentResult>(
                route('admin.enrollments.bulk-store'),
                {
                    class_id: state.selectedClass.id,
                    student_ids: state.selectedStudents.map((s) => s.id),
                    new_student_ids: newStudentIds,
                    send_credentials: state.sendCredentials,
                },
            );
            actions.setBulkResult(response.data);
        } catch (error: unknown) {
            if (axios.isAxiosError(error) && error.response?.data?.message) {
                setSubmitError(error.response.data.message as string);
            } else {
                setSubmitError(t('admin_pages.enrollments.bulk_error'));
            }
        } finally {
            setIsSubmitting(false);
        }
    }, [state, actions, t]);

    const hasNewStudents = state.newlyCreatedStudents.length > 0;
    const newStudentIds = useMemo(
        () => new Set(state.newlyCreatedStudents.map((s) => s.id)),
        [state.newlyCreatedStudents],
    );

    const studentColumns: ColumnConfig<User>[] = useMemo(
        () => [
            {
                key: 'name',
                label: t('admin_pages.users.name'),
                render: (student) => (
                    <div className="flex items-center gap-3">
                        <UserAvatar name={student.name} size="sm" />
                        <div className="min-w-0">
                            <div className="flex items-center gap-2">
                                <span className="text-sm font-medium text-gray-900">
                                    {student.name}
                                </span>
                                {newStudentIds.has(student.id) && (
                                    <span className="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                        {t('admin_pages.enrollments.new_account')}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>
                ),
            },
            {
                key: 'email',
                label: t('admin_pages.users.email'),
                render: (student) => <span className="text-sm text-gray-500">{student.email}</span>,
            },
        ],
        [t, newStudentIds],
    );

    return (
        <div className="space-y-6">
            <Section title={t('admin_pages.enrollments.summary_class')}>
                <div className="flex items-start gap-4">
                    <div>
                        <div className="text-base font-semibold text-gray-900">
                            {state.selectedClass?.name}
                        </div>
                        {state.selectedClass?.level && (
                            <div className="text-sm text-gray-500">
                                {state.selectedClass.level.name}
                                {state.selectedClass.level.description
                                    ? ` — ${state.selectedClass.level.description}`
                                    : ''}
                            </div>
                        )}
                        {state.selectedClass?.academic_year && (
                            <div className="text-xs text-gray-400 mt-1">
                                {state.selectedClass.academic_year.name}
                            </div>
                        )}
                    </div>
                </div>
            </Section>

            <Section
                title={t('admin_pages.enrollments.summary_students', {
                    count: state.selectedStudents.length,
                })}
                actions={
                    <div className="flex items-center gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            color="primary"
                            size="sm"
                            onClick={handleBack}
                        >
                            <ArrowLeftIcon className="mr-2 h-4 w-4" />
                            {t('admin_pages.enrollments.back')}
                        </Button>
                        <Button
                            type="button"
                            color="primary"
                            variant="solid"
                            size="sm"
                            onClick={handleConfirm}
                            disabled={isSubmitting || state.selectedStudents.length === 0}
                        >
                            {isSubmitting ? (
                                <span className="flex items-center gap-2">
                                    <span className="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                                    {t('admin_pages.enrollments.enrolling')}
                                </span>
                            ) : (
                                <span className="flex items-center gap-2">
                                    <CheckIcon className="h-4 w-4" />
                                    {t('admin_pages.enrollments.confirm_enrollment')}
                                </span>
                            )}
                        </Button>
                    </div>
                }
            >
                <DataTable
                    data={state.selectedStudents}
                    config={{
                        columns: studentColumns,
                        emptyState: {
                            title: t('admin_pages.enrollments.no_students_title'),
                            subtitle: '',
                        },
                    }}
                    className="border border-gray-100 rounded-lg"
                />

                {hasNewStudents && (
                    <div className="mt-4 border-t border-gray-100 pt-4">
                        <Checkbox
                            label={t('admin_pages.enrollments.send_credentials_label', {
                                count: state.newlyCreatedStudents.length,
                            })}
                            checked={state.sendCredentials}
                            onChange={(e) => actions.setSendCredentials(e.target.checked)}
                        />
                        <p className="mt-1 text-xs text-gray-500">
                            {t('admin_pages.enrollments.send_credentials_hint')}
                        </p>
                    </div>
                )}

                {submitError && (
                    <div className="mt-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        {submitError}
                    </div>
                )}
            </Section>
        </div>
    );
}
