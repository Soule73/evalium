import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import {
    type ClassModel,
    type Assessment,
    type ClassSubject,
    type Subject,
    type User,
    type Semester,
    type PageProps,
    type PaginationType,
    type ClassStatistics,
} from '@/types';
import { hasPermission } from '@/utils';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section, Badge, ConfirmationModal, Stat } from '@/Components';
import { ClassSubjectList, AssessmentList } from '@/Components/shared/lists';
import { CreateClassSubjectModal } from '@/Components/features';
import { route } from 'ziggy-js';
import {
    AcademicCapIcon,
    UserGroupIcon,
    BookOpenIcon,
    CalendarIcon,
    DocumentTextIcon,
    PlusIcon,
} from '@heroicons/react/24/outline';

interface ClassSubjectFormData {
    classes: ClassModel[];
    subjects: Subject[];
    teachers: User[];
    semesters: Semester[];
}

interface Props extends PageProps {
    class: ClassModel;
    recentClassSubjects: PaginationType<ClassSubject>;
    recentAssessments?: PaginationType<Assessment>;
    statistics: ClassStatistics;
    classSubjectFormData: ClassSubjectFormData;
}

export default function ClassShow({
    class: classItem,
    recentClassSubjects,
    recentAssessments,
    statistics,
    auth,
    classSubjectFormData,
}: Props) {
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [isAssignSubjectModalOpen, setIsAssignSubjectModalOpen] = useState(false);
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const canUpdate = hasPermission(auth.permissions, 'update classes');
    const canDelete = hasPermission(auth.permissions, 'delete classes');
    const canCreateSubject = hasPermission(auth.permissions, 'create class subjects');
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
            studentsSection: t('admin_pages.classes.students_section'),
            studentsSectionSubtitle: t('admin_pages.classes.students_section_subtitle'),
            seeAllStudents: t('admin_pages.classes.see_all_students'),
            subjectsSection: t('admin_pages.classes.subjects_section'),
            subjectsSectionSubtitle: t('admin_pages.classes.subjects_section_subtitle'),
            assignSubject: t('admin_pages.classes.assign_subject'),
            seeAllSubjects: t('admin_pages.classes.see_all_subjects'),
            assessmentsSection: t('admin_pages.classes.assessments_section'),
            assessmentsSectionSubtitle: t('admin_pages.classes.assessments_section_subtitle'),
            seeAllAssessments: t('admin_pages.classes.see_all_assessments'),
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
                    title={translations.studentsSection}
                    subtitle={translations.studentsSectionSubtitle}
                    actions={
                        <Button
                            size="sm"
                            variant="outline"
                            color="secondary"
                            onClick={() =>
                                router.visit(
                                    route('admin.classes.students.index', classItem.id),
                                )
                            }
                        >
                            {translations.seeAllStudents}
                        </Button>
                    }
                >
                    <div className="flex items-center gap-2 text-sm text-gray-600">
                        <UserGroupIcon className="h-5 w-5" />
                        <span>
                            {statistics.active_students} / {statistics.max_students}{' '}
                            {translations.students}
                        </span>
                        {enrollmentPercentage >= 90 && (
                            <Badge label={translations.full} type="warning" size="sm" />
                        )}
                    </div>
                </Section>

                <Section
                    title={translations.subjectsSection}
                    subtitle={translations.subjectsSectionSubtitle}
                    actions={
                        <div className="flex items-center gap-3">
                            <Button
                                size="sm"
                                variant="outline"
                                color="secondary"
                                onClick={() =>
                                    router.visit(route('admin.classes.subjects', classItem.id))
                                }
                            >
                                {translations.seeAllSubjects}
                            </Button>
                            {canCreateSubject && (
                                <Button
                                    size="sm"
                                    variant="solid"
                                    color="primary"
                                    onClick={() => setIsAssignSubjectModalOpen(true)}
                                >
                                    <PlusIcon className="w-4 h-4 mr-1" />
                                    {translations.assignSubject}
                                </Button>
                            )}
                        </div>
                    }
                >
                    <ClassSubjectList
                        data={recentClassSubjects}
                        variant="admin"
                        showClassColumn={false}
                        showPagination={false}
                    />
                </Section>

                {recentAssessments && (
                    <Section
                        title={translations.assessmentsSection}
                        subtitle={translations.assessmentsSectionSubtitle}
                        actions={
                            <Button
                                size="sm"
                                variant="outline"
                                color="secondary"
                                onClick={() =>
                                    router.visit(route('admin.classes.assessments', classItem.id))
                                }
                            >
                                {translations.seeAllAssessments}
                            </Button>
                        }
                    >
                        <AssessmentList
                            data={recentAssessments}
                            variant="admin"
                            showClassColumn={false}
                            showPagination={false}
                            onView={(item) => {
                                const assessment = item as Assessment;
                                router.visit(
                                    route('admin.classes.assessments.show', {
                                        class: assessment.class_subject?.class_id ?? classItem.id,
                                        assessment: assessment.id,
                                    }),
                                );
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

            <CreateClassSubjectModal
                isOpen={isAssignSubjectModalOpen}
                onClose={() => setIsAssignSubjectModalOpen(false)}
                formData={classSubjectFormData}
                classId={classItem.id}
                redirectTo={route('admin.classes.show', classItem.id)}
            />
        </AuthenticatedLayout>
    );
}
