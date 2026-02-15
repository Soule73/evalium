import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import {
    type ClassModel,
    type Assessment,
    type Enrollment,
    type ClassSubject,
    type PageProps,
    type PaginationType,
} from '@/types';
import { hasPermission } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section, Badge, ConfirmationModal, Stat } from '@/Components';
import { EnrollmentList, ClassSubjectList, AssessmentList } from '@/Components/shared/lists';
import { route } from 'ziggy-js';
import {
    AcademicCapIcon,
    UserGroupIcon,
    BookOpenIcon,
    CalendarIcon,
    DocumentTextIcon,
} from '@heroicons/react/24/outline';

interface ClassStatistics {
    total_students: number;
    active_students: number;
    withdrawn_students: number;
    subjects_count: number;
    assessments_count: number;
    max_students: number;
    available_slots: number;
}

interface Props extends PageProps {
    class: ClassModel;
    enrollments: PaginationType<Enrollment>;
    classSubjects: PaginationType<ClassSubject>;
    assessments?: PaginationType<Assessment>;
    statistics: ClassStatistics;
    studentsFilters?: {
        search?: string;
    };
    subjectsFilters?: {
        search?: string;
    };
    assessmentsFilters?: {
        search?: string;
        subject_id?: string;
        teacher_id?: string;
        type?: string;
        delivery_mode?: string;
    };
}

export default function ClassShow({
    class: classItem,
    enrollments,
    classSubjects,
    assessments,
    statistics,
    auth,
}: Props) {
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const canUpdate = hasPermission(auth.permissions, 'update classes');
    const canDelete = hasPermission(auth.permissions, 'delete classes');
    const canBeSafelyDeleted = classItem.can_delete ?? false;

    const handleEdit = () => {
        router.visit(route('admin.classes.edit', classItem.id));
    };

    const handleDeleteConfirm = () => {
        router.delete(route('admin.classes.destroy', classItem.id), {
            onSuccess: () => setIsDeleteModalOpen(false),
        });
    };

    const handleBack = () => {
        router.visit(route('admin.classes.index'));
    };

    const enrollmentPercentage =
        statistics.max_students > 0
            ? (statistics.active_students / statistics.max_students) * 100
            : 0;

    const translations = useMemo(
        () => ({
            showSubtitle: t('admin_pages.classes.show_subtitle'),
            back: t('admin_pages.common.back'),
            edit: t('admin_pages.common.edit'),
            cannotDeleteHasData: t('admin_pages.classes.cannot_delete_has_data'),
            delete: t('admin_pages.common.delete'),
            level: t('admin_pages.classes.level'),
            academicYear: t('admin_pages.classes.academic_year'),
            students: t('admin_pages.classes.students'),
            full: t('admin_pages.classes.full'),
            subjects: t('admin_pages.classes.subjects'),
            assessmentsLabel: t('admin_pages.classes.assessments'),
            enrollmentsSection: t('admin_pages.classes.enrollments_section'),
            enrollmentsSectionSubtitle: t('admin_pages.classes.enrollments_section_subtitle'),
            subjectsSection: t('admin_pages.classes.subjects_section'),
            subjectsSectionSubtitle: t('admin_pages.classes.subjects_section_subtitle'),
            assessmentsSection: t('admin_pages.classes.assessments_section'),
            assessmentsSectionSubtitle: t('admin_pages.classes.assessments_section_subtitle'),
            deleteTitle: t('admin_pages.classes.delete_title'),
            cancel: t('admin_pages.common.cancel'),
        }),
        [t],
    );

    const deleteMessage = useMemo(
        () => t('admin_pages.classes.delete_message', { name: classItem.name }),
        [t, classItem.name],
    );

    return (
        <AuthenticatedLayout
            title={classItem.name || '-'}
            breadcrumb={breadcrumbs.admin.showClass(classItem)}
        >
            <div className="space-y-6">
                <Section
                    title={classItem.name || '-'}
                    subtitle={translations.showSubtitle}
                    actions={
                        <div className="flex space-x-3">
                            <Button
                                size="sm"
                                variant="outline"
                                color="secondary"
                                onClick={handleBack}
                            >
                                {translations.back}
                            </Button>
                            {canUpdate && (
                                <Button
                                    size="sm"
                                    variant="solid"
                                    color="primary"
                                    onClick={handleEdit}
                                >
                                    {translations.edit}
                                </Button>
                            )}
                            {canDelete && (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    color="danger"
                                    onClick={() => setIsDeleteModalOpen(true)}
                                    disabled={!canBeSafelyDeleted}
                                    title={
                                        !canBeSafelyDeleted
                                            ? translations.cannotDeleteHasData
                                            : undefined
                                    }
                                >
                                    {translations.delete}
                                </Button>
                            )}
                        </div>
                    }
                >
                    <Stat.Group columns={4}>
                        <Stat.Item
                            icon={AcademicCapIcon}
                            title={translations.level}
                            value={
                                <span className="text-sm text-gray-900">
                                    {classItem.level?.name || '-'}
                                </span>
                            }
                        />
                        <Stat.Item
                            icon={CalendarIcon}
                            title={translations.academicYear}
                            value={
                                <span className="text-sm text-gray-900">
                                    {classItem.academic_year?.name || '-'}
                                </span>
                            }
                        />
                        <Stat.Item
                            icon={UserGroupIcon}
                            title={translations.students}
                            value={
                                <div className="flex items-center space-x-2">
                                    <span className="text-sm font-semibold text-gray-900">
                                        {statistics.active_students} / {statistics.max_students}
                                    </span>
                                    {enrollmentPercentage >= 90 && (
                                        <Badge label={translations.full} type="warning" size="sm" />
                                    )}
                                </div>
                            }
                        />
                        <Stat.Item
                            icon={BookOpenIcon}
                            title={translations.subjects}
                            value={
                                <span className="text-sm font-semibold text-gray-900">
                                    {statistics.subjects_count}
                                </span>
                            }
                        />
                        <Stat.Item
                            icon={DocumentTextIcon}
                            title={translations.assessmentsLabel}
                            value={
                                <span className="text-sm font-semibold text-gray-900">
                                    {statistics.assessments_count}
                                </span>
                            }
                        />
                    </Stat.Group>
                </Section>

                <Section
                    title={translations.enrollmentsSection}
                    subtitle={translations.enrollmentsSectionSubtitle}
                >
                    <EnrollmentList
                        data={enrollments}
                        variant="admin"
                        showClassColumn={false}
                        permissions={{ canView: true }}
                        onView={(enrollment) =>
                            enrollment.student_id &&
                            router.visit(route('admin.users.show', enrollment.student_id))
                        }
                    />
                </Section>

                <Section
                    title={translations.subjectsSection}
                    subtitle={translations.subjectsSectionSubtitle}
                >
                    <ClassSubjectList
                        data={classSubjects}
                        variant="admin"
                        showClassColumn={false}
                    />
                </Section>

                {assessments && (
                    <Section
                        title={translations.assessmentsSection}
                        subtitle={translations.assessmentsSectionSubtitle}
                    >
                        <AssessmentList
                            data={assessments}
                            variant="admin"
                            showClassColumn={false}
                            onView={(item) => {
                                const assessment = item as Assessment;
                                router.visit(route('admin.assessments.show', assessment.id));
                            }}
                        />
                    </Section>
                )}
            </div>

            <ConfirmationModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={handleDeleteConfirm}
                title={translations.deleteTitle}
                message={deleteMessage}
                confirmText={translations.delete}
                cancelText={translations.cancel}
                type="danger"
            />
        </AuthenticatedLayout>
    );
}
