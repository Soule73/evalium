import { route } from 'ziggy-js';
import { trans } from './translations';
import { BreadcrumbItem } from '@/Components/layout/Breadcrumb';

// Breadcrumb tableau de board
const dashboardBreadcrumb = (): BreadcrumbItem => ({
    label: trans('breadcrumbs.dashboard'),
    href: route('dashboard')
});

// Breadcrumb index des utilisateurs
const userIndex = (): BreadcrumbItem[] => [
    dashboardBreadcrumb(),
    { label: trans('breadcrumbs.users'), href: route('users.index') },
];

// Breadcrumb index des niveaux
const levelIndex = (): BreadcrumbItem[] => [
    dashboardBreadcrumb(),
    { label: trans('breadcrumbs.levels'), href: route('levels.index') },
];

// Breadcrumb index des rôles et permissions
const roleIndex = (): BreadcrumbItem[] => [
    dashboardBreadcrumb(),
    { label: trans('breadcrumbs.roles_permissions'), href: route('roles.index') },
];

// Helper pour créer des breadcrumbs
export const breadcrumbs = {
    dashboard: (): BreadcrumbItem[] => [
        dashboardBreadcrumb()
    ],

    users: (): BreadcrumbItem[] => userIndex(),

    userCreate: (): BreadcrumbItem[] => [
        ...userIndex(),
        { label: trans('breadcrumbs.create') }
    ],

    userEdit: (userName: string): BreadcrumbItem[] => [
        ...userIndex(),
        { label: userName }
    ],

    levels: (): BreadcrumbItem[] => levelIndex(),

    levelCreate: (): BreadcrumbItem[] => [
        ...levelIndex(),
        { label: trans('breadcrumbs.create') }
    ],

    levelEdit: (levelName: string): BreadcrumbItem[] => [
        ...levelIndex(),
        { label: levelName }
    ],

    roles: (): BreadcrumbItem[] => roleIndex(),

    roleCreate: (): BreadcrumbItem[] => [
        ...roleIndex(),
        { label: trans('breadcrumbs.create') }
    ],

    roleEdit: (roleName: string): BreadcrumbItem[] => [
        ...roleIndex(),
        { label: roleName }
    ],
    studentShow: (user: { name: string }): BreadcrumbItem[] => [
        ...userIndex(),
        { label: user.name }
    ],
    teacherShow: (user: { name: string }): BreadcrumbItem[] => [
        ...userIndex(),
        { label: user.name }
    ],

    admin: {
        academicYears: (): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.academic_years'), href: route('admin.academic-years.index') }
        ],

        createAcademicYear: (): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.academic_years'), href: route('admin.academic-years.index') },
            { label: trans('breadcrumbs.create') }
        ],

        showAcademicYear: (year: { id: number; name: string }): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.academic_years'), href: route('admin.academic-years.index') },
            { label: year.name, href: route('admin.academic-years.show', year.id) }
        ],

        editAcademicYear: (year: { id: number; name: string }): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.academic_years'), href: route('admin.academic-years.index') },
            { label: year.name, href: route('admin.academic-years.show', year.id) },
            { label: trans('breadcrumbs.edit') }
        ],

        subjects: (): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.subjects'), href: route('admin.subjects.index') }
        ],

        createSubject: (): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.subjects'), href: route('admin.subjects.index') },
            { label: trans('breadcrumbs.create') }
        ],

        showSubject: (subject: { id: number; name: string }): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.subjects'), href: route('admin.subjects.index') },
            { label: subject.name, href: route('admin.subjects.show', subject.id) }
        ],

        editSubject: (subject: { id: number; name: string }): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.subjects'), href: route('admin.subjects.index') },
            { label: subject.name, href: route('admin.subjects.show', subject.id) },
            { label: trans('breadcrumbs.edit') }
        ],

        classes: (): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.classes'), href: route('admin.classes.index') }
        ],

        createClass: (): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.classes'), href: route('admin.classes.index') },
            { label: trans('breadcrumbs.create') }
        ],

        showClass: (classItem: { id: number; name?: string; display_name?: string }): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.classes'), href: route('admin.classes.index') },
            { label: classItem.display_name || classItem.name || '', href: route('admin.classes.show', classItem.id) }
        ],

        editClass: (classItem: { id: number; name?: string; display_name?: string }): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.classes'), href: route('admin.classes.index') },
            { label: classItem.display_name || classItem.name || '', href: route('admin.classes.show', classItem.id) },
            { label: trans('breadcrumbs.edit') }
        ],

        enrollments: (): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.enrollments'), href: route('admin.enrollments.index') }
        ],

        createEnrollment: (): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.enrollments'), href: route('admin.enrollments.index') },
            { label: trans('breadcrumbs.create') }
        ],

        showEnrollment: (enrollment: { id: number; student?: { name: string } }): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.enrollments'), href: route('admin.enrollments.index') },
            { label: enrollment.student?.name || `#${enrollment.id}`, href: route('admin.enrollments.show', enrollment.id) }
        ],

        transferEnrollment: (enrollment: { id: number; student?: { name: string } }): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.enrollments'), href: route('admin.enrollments.index') },
            { label: enrollment.student?.name || `#${enrollment.id}`, href: route('admin.enrollments.show', enrollment.id) },
            { label: trans('breadcrumbs.transfer') }
        ],

        classSubjects: (): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.class_subjects'), href: route('admin.class-subjects.index') }
        ],

        createClassSubject: (): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.class_subjects'), href: route('admin.class-subjects.index') },
            { label: trans('breadcrumbs.create') }
        ],

        showClassSubject: (classSubject: { id: number; subject?: { name: string }; class?: { name?: string; display_name?: string } }): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.class_subjects'), href: route('admin.class-subjects.index') },
            { label: `${classSubject.class?.display_name || classSubject.class?.name || ''} - ${classSubject.subject?.name || ''}`, href: route('admin.class-subjects.show', classSubject.id) }
        ],

        replaceTeacherClassSubject: (classSubject: { id: number; subject?: { name: string }; class?: { name?: string; display_name?: string } }): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.class_subjects'), href: route('admin.class-subjects.index') },
            { label: `${classSubject.class?.display_name || classSubject.class?.name || ''} - ${classSubject.subject?.name || ''}`, href: route('admin.class-subjects.show', classSubject.id) },
            { label: trans('breadcrumbs.replace_teacher') }
        ],

        updateCoefficientClassSubject: (classSubject: { id: number; subject?: { name: string }; class?: { name?: string; display_name?: string } }): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.class_subjects'), href: route('admin.class-subjects.index') },
            { label: `${classSubject.class?.display_name || classSubject.class?.name || ''} - ${classSubject.subject?.name || ''}`, href: route('admin.class-subjects.show', classSubject.id) },
            { label: trans('breadcrumbs.update_coefficient') }
        ],
    },

    teacherAssessments: (): BreadcrumbItem[] => [
        dashboardBreadcrumb(),
        { label: trans('breadcrumbs.assessments'), href: route('teacher.assessments.index') }
    ],

    createTeacherAssessment: (): BreadcrumbItem[] => [
        dashboardBreadcrumb(),
        { label: trans('breadcrumbs.assessments'), href: route('teacher.assessments.index') },
        { label: trans('breadcrumbs.create') }
    ],

    showTeacherAssessment: (assessment: { id: number; title: string }): BreadcrumbItem[] => [
        dashboardBreadcrumb(),
        { label: trans('breadcrumbs.assessments'), href: route('teacher.assessments.index') },
        { label: assessment.title, href: route('teacher.assessments.show', assessment.id) }
    ],

    gradingIndex: (assessment: { id: number; title: string }): BreadcrumbItem[] => [
        dashboardBreadcrumb(),
        { label: trans('breadcrumbs.assessments'), href: route('teacher.assessments.index') },
        { label: assessment.title, href: route('teacher.assessments.show', assessment.id) },
        { label: trans('breadcrumbs.grading') }
    ],

    gradingShow: (assessment: { id: number; title: string }, student: { name: string }): BreadcrumbItem[] => [
        dashboardBreadcrumb(),
        { label: trans('breadcrumbs.assessments'), href: route('teacher.assessments.index') },
        { label: assessment.title, href: route('teacher.assessments.show', assessment.id) },
        { label: trans('breadcrumbs.grading'), href: route('teacher.grading.index', assessment.id) },
        { label: student.name }
    ],

    editTeacherAssessment: (assessment: { id: number; title: string }): BreadcrumbItem[] => [
        dashboardBreadcrumb(),
        { label: trans('breadcrumbs.assessments'), href: route('teacher.assessments.index') },
        { label: assessment.title, href: route('teacher.assessments.show', assessment.id) },
        { label: trans('breadcrumbs.edit') }
    ],

    teacher: {
        classes: (): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.classes'), href: route('teacher.classes.index') }
        ],

        showClass: (classItem: { id: number; name?: string; display_name?: string }): BreadcrumbItem[] => [
            dashboardBreadcrumb(),
            { label: trans('breadcrumbs.classes'), href: route('teacher.classes.index') },
            { label: classItem.display_name || classItem.name || '', href: route('teacher.classes.show', classItem.id) }
        ],
    },

    adminAcademicYears: (): BreadcrumbItem[] => [
        dashboardBreadcrumb(),
        { label: trans('admin_pages.academic_years.title') }
    ],
};

// Routes de navigation principales (utilisées par le Sidebar)
export const navRoutes = {
    dashboard: () => route('dashboard'),

    // Student MCD Routes
    studentAssessments: () => route('student.assessments.index'),
    studentEnrollment: () => route('student.enrollment.show'),

    // Teacher MCD Routes
    teacherDashboard: () => route('teacher.dashboard'),
    teacherAssessments: () => route('teacher.assessments.index'),
    teacherClasses: () => route('teacher.classes.index'),
    teacherGrading: () => route('teacher.grading.index', { assessment: '__assessment__' }), // Dynamic route

    // Admin MCD Routes
    adminAcademicYears: () => route('admin.academic-years.index'),
    adminSubjects: () => route('admin.subjects.index'),
    adminClasses: () => route('admin.classes.index'),
    adminEnrollments: () => route('admin.enrollments.index'),
    adminClassSubjects: () => route('admin.class-subjects.index'),

    // System Routes
    users: () => route('users.index'),
    levels: () => route('levels.index'),
    roles: () => route('roles.index'),

    // Profile & Auth
    profile: () => route('profile'),
    logout: () => route('logout'),
};
