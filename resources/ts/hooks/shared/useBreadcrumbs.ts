import { useMemo } from 'react';
import { useTranslations } from './useTranslations';
import { route } from 'ziggy-js';
import { type BreadcrumbItem } from '@/Components/layout/Breadcrumb';
import { type ClassSubject, type AssessmentRouteContext } from '@/types';

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
    const dashboard = (): BreadcrumbItem => ({
        label: t('breadcrumbs.dashboard'),
        href: route('dashboard'),
    });

    function createEntityBreadcrumbs<T extends NamedEntity>(config: {
        labelKey: string;
        indexRoute: string;
        showRoute?: string;
    }) {
        const { labelKey, indexRoute, showRoute } = config;

        const index = (): BreadcrumbItem[] => [
            dashboard(),
            { label: t(labelKey), href: route(indexRoute) },
        ];

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
        const index = (): BreadcrumbItem[] => [
            dashboard(),
            { label: t(labelKey), href: route(indexRoute) },
        ];

        const create = (): BreadcrumbItem[] => [...index(), { label: t('breadcrumbs.create') }];

        const edit = (name: string): BreadcrumbItem[] => [...index(), { label: name }];

        return { index, create, edit };
    }

    const levelsBc = createSimpleBreadcrumbs('breadcrumbs.levels', 'admin.levels.index');
    const usersBc = createSimpleBreadcrumbs('breadcrumbs.users', 'admin.users.index');
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

    const classesBc = createEntityBreadcrumbs<{ id: number; name?: string }>({
        labelKey: 'breadcrumbs.classes',
        indexRoute: 'admin.classes.index',
        showRoute: 'admin.classes.show',
    });

    const enrollmentsBc = createEntityBreadcrumbs<{ id: number; student?: { name: string } }>({
        labelKey: 'breadcrumbs.enrollments',
        indexRoute: 'admin.enrollments.index',
        showRoute: 'admin.enrollments.show',
    });

    const classSubjectsBc = {
        index: (): BreadcrumbItem[] => [
            dashboard(),
            { label: t('breadcrumbs.class_subjects'), href: route('admin.class-subjects.index') },
        ],
        show: (classSubject: ClassSubject): BreadcrumbItem[] => {
            const levelInfo = classSubject.class?.level
                ? `${classSubject.class.level.name} (${classSubject.class.level.description})`
                : '';
            return [
                dashboard(),
                {
                    label: t('breadcrumbs.class_subjects'),
                    href: route('admin.class-subjects.index'),
                },
                {
                    label: `${classSubject.class?.name || ''}, ${levelInfo} - ${classSubject.subject?.name || ''}`,
                },
            ];
        },
    };

    const teacherClassesBc = {
        index: (): BreadcrumbItem[] => [
            dashboard(),
            { label: t('breadcrumbs.classes'), href: route('teacher.classes.index') },
        ],
        show: (classItem: { id: number; name?: string }): BreadcrumbItem[] => [
            dashboard(),
            { label: t('breadcrumbs.classes'), href: route('teacher.classes.index') },
            { label: classItem.name || '' },
        ],
    };

    const teacherSubjectsBc = {
        index: (): BreadcrumbItem[] => [
            dashboard(),
            { label: t('breadcrumbs.subjects'), href: route('teacher.subjects.index') },
        ],
        show: (subject: { id: number; name?: string }): BreadcrumbItem[] => [
            dashboard(),
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
            dashboard(),
            { label: t('breadcrumbs.assessments'), href: route('admin.assessments.index') },
        ],
    };

    const dynamicAssessmentBc = {
        index: (ctx: AssessmentRouteContext): BreadcrumbItem[] => [
            dashboard(),
            { label: t('breadcrumbs.assessments'), href: route(ctx.backRoute) },
        ],
        show: (
            ctx: AssessmentRouteContext,
            assessment: { id: number; title: string },
        ): BreadcrumbItem[] => [
            dashboard(),
            { label: t('breadcrumbs.assessments'), href: route(ctx.backRoute) },
            { label: assessment.title, href: route(ctx.showRoute, assessment.id) },
        ],
        grade: (
            ctx: AssessmentRouteContext,
            assessment: { id: number; title: string },
            student: { name: string },
        ): BreadcrumbItem[] => [
            ...dynamicAssessmentBc.show(ctx, assessment),
            { label: t('breadcrumbs.grading') + ': ' + student.name },
        ],
        review: (
            ctx: AssessmentRouteContext,
            assessment: { id: number; title: string },
            student: { name: string },
        ): BreadcrumbItem[] => [
            ...dynamicAssessmentBc.show(ctx, assessment),
            { label: t('breadcrumbs.review') + ': ' + student.name },
        ],
    };

    return {
        dashboard: (): BreadcrumbItem[] => [dashboard()],
        assessment: dynamicAssessmentBc,
        users: usersBc.index,
        userCreate: usersBc.create,
        userEdit: usersBc.edit,
        teacherShow: (user: { name: string }): BreadcrumbItem[] => [
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
            enrollments: enrollmentsBc.index,
            createEnrollment: enrollmentsBc.create,
            showEnrollment: (enrollment: {
                id: number;
                student?: { name: string };
            }): BreadcrumbItem[] => [
                ...enrollmentsBc.index(),
                { label: enrollment.student?.name || `#${enrollment.id}` },
            ],
            classSubjects: classSubjectsBc.index,
            showClassSubject: classSubjectsBc.show,
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
            dashboard(),
            { label: t('admin_pages.academic_years.title') },
        ],
        student: {
            assessments: (): BreadcrumbItem[] => [
                dashboard(),
                {
                    label: t('breadcrumbs.my_assessments'),
                    href: route('student.assessments.index'),
                },
            ],
            showAssessment: (assessment: { id: number; title: string }): BreadcrumbItem[] => [
                dashboard(),
                {
                    label: t('breadcrumbs.my_assessments'),
                    href: route('student.assessments.index'),
                },
                { label: assessment.title, href: route('student.assessments.show', assessment.id) },
            ],
            assessmentResults: (assessment: { id: number; title: string }): BreadcrumbItem[] => [
                dashboard(),
                {
                    label: t('breadcrumbs.my_assessments'),
                    href: route('student.assessments.index'),
                },
                { label: assessment.title, href: route('student.assessments.show', assessment.id) },
                { label: t('breadcrumbs.results') },
            ],
            assessmentWork: (assessment: { id: number; title: string }): BreadcrumbItem[] => [
                dashboard(),
                {
                    label: t('breadcrumbs.my_assessments'),
                    href: route('student.assessments.index'),
                },
                { label: assessment.title, href: route('student.assessments.show', assessment.id) },
                { label: t('breadcrumbs.homework') },
            ],
            enrollment: (): BreadcrumbItem[] => [
                dashboard(),
                { label: t('breadcrumbs.my_enrollment'), href: route('student.enrollment.show') },
            ],
            enrollmentHistory: (): BreadcrumbItem[] => [
                dashboard(),
                { label: t('breadcrumbs.my_enrollment'), href: route('student.enrollment.show') },
                { label: t('breadcrumbs.enrollment_history') },
            ],
            enrollmentClassmates: (): BreadcrumbItem[] => [
                dashboard(),
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
