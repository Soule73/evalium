import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { BaseEntityList } from './BaseEntityList';
import { type ClassSubject, type ClassModel, type Subject, type User } from '@/types';
import { Badge } from '@evalium/ui';
import { useTranslations } from '@/hooks';
import type { EntityListConfig } from './types/listConfig';
import type { PaginationType } from '@/types/datatable';

interface ClassSubjectListProps {
    data: PaginationType<ClassSubject>;
    variant?: 'admin' | 'teacher';
    classes?: ClassModel[];
    subjects?: Subject[];
    teachers?: User[];
    showClassColumn?: boolean;
    showTeacherColumn?: boolean;
    showAssessmentsColumn?: boolean;
    showPagination?: boolean;
    onView?: (classSubject: ClassSubject) => void;
    onCreateAssessment?: (classSubject: ClassSubject) => void;
}

/**
 * Unified ClassSubjectList component for displaying class-subject assignments
 *
 * Supports variants:
 * - admin: View assignments with link to detail page
 * - teacher: View with create assessment action
 */
export function ClassSubjectList({
    data,
    variant = 'admin',
    classes = [],
    subjects = [],
    teachers = [],
    showClassColumn = true,
    showTeacherColumn = true,
    showAssessmentsColumn = true,
    showPagination = true,
    onView,
    onCreateAssessment,
}: ClassSubjectListProps) {
    const { t } = useTranslations();

    const config: EntityListConfig<ClassSubject> = useMemo(
        () => ({
            entity: 'class-subject',

            columns: [
                {
                    key: 'class',
                    labelKey: 'admin_pages.class_subjects.class',
                    render: (classSubject) => {
                        const levelInfo = classSubject.class?.level
                            ? `${classSubject.class.level.name} (${classSubject.class.level.description})`
                            : '';
                        return (
                            <>
                                <div className="font-medium text-gray-900">
                                    {classSubject.class?.name}
                                </div>
                                {levelInfo && (
                                    <div className="text-sm text-gray-500">{levelInfo}</div>
                                )}
                            </>
                        );
                    },
                    conditional: () => showClassColumn,
                },

                {
                    key: 'subject',
                    labelKey: 'admin_pages.class_subjects.subject',
                    render: (classSubject) => (
                        <>
                            <span className="text-sm text-gray-900">
                                {classSubject.subject?.name}
                            </span>
                            <Badge label={classSubject.subject?.code || ''} type="info" size="sm" />
                        </>
                    ),
                },

                {
                    key: 'teacher',
                    labelKey: 'admin_pages.class_subjects.teacher',
                    render: (classSubject) => (
                        <>
                            <div className="text-sm font-medium text-gray-900">
                                {classSubject.teacher?.name || '-'}
                            </div>
                            {classSubject.teacher?.email && (
                                <div className="text-xs text-gray-500">
                                    {classSubject.teacher.email}
                                </div>
                            )}
                        </>
                    ),
                    conditional: () => showTeacherColumn,
                },

                {
                    key: 'coefficient',
                    labelKey: 'admin_pages.class_subjects.coefficient',
                    render: (classSubject) => (
                        <Badge label={classSubject.coefficient.toString()} type="info" size="sm" />
                    ),
                },

                {
                    key: 'semester',
                    labelKey: 'admin_pages.class_subjects.semester',
                    render: (classSubject) => (
                        <div className="text-sm text-gray-600">
                            {classSubject.semester
                                ? `S${classSubject.semester.order_number}`
                                : t('admin_pages.class_subjects.all_year')}
                        </div>
                    ),
                },

                {
                    key: 'status',
                    labelKey: 'admin_pages.common.status',
                    render: (classSubject) => {
                        const isActive = !classSubject.valid_to;
                        return (
                            <Badge
                                label={
                                    isActive
                                        ? t('admin_pages.class_subjects.active')
                                        : t('admin_pages.class_subjects.archived')
                                }
                                type={isActive ? 'success' : 'gray'}
                                size="sm"
                            />
                        );
                    },
                },

                {
                    key: 'assessments',
                    labelKey: 'admin_pages.classes.assessments',
                    render: (classSubject) => (
                        <div className="text-sm text-gray-600">
                            {classSubject.assessments_count || 0}
                        </div>
                    ),
                    conditional: () => showAssessmentsColumn,
                },
            ],

            actions: [
                {
                    labelKey: 'admin_pages.common.view',
                    onClick: (classSubject: ClassSubject) => {
                        if (onView) {
                            onView(classSubject);
                        } else {
                            router.visit(
                                route('admin.classes.subjects.show', {
                                    class: classSubject.class_id,
                                    class_subject: classSubject.id,
                                }),
                            );
                        }
                    },
                    color: 'secondary' as const,
                    variant: 'outline' as const,
                    conditional: (_item: ClassSubject, v) => v === 'admin',
                },
                {
                    labelKey: 'teacher_class_pages.show.create_assessment',
                    onClick: (classSubject: ClassSubject) => {
                        onCreateAssessment?.(classSubject);
                    },
                    color: 'primary' as const,
                    variant: 'solid' as const,
                    conditional: (_item: ClassSubject, v) =>
                        v === 'teacher' && !!onCreateAssessment,
                },
            ],
            filters: [
                {
                    key: 'class_id',
                    labelKey: 'admin_pages.class_subjects.class',
                    type: 'select' as const,
                    options: [
                        { value: '', label: t('admin_pages.class_subjects.all_classes') },
                        ...classes.map((c) => ({ value: String(c.id), label: `${c.name} (${c.level?.name ?? ''})` })),
                    ],
                    conditional: (v) => v === 'admin',
                },
                {
                    key: 'subject_id',
                    labelKey: 'admin_pages.class_subjects.subject',
                    type: 'select' as const,
                    options: [
                        { value: '', label: t('admin_pages.class_subjects.all_subjects') },
                        ...subjects.map((s) => ({ value: String(s.id), label: s.name })),
                    ],
                    conditional: (v) => v === 'admin',
                },
                {
                    key: 'teacher_id',
                    labelKey: 'admin_pages.class_subjects.teacher',
                    type: 'select' as const,
                    options: [
                        { value: '', label: t('admin_pages.class_subjects.all_teachers') },
                        ...teachers.map((teacher) => ({
                            value: String(teacher.id),
                            label: teacher.name,
                        })),
                    ],
                    conditional: (v) => v === 'admin',
                },
                {
                    key: 'include_archived',
                    labelKey: 'admin_pages.class_subjects.include_archived',
                    type: 'boolean' as const,
                    conditional: (v) => v === 'admin',
                },
            ],
        }),
        [
            showClassColumn,
            showTeacherColumn,
            showAssessmentsColumn,
            onView,
            onCreateAssessment,
            classes,
            subjects,
            teachers,
            t,
        ],
    );

    return (
        <BaseEntityList
            data={data}
            config={config}
            variant={variant}
            showPagination={showPagination}
        />
    );
}
