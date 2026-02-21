import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type ClassSubject, type PageProps, type User } from '@/types';
import { type PaginationType } from '@/types/datatable';
import { formatDate, hasPermission } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Section, Badge, Stat, ActionGroup } from '@/Components';
import { ClassSubjectHistoryList } from '@/Components/shared/lists';
import {
    ReplaceTeacherModal,
    UpdateCoefficientModal,
    TerminateClassSubjectModal,
} from '@/Components/features';
import { route } from 'ziggy-js';
import {
    AcademicCapIcon,
    UserIcon,
    HashtagIcon,
    CalendarIcon,
    ClockIcon,
    BookOpenIcon,
    ArrowLeftIcon,
    XMarkIcon,
} from '@heroicons/react/24/outline';

interface Props extends PageProps {
    classSubject: ClassSubject;
    history: PaginationType<ClassSubject>;
    teachers?: User[];
}

export default function ClassSubjectShow({ classSubject, history, teachers = [], auth }: Props) {
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();
    const canUpdate = hasPermission(auth.permissions, 'update class subjects');

    const [replaceTeacherModal, setReplaceTeacherModal] = useState(false);
    const [coefficientModal, setCoefficientModal] = useState(false);
    const [terminateModal, setTerminateModal] = useState(false);

    const handleBack = () => {
        router.visit(route('admin.class-subjects.index'));
    };

    const isActive = !classSubject.valid_to;

    const translations = useMemo(
        () => ({
            showTitle: t('admin_pages.class_subjects.show_title'),
            showSubtitle: t('admin_pages.class_subjects.show_subtitle'),
            back: t('commons/ui.back'),
            replaceTeacher: t('admin_pages.class_subjects.replace_teacher'),
            updateCoefficient: t('admin_pages.class_subjects.update_coefficient'),
            terminate: t('admin_pages.class_subjects.terminate'),
            class: t('admin_pages.class_subjects.class'),
            subject: t('admin_pages.class_subjects.subject'),
            teacher: t('admin_pages.class_subjects.teacher'),
            coefficient: t('admin_pages.class_subjects.coefficient'),
            status: t('commons/table.status'),
            active: t('admin_pages.class_subjects.active'),
            archived: t('admin_pages.class_subjects.archived'),
            validityPeriod: t('admin_pages.class_subjects.validity_period'),
            semester: t('admin_pages.class_subjects.semester'),
            historyTitle: t('admin_pages.class_subjects.history_title'),
            historySubtitle: t('admin_pages.class_subjects.history_subtitle'),
        }),
        [t],
    );

    return (
        <AuthenticatedLayout
            title={translations.showTitle}
            breadcrumb={breadcrumbs.admin.classSubjects()}
        >
            <div className="space-y-6">
                <Section
                    title={translations.showTitle}
                    subtitle={translations.showSubtitle}
                    actions={
                        <ActionGroup
                            actions={[
                                {
                                    label: translations.back,
                                    onClick: handleBack,
                                    color: 'secondary',
                                    icon: ArrowLeftIcon,
                                },
                                ...(canUpdate && isActive
                                    ? [
                                          {
                                              label: translations.replaceTeacher,
                                              onClick: () => setReplaceTeacherModal(true),
                                              color: 'primary' as const,
                                              icon: UserIcon,
                                          },
                                      ]
                                    : []),
                            ]}
                            dropdownActions={
                                canUpdate && isActive
                                    ? [
                                          {
                                              label: translations.updateCoefficient,
                                              onClick: () => setCoefficientModal(true),
                                              icon: HashtagIcon,
                                              color: 'warning' as const,
                                          },
                                          'divider',
                                          {
                                              label: translations.terminate,
                                              onClick: () => setTerminateModal(true),
                                              icon: XMarkIcon,
                                              color: 'danger' as const,
                                          },
                                      ]
                                    : []
                            }
                        />
                    }
                >
                    <Stat.Group columns={2}>
                        <Stat.Item
                            icon={AcademicCapIcon}
                            title={translations.class}
                            value={
                                <div className="text-sm font-semibold text-gray-900">
                                    {classSubject.class?.display_name ?? classSubject.class?.name}
                                </div>
                            }
                        />
                        <Stat.Item
                            icon={BookOpenIcon}
                            title={translations.subject}
                            value={
                                <div>
                                    <span className="text-sm font-semibold text-gray-900">
                                        {classSubject.subject?.name}
                                    </span>
                                    <Badge
                                        label={classSubject.subject?.code || ''}
                                        type="info"
                                        size="sm"
                                    />
                                </div>
                            }
                        />
                        <Stat.Item
                            icon={UserIcon}
                            title={translations.teacher}
                            value={
                                <div>
                                    <div className="text-sm font-semibold text-gray-900">
                                        {classSubject.teacher?.name}
                                    </div>
                                    <div className="text-xs text-gray-500">
                                        {classSubject.teacher?.email}
                                    </div>
                                </div>
                            }
                        />
                        <Stat.Item
                            icon={HashtagIcon}
                            title={translations.coefficient}
                            value={
                                <Badge
                                    label={classSubject.coefficient.toString()}
                                    type="info"
                                    size="sm"
                                />
                            }
                        />
                        <Stat.Item
                            icon={ClockIcon}
                            title={translations.status}
                            value={
                                <Badge
                                    label={isActive ? translations.active : translations.archived}
                                    type={isActive ? 'success' : 'gray'}
                                    size="sm"
                                />
                            }
                        />
                    </Stat.Group>

                    <div className="mt-6 pt-6 border-t border-gray-200">
                        <Stat.Group columns={2}>
                            <Stat.Item
                                icon={CalendarIcon}
                                title={translations.validityPeriod}
                                value={
                                    <span className="text-sm text-gray-700">
                                        {formatDate(classSubject.valid_from)}
                                        {classSubject.valid_to &&
                                            ` - ${formatDate(classSubject.valid_to)}`}
                                    </span>
                                }
                            />
                            {classSubject.semester && (
                                <Stat.Item
                                    icon={CalendarIcon}
                                    title={translations.semester}
                                    value={
                                        <Badge
                                            label={`S${classSubject.semester.order_number}`}
                                            type="info"
                                            size="sm"
                                        />
                                    }
                                />
                            )}
                        </Stat.Group>
                    </div>
                </Section>

                {history.data.length > 0 && (
                    <Section
                        title={translations.historyTitle}
                        subtitle={translations.historySubtitle}
                    >
                        <ClassSubjectHistoryList data={history} />
                    </Section>
                )}
            </div>

            <ReplaceTeacherModal
                isOpen={replaceTeacherModal}
                onClose={() => setReplaceTeacherModal(false)}
                classSubject={classSubject}
                teachers={teachers}
            />

            <UpdateCoefficientModal
                isOpen={coefficientModal}
                onClose={() => setCoefficientModal(false)}
                classSubject={classSubject}
            />

            <TerminateClassSubjectModal
                isOpen={terminateModal}
                onClose={() => setTerminateModal(false)}
                classSubject={classSubject}
            />
        </AuthenticatedLayout>
    );
}
