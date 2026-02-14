import { route } from 'ziggy-js';
import { trans } from './translations';
import { BreadcrumbItem } from '@/Components/layout/Breadcrumb';
import { ClassSubject, AssessmentRouteContext } from '@/types';

const dashboard = (): BreadcrumbItem => ({
    label: trans('breadcrumbs.dashboard'),
    href: route('dashboard')
});

interface EntityConfig {
    labelKey: string;
    indexRoute: string;
    showRoute?: string;
}

interface NamedEntity {
    id: number;
    name?: string;
    title?: string;
}

/**
 * Creates standard CRUD breadcrumb methods for an entity
 */
function createEntityBreadcrumbs<T extends NamedEntity>(config: EntityConfig) {
    const { labelKey, indexRoute, showRoute } = config;

    const index = (): BreadcrumbItem[] => [
        dashboard(),
        { label: trans(labelKey), href: route(indexRoute) }
    ];

    const create = (): BreadcrumbItem[] => [
        ...index(),
        { label: trans('breadcrumbs.create') }
    ];

    const show = (item: T): BreadcrumbItem[] => {
        const label = item.name || item.title || `#${item.id}`;
        return [
            ...index(),
            { label, href: showRoute ? route(showRoute, item.id) : undefined }
        ];
    };

    const edit = (item: T): BreadcrumbItem[] => [
        ...show(item),
        { label: trans('breadcrumbs.edit') }
    ];

    return { index, create, show, edit };
}

/**
 * Creates simple breadcrumbs for entities without show page
 */
function createSimpleBreadcrumbs(labelKey: string, indexRoute: string) {
    const index = (): BreadcrumbItem[] => [
        dashboard(),
        { label: trans(labelKey), href: route(indexRoute) }
    ];

    const create = (): BreadcrumbItem[] => [
        ...index(),
        { label: trans('breadcrumbs.create') }
    ];

    const edit = (name: string): BreadcrumbItem[] => [
        ...index(),
        { label: name }
    ];

    return { index, create, edit };
}

const levelsBc = createSimpleBreadcrumbs('breadcrumbs.levels', 'admin.levels.index');
const usersBc = createSimpleBreadcrumbs('breadcrumbs.users', 'admin.users.index');
const rolesBc = createSimpleBreadcrumbs('breadcrumbs.roles_permissions', 'admin.roles.index');

const academicYearsBc = createEntityBreadcrumbs<{ id: number; name: string }>({
    labelKey: 'breadcrumbs.academic_years',
    indexRoute: 'admin.academic-years.index',
    showRoute: 'admin.academic-years.show'
});

const subjectsBc = createEntityBreadcrumbs<{ id: number; name: string }>({
    labelKey: 'breadcrumbs.subjects',
    indexRoute: 'admin.subjects.index',
    showRoute: 'admin.subjects.show'
});

const classesBc = createEntityBreadcrumbs<{ id: number; name?: string }>({
    labelKey: 'breadcrumbs.classes',
    indexRoute: 'admin.classes.index',
    showRoute: 'admin.classes.show'
});

const enrollmentsBc = createEntityBreadcrumbs<{ id: number; student?: { name: string } }>({
    labelKey: 'breadcrumbs.enrollments',
    indexRoute: 'admin.enrollments.index',
    showRoute: 'admin.enrollments.show'
});

const classSubjectsBc = {
    index: (): BreadcrumbItem[] => [
        dashboard(),
        { label: trans('breadcrumbs.class_subjects'), href: route('admin.class-subjects.index') }
    ],
    show: (classSubject: ClassSubject): BreadcrumbItem[] => {
        const levelInfo = classSubject.class?.level
            ? `${classSubject.class.level.name} (${classSubject.class.level.description})`
            : '';
        return [
            dashboard(),
            { label: trans('breadcrumbs.class_subjects'), href: route('admin.class-subjects.index') },
            { label: `${classSubject.class?.name || ''}, ${levelInfo} - ${classSubject.subject?.name || ''}` }
        ];
    }
};

const teacherClassesBc = {
    index: (): BreadcrumbItem[] => [
        dashboard(),
        { label: trans('breadcrumbs.classes'), href: route('teacher.classes.index') }
    ],
    show: (classItem: { id: number; name?: string }): BreadcrumbItem[] => [
        dashboard(),
        { label: trans('breadcrumbs.classes'), href: route('teacher.classes.index') },
        { label: classItem.name || '' }
    ]
};

const teacherSubjectsBc = {
    index: (): BreadcrumbItem[] => [
        dashboard(),
        { label: trans('breadcrumbs.subjects'), href: route('teacher.subjects.index') }
    ],
    show: (subject: { id: number; name?: string }): BreadcrumbItem[] => [
        dashboard(),
        { label: trans('breadcrumbs.subjects'), href: route('teacher.subjects.index') },
        { label: subject.name || '' }
    ]
};

const assessmentsBc = createEntityBreadcrumbs<{ id: number; title: string }>({
    labelKey: 'breadcrumbs.assessments',
    indexRoute: 'teacher.assessments.index',
    showRoute: 'teacher.assessments.show'
});

const adminAssessmentsBc = {
    index: (): BreadcrumbItem[] => [
        dashboard(),
        { label: trans('breadcrumbs.assessments'), href: route('admin.assessments.index') }
    ],
};

/**
 * Dynamic breadcrumbs based on AssessmentRouteContext for unified pages.
 */
const dynamicAssessmentBc = {
    index: (ctx: AssessmentRouteContext): BreadcrumbItem[] => [
        dashboard(),
        { label: trans('breadcrumbs.assessments'), href: route(ctx.backRoute) }
    ],
    show: (ctx: AssessmentRouteContext, assessment: { id: number; title: string }): BreadcrumbItem[] => [
        dashboard(),
        { label: trans('breadcrumbs.assessments'), href: route(ctx.backRoute) },
        { label: assessment.title, href: route(ctx.showRoute, assessment.id) }
    ],
    grade: (
        ctx: AssessmentRouteContext,
        assessment: { id: number; title: string },
        student: { name: string }
    ): BreadcrumbItem[] => [
            ...dynamicAssessmentBc.show(ctx, assessment),
            { label: trans('breadcrumbs.grading') + ': ' + student.name }
        ],
    review: (
        ctx: AssessmentRouteContext,
        assessment: { id: number; title: string },
        student: { name: string }
    ): BreadcrumbItem[] => [
            ...dynamicAssessmentBc.show(ctx, assessment),
            { label: trans('breadcrumbs.review') + ': ' + student.name }
        ],
};

export const breadcrumbs = {
    dashboard: (): BreadcrumbItem[] => [dashboard()],

    // Dynamic assessment breadcrumbs (routeContext-based)
    assessment: dynamicAssessmentBc,

    // Users (simple: index, create, edit by name)
    users: usersBc.index,
    userCreate: usersBc.create,
    userEdit: usersBc.edit,
    teacherShow: (user: { name: string }): BreadcrumbItem[] => [...usersBc.index(), { label: user.name }],

    // Levels (simple: index, create, edit by name)
    levels: levelsBc.index,
    levelCreate: levelsBc.create,
    levelEdit: levelsBc.edit,

    // Roles (config only: index, edit)
    roles: rolesBc.index,
    roleEdit: rolesBc.edit,

    // Admin entities with full CRUD
    admin: {
        academicYears: academicYearsBc.index,
        createAcademicYear: academicYearsBc.create,
        showAcademicYear: academicYearsBc.show,
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
        showEnrollment: (enrollment: { id: number; student?: { name: string } }): BreadcrumbItem[] => [
            ...enrollmentsBc.index(),
            { label: enrollment.student?.name || `#${enrollment.id}` }
        ],

        classSubjects: classSubjectsBc.index,
        showClassSubject: classSubjectsBc.show,

        assessments: adminAssessmentsBc.index,
    },

    // Teacher entities
    teacher: {
        classes: teacherClassesBc.index,
        showClass: teacherClassesBc.show,
        subjects: teacherSubjectsBc.index,
        showSubject: teacherSubjectsBc.show,
    },

    // Teacher assessments (legacy naming)
    teacherAssessments: assessmentsBc.index,
    createTeacherAssessment: assessmentsBc.create,
    showTeacherAssessment: assessmentsBc.show,
    editTeacherAssessment: assessmentsBc.edit,

    // Assessment grading/review (nested under assessment)
    assessmentGrade: (
        assessment: { id: number; title: string },
        _assignment: { id: number },
        student: { name: string }
    ): BreadcrumbItem[] => [
            ...assessmentsBc.show(assessment),
            { label: trans('breadcrumbs.grading') + ': ' + student.name }
        ],
    assessmentReview: (
        assessment: { id: number; title: string },
        _assignment: { id: number },
        student: { name: string }
    ): BreadcrumbItem[] => [
            ...assessmentsBc.show(assessment),
            { label: trans('breadcrumbs.review') + ': ' + student.name }
        ],

    // Legacy alias
    adminAcademicYears: (): BreadcrumbItem[] => [
        dashboard(),
        { label: trans('admin_pages.academic_years.title') }
    ],

    // Student assessments
    student: {
        assessments: (): BreadcrumbItem[] => [
            dashboard(),
            { label: trans('breadcrumbs.my_assessments'), href: route('student.assessments.index') }
        ],
        showAssessment: (assessment: { id: number; title: string }): BreadcrumbItem[] => [
            dashboard(),
            { label: trans('breadcrumbs.my_assessments'), href: route('student.assessments.index') },
            { label: assessment.title, href: route('student.assessments.show', assessment.id) }
        ],
        assessmentResults: (assessment: { id: number; title: string }): BreadcrumbItem[] => [
            dashboard(),
            { label: trans('breadcrumbs.my_assessments'), href: route('student.assessments.index') },
            { label: assessment.title, href: route('student.assessments.show', assessment.id) },
            { label: trans('breadcrumbs.results') }
        ],
        assessmentWork: (assessment: { id: number; title: string }): BreadcrumbItem[] => [
            dashboard(),
            { label: trans('breadcrumbs.my_assessments'), href: route('student.assessments.index') },
            { label: assessment.title, href: route('student.assessments.show', assessment.id) },
            { label: trans('breadcrumbs.homework') }
        ],
        enrollment: (): BreadcrumbItem[] => [
            dashboard(),
            { label: trans('breadcrumbs.my_enrollment'), href: route('student.enrollment.show') }
        ],
        enrollmentHistory: (): BreadcrumbItem[] => [
            dashboard(),
            { label: trans('breadcrumbs.my_enrollment'), href: route('student.enrollment.show') },
            { label: trans('breadcrumbs.enrollment_history') }
        ],
        enrollmentClassmates: (): BreadcrumbItem[] => [
            dashboard(),
            { label: trans('breadcrumbs.my_enrollment'), href: route('student.enrollment.show') },
            { label: trans('breadcrumbs.classmates') }
        ],
    },
};

export const navRoutes = {
    dashboard: () => route('dashboard'),

    // Student Routes
    studentAssessments: () => route('student.assessments.index'),
    studentEnrollment: () => route('student.enrollment.show'),

    // Teacher Routes
    teacherDashboard: () => route('teacher.dashboard'),
    teacherAssessments: () => route('teacher.assessments.index'),
    teacherClasses: () => route('teacher.classes.index'),
    teacherSubjects: () => route('teacher.subjects.index'),

    // Admin Routes
    adminAcademicYears: () => route('admin.academic-years.index'),
    adminSubjects: () => route('admin.subjects.index'),
    adminClasses: () => route('admin.classes.index'),
    adminEnrollments: () => route('admin.enrollments.index'),
    adminClassSubjects: () => route('admin.class-subjects.index'),
    adminAssessments: () => route('admin.assessments.index'),

    // System Routes
    users: () => route('admin.users.index'),
    levels: () => route('admin.levels.index'),
    roles: () => route('admin.roles.index'),

    // Profile & Auth
    profile: () => route('profile'),
    logout: () => route('logout'),
};
