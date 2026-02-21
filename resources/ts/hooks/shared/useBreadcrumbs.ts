import { useMemo } from 'react';
import { useTranslations } from './useTranslations';
import { route } from 'ziggy-js';
import { type BreadcrumbItem } from '@/Components/layout/Breadcrumb';
import { type AssessmentRouteContext } from '@/types';

type TranslateFn = (key: string, replacements?: Record<string, string | number>) => string;

interface NamedEntity {
    id: number;
    name?: string;
    title?: string;
}

/**
 * Creates breadcrumbs factory bound to a specific translation function.
 */
function createBreadcrumbs(t: TranslateFn) {
    function createEntityBreadcrumbs<T extends NamedEntity>(config: {
        labelKey: string;
        indexRoute: string;
        showRoute?: string;
    }) {
        const { labelKey, indexRoute, showRoute } = config;

        const index = (): BreadcrumbItem[] => [{ label: t(labelKey), href: route(indexRoute) }];

        const create = (): BreadcrumbItem[] => [...index(), { label: t('breadcrumbs.create') }];

        const show = (item: T): BreadcrumbItem[] => {
            const label = item.name || item.title || `#${item.id}`;
            return [...index(), { label, href: showRoute ? route(showRoute, item.id) : undefined }];
        };

        const edit = (item: T): BreadcrumbItem[] => [
            ...show(item),
            { label: t('breadcrumbs.edit') },
        ];

        return { index, create, show, edit };
    }

    function createSimpleBreadcrumbs(labelKey: string, indexRoute: string) {
        const index = (): BreadcrumbItem[] => [{ label: t(labelKey), href: route(indexRoute) }];

        const create = (): BreadcrumbItem[] => [...index(), { label: t('breadcrumbs.create') }];

        const edit = (name: string): BreadcrumbItem[] => [...index(), { label: name }];

        return { index, create, edit };
    }

    const levelsBc = createSimpleBreadcrumbs('breadcrumbs.levels', 'admin.levels.index');
    const usersBc = createSimpleBreadcrumbs('breadcrumbs.users', 'admin.users.index');
    const teachersBc = createSimpleBreadcrumbs('breadcrumbs.teachers', 'admin.teachers.index');
    const rolesBc = createSimpleBreadcrumbs('breadcrumbs.roles_permissions', 'admin.roles.index');

    const academicYearsBc = createEntityBreadcrumbs<{ id: number; name: string }>({
        labelKey: 'breadcrumbs.academic_years',
        indexRoute: 'admin.academic-years.archives',
    });

    const subjectsBc = createEntityBreadcrumbs<{ id: number; name: string }>({
        labelKey: 'breadcrumbs.subjects',
        indexRoute: 'admin.subjects.index',
        showRoute: 'admin.subjects.show',
    });

    type ClassBcItem = { id: number; name?: string; level?: { name?: string } };

    const classLabel = (item: ClassBcItem): string => {
        const namePart = item.name || `#${item.id}`;
        return item.level?.name ? `${namePart} (${item.level.name})` : namePart;
    };

    const classesBc = {
        index: (): BreadcrumbItem[] => [
            { label: t('breadcrumbs.classes'), href: route('admin.classes.index') },
        ],
        create: (): BreadcrumbItem[] => [
            { label: t('breadcrumbs.classes'), href: route('admin.classes.index') },
            { label: t('breadcrumbs.create') },
        ],
        show: (item: ClassBcItem): BreadcrumbItem[] => [
            { label: t('breadcrumbs.classes'), href: route('admin.classes.index') },
            { label: classLabel(item), href: route('admin.classes.show', item.id) },
        ],
        edit: (item: ClassBcItem): BreadcrumbItem[] => [
            { label: t('breadcrumbs.classes'), href: route('admin.classes.index') },
            { label: classLabel(item), href: route('admin.classes.show', item.id) },
            { label: t('breadcrumbs.edit') },
        ],
    };

    const enrollmentsBc = createEntityBreadcrumbs<{ id: number; student?: { name: string } }>({
        labelKey: 'breadcrumbs.enrollments',
        indexRoute: 'admin.enrollments.index',
    });

    const classSubjectsBc = {
        index: (): BreadcrumbItem[] => [
            { label: t('breadcrumbs.class_subjects'), href: route('admin.class-subjects.index') },
        ],
    };

    const teacherClassesBc = {
        index: (): BreadcrumbItem[] => [
            { label: t('breadcrumbs.classes'), href: route('teacher.classes.index') },
        ],
        show: (classItem: { id: number; name?: string }): BreadcrumbItem[] => [
            { label: t('breadcrumbs.classes'), href: route('teacher.classes.index') },
            { label: classItem.name || '' },
        ],
    };

    const teacherSubjectsBc = {
        index: (): BreadcrumbItem[] => [
            { label: t('breadcrumbs.subjects'), href: route('teacher.subjects.index') },
        ],
        show: (subject: { id: number; name?: string }): BreadcrumbItem[] => [
            { label: t('breadcrumbs.subjects'), href: route('teacher.subjects.index') },
            { label: subject.name || '' },
        ],
    };

    const assessmentsBc = createEntityBreadcrumbs<{ id: number; title: string }>({
        labelKey: 'breadcrumbs.assessments',
        indexRoute: 'teacher.assessments.index',
        showRoute: 'teacher.assessments.show',
    });

    const adminAssessmentsBc = {
        index: (): BreadcrumbItem[] => [
            { label: t('breadcrumbs.assessments'), href: route('admin.assessments.index') },
        ],
    };

    const dynamicAssessmentBc = {
        index: (ctx: AssessmentRouteContext): BreadcrumbItem[] => [
            { label: t('breadcrumbs.assessments'), href: route(ctx.backRoute) },
        ],
        show: (
            ctx: AssessmentRouteContext,
            assessment: {
                id: number;
                title: string;
                class_subject?: { class?: { id: number; name?: string } };
            },
        ): BreadcrumbItem[] => {
            if (ctx.role === 'admin' && assessment.class_subject?.class) {
                const classItem = assessment.class_subject.class;
                return [
                    ...classesBc.show(classItem),
                    {
                        label: t('breadcrumbs.assessments'),
                        href: route('admin.classes.assessments', classItem.id),
                    },
                    {
                        label: assessment.title,
                        href: route('admin.classes.assessments.show', {
                            class: classItem.id,
                            assessment: assessment.id,
                        }),
                    },
                ];
            }
            return [
                { label: t('breadcrumbs.assessments'), href: route(ctx.backRoute) },
                ctx.showRoute
                    ? { label: assessment.title, href: route(ctx.showRoute, assessment.id) }
                    : { label: assessment.title },
            ];
        },
        grade: (
            ctx: AssessmentRouteContext,
            assessment: {
                id: number;
                title: string;
                class_subject?: { class?: { id: number; name?: string } };
            },
            student: { name: string },
        ): BreadcrumbItem[] => [
            ...dynamicAssessmentBc.show(ctx, assessment),
            { label: t('breadcrumbs.grading') + ': ' + student.name },
        ],
        review: (
            ctx: AssessmentRouteContext,
            assessment: {
                id: number;
                title: string;
                class_subject?: { class?: { id: number; name?: string } };
            },
            student: { name: string },
        ): BreadcrumbItem[] => [
            ...dynamicAssessmentBc.show(ctx, assessment),
            { label: t('breadcrumbs.review') + ': ' + student.name },
        ],
    };

    return {
        dashboard: (): BreadcrumbItem[] => [],
        assessment: dynamicAssessmentBc,
        users: usersBc.index,
        userCreate: usersBc.create,
        userEdit: usersBc.edit,
        teacherShow: (user: { name: string }): BreadcrumbItem[] => [
            ...teachersBc.index(),
            { label: user.name },
        ],
        adminShow: (user: { name: string }): BreadcrumbItem[] => [
            ...usersBc.index(),
            { label: user.name },
        ],
        levels: levelsBc.index,
        levelCreate: levelsBc.create,
        levelEdit: levelsBc.edit,
        roles: rolesBc.index,
        roleEdit: rolesBc.edit,
        admin: {
            academicYears: academicYearsBc.index,
            createAcademicYear: academicYearsBc.create,
            editAcademicYear: academicYearsBc.edit,
            subjects: subjectsBc.index,
            createSubject: subjectsBc.create,
            showSubject: subjectsBc.show,
            editSubject: subjectsBc.edit,
            classes: classesBc.index,
            createClass: classesBc.create,
            showClass: classesBc.show,
            editClass: classesBc.edit,
            classAssessments: (classItem: ClassBcItem): BreadcrumbItem[] => [
                ...classesBc.show(classItem),
                { label: t('breadcrumbs.assessments') },
            ],
            classSubjectsList: (classItem: ClassBcItem): BreadcrumbItem[] => [
                ...classesBc.show(classItem),
                { label: t('breadcrumbs.class_subjects') },
            ],
            showClassesSubject: (
                classItem: ClassBcItem,
                classSubject: { subject?: { name?: string }; id: number },
            ): BreadcrumbItem[] => [
                ...classesBc.show(classItem),
                {
                    label: t('breadcrumbs.class_subjects'),
                    href: route('admin.classes.subjects', classItem.id),
                },
                { label: classSubject.subject?.name || `#${classSubject.id}` },
            ],
            enrollments: enrollmentsBc.index,
            createEnrollment: enrollmentsBc.create,
            showEnrollment: (enrollment: {
                id: number;
                student?: { name: string };
            }): BreadcrumbItem[] => [
                ...enrollmentsBc.index(),
                {
                    label: enrollment.student?.name || `#${enrollment.id}`,
                },
            ],
            teachers: teachersBc.index,
            teacherShow: (user: { name: string }): BreadcrumbItem[] => [
                ...teachersBc.index(),
                { label: user.name },
            ],
            adminShow: (user: { name: string }): BreadcrumbItem[] => [
                ...usersBc.index(),
                { label: user.name },
            ],
            classStudentsList: (classItem: ClassBcItem): BreadcrumbItem[] => [
                ...classesBc.show(classItem),
                { label: t('breadcrumbs.class_students') },
            ],
            showClassStudent: (
                classItem: ClassBcItem,
                enrollment: { id: number; student?: { name: string } },
            ): BreadcrumbItem[] => [
                ...classesBc.show(classItem),
                {
                    label: t('breadcrumbs.class_students'),
                    href: route('admin.classes.students.index', classItem.id),
                },
                {
                    label: enrollment.student?.name || `#${enrollment.id}`,
                    href: route('admin.classes.students.show', {
                        class: classItem.id,
                        enrollment: enrollment.id,
                    }),
                },
            ],
            classStudentAssignments: (
                classItem: ClassBcItem,
                enrollment: { id: number; student?: { name: string } },
            ): BreadcrumbItem[] => [
                ...classesBc.show(classItem),
                {
                    label: t('breadcrumbs.class_students'),
                    href: route('admin.classes.students.index', classItem.id),
                },
                {
                    label: enrollment.student?.name || `#${enrollment.id}`,
                    href: route('admin.classes.students.show', {
                        class: classItem.id,
                        enrollment: enrollment.id,
                    }),
                },
                { label: t('breadcrumbs.class_student_assignments') },
            ],
            classSubjects: classSubjectsBc.index,
            assessments: adminAssessmentsBc.index,
        },
        teacher: {
            classes: teacherClassesBc.index,
            showClass: teacherClassesBc.show,
            subjects: teacherSubjectsBc.index,
            showSubject: teacherSubjectsBc.show,
        },
        teacherAssessments: assessmentsBc.index,
        createTeacherAssessment: assessmentsBc.create,
        showTeacherAssessment: assessmentsBc.show,
        editTeacherAssessment: assessmentsBc.edit,
        assessmentGrade: (
            assessment: { id: number; title: string },
            _assignment: { id: number },
            student: { name: string },
        ): BreadcrumbItem[] => [
            ...assessmentsBc.show(assessment),
            { label: t('breadcrumbs.grading') + ': ' + student.name },
        ],
        assessmentReview: (
            assessment: { id: number; title: string },
            _assignment: { id: number },
            student: { name: string },
        ): BreadcrumbItem[] => [
            ...assessmentsBc.show(assessment),
            { label: t('breadcrumbs.review') + ': ' + student.name },
        ],
        adminAcademicYears: (): BreadcrumbItem[] => [
            { label: t('admin_pages.academic_years.title') },
        ],
        student: {
            assessments: (): BreadcrumbItem[] => [
                {
                    label: t('breadcrumbs.my_assessments'),
                    href: route('student.assessments.index'),
                },
            ],
            showAssessment: (assessment: { id: number; title: string }): BreadcrumbItem[] => [
                {
                    label: t('breadcrumbs.my_assessments'),
                    href: route('student.assessments.index'),
                },
                { label: assessment.title, href: route('student.assessments.show', assessment.id) },
            ],
            assessmentResults: (assessment: { id: number; title: string }): BreadcrumbItem[] => [
                {
                    label: t('breadcrumbs.my_assessments'),
                    href: route('student.assessments.index'),
                },
                { label: assessment.title, href: route('student.assessments.show', assessment.id) },
                { label: t('breadcrumbs.results') },
            ],
            assessmentWork: (assessment: { id: number; title: string }): BreadcrumbItem[] => [
                {
                    label: t('breadcrumbs.my_assessments'),
                    href: route('student.assessments.index'),
                },
                { label: assessment.title, href: route('student.assessments.show', assessment.id) },
                { label: t('breadcrumbs.homework') },
            ],
            enrollment: (): BreadcrumbItem[] => [
                { label: t('breadcrumbs.my_enrollment'), href: route('student.enrollment.show') },
            ],
            enrollmentHistory: (): BreadcrumbItem[] => [
                { label: t('breadcrumbs.my_enrollment'), href: route('student.enrollment.show') },
                { label: t('breadcrumbs.enrollment_history') },
            ],
            enrollmentClassmates: (): BreadcrumbItem[] => [
                { label: t('breadcrumbs.my_enrollment'), href: route('student.enrollment.show') },
                { label: t('breadcrumbs.classmates') },
            ],
        },
    };
}

/**
 * Hook providing localized breadcrumbs generator.
 * Replaces the static breadcrumbs export for React hook safety.
 */
export function useBreadcrumbs() {
    const { t } = useTranslations();
    return useMemo(() => createBreadcrumbs(t), [t]);
}
