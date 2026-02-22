import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { type Subject, type ClassSubject, type PageProps, type PaginationType } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useBreadcrumbs } from '@/hooks/shared/useBreadcrumbs';
import { Button, Section, ConfirmationModal } from '@/Components';
import { Stat } from '@/Components';
import { Badge } from '@evalium/ui';
import { SubjectList } from '@/Components/shared/lists';
import { AcademicCapIcon, BookOpenIcon } from '@heroicons/react/24/outline';

interface SubjectWithDetails extends Subject {
    can_delete?: boolean;
}

interface Props extends PageProps {
    subject: SubjectWithDetails;
    classSubjects?: PaginationType<ClassSubject>;
    filters?: Record<string, string>;
}

/**
 * Admin-only subject detail page showing class assignments.
 */
export default function AdminSubjectsShow({ subject, classSubjects }: Props) {
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const { t } = useTranslations();
    const breadcrumbs = useBreadcrumbs();

    const translations = useMemo(
        () => ({
            back: t('commons/ui.back'),
            edit: t('commons/ui.edit'),
            delete: t('commons/ui.delete'),
            cancel: t('commons/ui.cancel'),
            code: t('admin_pages.subjects.code'),
            level: t('admin_pages.subjects.level'),
            classesCount: t('admin_pages.subjects.classes_count'),
            description: t('admin_pages.subjects.description'),
            classesSection: t('admin_pages.subjects.classes_section'),
            classesSectionSubtitle: t('admin_pages.subjects.classes_section_subtitle'),
            deleteTitle: t('admin_pages.subjects.delete_title'),
        }),
        [t],
    );

    const deleteMessage = useMemo(
        () => t('admin_pages.subjects.delete_message', { name: subject.name }),
        [t, subject.name],
    );

    const handleBack = () => router.visit(route('admin.subjects.index'));

    const handleEdit = () => router.visit(route('admin.subjects.edit', subject.id));

    const handleDeleteConfirm = () => {
        router.delete(route('admin.subjects.destroy', subject.id));
    };

    const handleClassClick = (classSubject: ClassSubject) => {
        if (classSubject.class) {
            router.visit(route('admin.classes.show', classSubject.class.id));
        }
    };

    return (
        <AuthenticatedLayout
            title={subject.name}
            breadcrumb={breadcrumbs.admin.showSubject(subject)}
        >
            <div className="space-y-6">
                <Section
                    title={subject.name}
                    subtitle={translations.classesSection}
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
                            <Button size="sm" variant="solid" color="primary" onClick={handleEdit}>
                                {translations.edit}
                            </Button>
                            {subject.can_delete && (
                                <Button
                                    size="sm"
                                    variant="outline"
                                    color="danger"
                                    onClick={() => setIsDeleteModalOpen(true)}
                                >
                                    {translations.delete}
                                </Button>
                            )}
                        </div>
                    }
                >
                    <Stat.Group columns={3}>
                        <Stat.Item
                            icon={BookOpenIcon}
                            title={translations.code}
                            value={<Badge label={subject.code} type="info" size="sm" />}
                        />
                        <Stat.Item
                            icon={AcademicCapIcon}
                            title={translations.level}
                            value={
                                <span className="text-sm text-gray-900">
                                    {subject.level?.name || '-'}
                                </span>
                            }
                        />
                        <Stat.Item
                            icon={BookOpenIcon}
                            title={translations.classesCount}
                            value={
                                <span className="text-sm font-semibold text-gray-900">
                                    {classSubjects?.total ?? 0}
                                </span>
                            }
                        />
                    </Stat.Group>

                    {subject.description && (
                        <div className="mt-6 pt-6 border-t border-gray-200">
                            <div className="text-sm font-medium text-gray-500 mb-2">
                                {translations.description}
                            </div>
                            <div className="text-sm text-gray-900">{subject.description}</div>
                        </div>
                    )}
                </Section>

                {classSubjects && (
                    <Section
                        title={translations.classesSection}
                        subtitle={translations.classesSectionSubtitle}
                    >
                        <SubjectList
                            data={classSubjects}
                            variant="class-assignment"
                            onClassClick={handleClassClick}
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
