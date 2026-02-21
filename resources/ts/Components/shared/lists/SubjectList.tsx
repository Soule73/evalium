import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { BaseEntityList } from './BaseEntityList';
import { type Subject, type ClassSubject, type Level, type ClassModel } from '@/types';
import { Badge } from '@evalium/ui';
import { useTranslations } from '@/hooks';
import type { EntityListConfig } from './types/listConfig';
import type { PaginationType } from '@/types/datatable';

interface TeacherSubject extends Subject {
    classes?: ClassModel[];
    classes_count?: number;
    assessments_count?: number;
}

type SubjectItem = Subject | ClassSubject | TeacherSubject;

interface SubjectListProps {
    data: PaginationType<SubjectItem>;
    variant?: 'admin' | 'teacher' | 'class-assignment';
    levels?: Level[];
    classes?: ClassModel[];
    onView?: (item: Subject | TeacherSubject) => void;
    onClassClick?: (classSubject: ClassSubject) => void;
}

/**
 * Unified SubjectList component for displaying subjects and class-subject assignments
 *
 * Supports three variants:
 * - admin: Shows subjects with code, name, level, class count (Subject type)
 * - teacher: Shows subjects with name/code, classes badges, assessments count (TeacherSubject type)
 * - class-assignment: Shows class-subject assignments with class, teacher, coefficient (ClassSubject type)
 */
export function SubjectList({
    data,
    variant = 'admin',
    levels = [],
    classes = [],
    onView,
    onClassClick,
}: SubjectListProps) {
    const { t } = useTranslations();

    const config: EntityListConfig<SubjectItem> = useMemo(() => {
        const levelFilterOptions = [
            { value: '', label: t('admin_pages.subjects.all_levels') },
            ...levels.map((level) => ({
                value: level.id,
                label: level.name,
            })),
        ];

        const classFilterOptions = [
            { value: '', label: t('teacher_subject_pages.index.all_classes') },
            ...classes.map((cls) => ({
                value: cls.id,
                label: cls.name,
            })),
        ];

        return {
            entity: 'subject',

            filters: [
                {
                    key: 'level_id',
                    labelKey: 'admin_pages.subjects.level',
                    type: 'select' as const,
                    options: levelFilterOptions,
                    conditional: (v: string) => v === 'admin',
                },
                {
                    key: 'class_id',
                    labelKey: 'teacher_subject_pages.index.filter_class',
                    type: 'select' as const,
                    options: classFilterOptions,
                    conditional: (v: string) => v === 'teacher',
                },
            ],

            columns: [
                {
                    key: 'code',
                    labelKey: 'admin_pages.subjects.code',
                    render: (item: SubjectItem) => {
                        const subject = item as Subject;
                        return (
                            <div className="flex items-center space-x-2">
                                <Badge label={subject.code} type="info" size="sm" />
                            </div>
                        );
                    },
                    conditional: (v: string) => v === 'admin',
                },

                {
                    key: 'name',
                    labelKey: 'admin_pages.subjects.name',
                    render: (item: SubjectItem) => {
                        const subject = item as Subject;
                        return (
                            <div>
                                <div className="font-medium text-gray-900">{subject.name}</div>
                                {subject.description && (
                                    <div className="text-sm text-gray-500 truncate max-w-md">
                                        {subject.description}
                                    </div>
                                )}
                            </div>
                        );
                    },
                    conditional: (v: string) => v === 'admin',
                },

                {
                    key: 'level',
                    labelKey: 'admin_pages.subjects.level',
                    render: (item: SubjectItem) => {
                        const subject = item as Subject;
                        return (
                            <div className="text-sm text-gray-900">
                                {subject.level?.name || '-'}
                            </div>
                        );
                    },
                    conditional: (v: string) => v === 'admin',
                },

                {
                    key: 'classes',
                    labelKey: 'admin_pages.subjects.classes_count',
                    render: (item: SubjectItem) => {
                        const subject = item as Subject;
                        return (
                            <div className="text-sm text-gray-600">
                                {subject.class_subjects_count || 0}
                            </div>
                        );
                    },
                    conditional: (v: string) => v === 'admin',
                },

                {
                    key: 'teacher_name',
                    labelKey: 'teacher_subject_pages.index.subject',
                    render: (item: SubjectItem) => {
                        const subject = item as TeacherSubject;
                        return (
                            <div>
                                <div className="font-medium text-gray-900">{subject.name}</div>
                                <div className="text-sm text-gray-500">{subject.code}</div>
                            </div>
                        );
                    },
                    conditional: (v: string) => v === 'teacher',
                },

                {
                    key: 'teacher_classes',
                    labelKey: 'teacher_subject_pages.index.classes',
                    render: (item: SubjectItem) => {
                        const subject = item as TeacherSubject;
                        const subjectClasses = subject.classes || [];
                        const classesCount = subject.classes_count || subjectClasses.length;
                        return (
                            <div className="flex flex-wrap gap-1">
                                {subjectClasses.slice(0, 3).map((cls) => (
                                    <Badge key={cls.id} label={cls.name} type="info" size="sm" />
                                ))}
                                {classesCount > 3 && (
                                    <Badge label={`+${classesCount - 3}`} type="gray" size="sm" />
                                )}
                            </div>
                        );
                    },
                    conditional: (v: string) => v === 'teacher',
                },

                {
                    key: 'teacher_assessments',
                    labelKey: 'teacher_subject_pages.index.assessments',
                    render: (item: SubjectItem) => {
                        const subject = item as TeacherSubject;
                        return (
                            <Badge
                                label={String(subject.assessments_count || 0)}
                                type="success"
                                size="sm"
                            />
                        );
                    },
                    conditional: (v: string) => v === 'teacher',
                },

                {
                    key: 'class',
                    labelKey: 'admin_pages.subjects.class',
                    render: (item: SubjectItem) => {
                        const classSubject = item as ClassSubject;
                        const levelNameDescription = `${classSubject.class?.level?.name} (${classSubject.class?.level?.description})`;
                        return (
                            <div
                                className="cursor-pointer hover:text-primary-600"
                                onClick={() => onClassClick?.(classSubject)}
                            >
                                <div className="font-medium">{classSubject.class?.name}</div>
                                <div className="text-sm text-gray-500">{levelNameDescription}</div>
                            </div>
                        );
                    },
                    conditional: (v: string) => v === 'class-assignment',
                },

                {
                    key: 'teacher',
                    labelKey: 'admin_pages.subjects.teacher',
                    render: (item: SubjectItem) => {
                        const classSubject = item as ClassSubject;
                        return (
                            <div className="text-sm text-gray-900">
                                {classSubject.teacher?.name || '-'}
                            </div>
                        );
                    },
                    conditional: (v: string) => v === 'class-assignment',
                },

                {
                    key: 'coefficient',
                    labelKey: 'admin_pages.subjects.coefficient',
                    render: (item: SubjectItem) => {
                        const classSubject = item as ClassSubject;
                        return (
                            <Badge
                                label={classSubject.coefficient.toString()}
                                type="info"
                                size="sm"
                            />
                        );
                    },
                    conditional: (v: string) => v === 'class-assignment',
                },
            ],

            actions: [
                {
                    labelKey:
                        variant === 'teacher'
                            ? 'teacher_subject_pages.index.view'
                            : 'commons/ui.view',
                    onClick: (item: SubjectItem) => {
                        if (variant === 'admin') {
                            const subject = item as Subject;
                            return (
                                onView?.(subject) ||
                                router.visit(route('admin.subjects.show', subject.id))
                            );
                        } else if (variant === 'teacher') {
                            const subject = item as TeacherSubject;
                            return (
                                onView?.(subject) ||
                                router.visit(route('teacher.subjects.show', subject.id))
                            );
                        } else {
                            const classSubject = item as ClassSubject;
                            if (classSubject.class) {
                                if (onClassClick) {
                                    onClassClick(classSubject);
                                } else {
                                    router.visit(
                                        route('admin.classes.show', classSubject.class.id),
                                    );
                                }
                            }
                        }
                    },
                    color: 'secondary' as const,
                    variant: 'outline' as const,
                },
            ],
        };
    }, [variant, levels, classes, onView, onClassClick, t]);

    return <BaseEntityList data={data} config={config} variant={variant} />;
}
