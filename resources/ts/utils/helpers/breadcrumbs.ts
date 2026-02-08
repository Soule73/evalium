import { route } from 'ziggy-js';
import { trans } from './translations';
import { BreadcrumbItem } from '@/Components/layout/Breadcrumb';
import { ClassSubject } from '@/types';

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

const assessmentsBc = createEntityBreadcrumbs<{ id: number; title: string }>({
    labelKey: 'breadcrumbs.assessments',
    indexRoute: 'teacher.assessments.index',
    showRoute: 'teacher.assessments.show'
});

export const breadcrumbs = {
    dashboard: (): BreadcrumbItem[] => [dashboard()],

    // Users (simple: index, create, edit by name)
    users: usersBc.index,
    userCreate: usersBc.create,
    userEdit: usersBc.edit,
    studentShow: (user: { name: string }): BreadcrumbItem[] => [...usersBc.index(), { label: user.name }],
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
    },

    // Teacher entities
    teacher: {
        classes: teacherClassesBc.index,
        showClass: teacherClassesBc.show,
    },

    // Teacher assessments (legacy naming)
    teacherAssessments: assessmentsBc.index,
    createTeacherAssessment: assessmentsBc.create,
    showTeacherAssessment: assessmentsBc.show,
    editTeacherAssessment: assessmentsBc.edit,

    // Grading (nested under assessment)
    gradingIndex: (assessment: { id: number; title: string }): BreadcrumbItem[] => [
        ...assessmentsBc.show(assessment),
        { label: trans('breadcrumbs.grading') }
    ],
    gradingShow: (assessment: { id: number; title: string }, student: { name: string }): BreadcrumbItem[] => [
        ...assessmentsBc.show(assessment),
        { label: trans('breadcrumbs.grading'), href: route('teacher.grading.index', assessment.id) },
        { label: student.name }
    ],

    // Legacy alias
    adminAcademicYears: (): BreadcrumbItem[] => [
        dashboard(),
        { label: trans('admin_pages.academic_years.title') }
    ],
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
    teacherGrading: () => route('teacher.grading.index', { assessment: '__assessment__' }),

    // Admin Routes
    adminAcademicYears: () => route('admin.academic-years.index'),
    adminSubjects: () => route('admin.subjects.index'),
    adminClasses: () => route('admin.classes.index'),
    adminEnrollments: () => route('admin.enrollments.index'),
    adminClassSubjects: () => route('admin.class-subjects.index'),

    // System Routes
    users: () => route('admin.users.index'),
    levels: () => route('admin.levels.index'),
    roles: () => route('admin.roles.index'),

    // Profile & Auth
    profile: () => route('profile'),
    logout: () => route('logout'),
};
