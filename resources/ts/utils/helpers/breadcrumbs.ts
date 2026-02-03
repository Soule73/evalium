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

// Breadcrumb index des groupes
const groupIndex = (): BreadcrumbItem[] => [
    dashboardBreadcrumb(),
    { label: trans('breadcrumbs.groups'), href: route('groups.index') },
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

// Breadcrumb index des examens
const examIndex = (): BreadcrumbItem[] => [
    dashboardBreadcrumb(),
    { label: trans('breadcrumbs.exams'), href: route('exams.index') },
];

// Breadcrumb index des examens pour étudiants
const studentExamIndex = (): BreadcrumbItem[] => [
    dashboardBreadcrumb(),
    { label: trans('breadcrumbs.my_groups'), href: route('student.exams.index') },
];

// Breadcrumb détails d'un groupe étudiant
const studentGroupShowBreadcrumb = (groupName: string, groupId: number): BreadcrumbItem[] => (
    [
        ...studentExamIndex(),
        { label: groupName, href: route('student.exams.group.show', { group: groupId }) },
    ]
);

// Breadcrumb details d'un examen 
const examShowBreadcrumb = (examTitle: string, examId: number): BreadcrumbItem[] => (
    [
        ...examIndex(),
        { label: examTitle, href: route('exams.show', { exam: examId }) },
    ]
);

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

    groups: (): BreadcrumbItem[] => groupIndex(),

    groupCreate: (): BreadcrumbItem[] => [
        ...groupIndex(),
        { label: trans('breadcrumbs.create') }
    ],

    groupEdit: (groupName: string): BreadcrumbItem[] => [
        ...groupIndex(),
        { label: groupName }
    ],
    groupAssignStudents: (groupName: string, id: number): BreadcrumbItem[] => [
        ...groupIndex(),
        { label: groupName, href: route('groups.show', { group: id }) },
        { label: trans('breadcrumbs.assign_students') }
    ],

    groupShow: (groupName: string): BreadcrumbItem[] => [
        ...groupIndex(),
        { label: groupName }
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

    examCreate: (): BreadcrumbItem[] => [
        ...examIndex(),
        { label: trans('breadcrumbs.create') }
    ],

    examEdit: (examTitle: string, examId: number): BreadcrumbItem[] => [
        ...examShowBreadcrumb(examTitle, examId),
        { label: trans('breadcrumbs.edit') }
    ],

    examShow: (examTitle: string): BreadcrumbItem[] => [
        ...examIndex(),
        { label: examTitle }
    ],

    examAssign: (examTitle: string, examId: number): BreadcrumbItem[] => [
        ...examShowBreadcrumb(examTitle, examId),
        { label: trans('breadcrumbs.assign') }
    ],

    examAssignments: (examTitle: string, examId: number): BreadcrumbItem[] => [
        ...examShowBreadcrumb(examTitle, examId),
        { label: trans('breadcrumbs.exam_groups') }
    ],

    examGroupShow: (examTitle: string, examId: number, groupName: string): BreadcrumbItem[] => [
        ...examShowBreadcrumb(examTitle, examId),
        { label: trans('breadcrumbs.exam_groups'), href: route('exams.groups', { exam: examId }) },
        { label: groupName }
    ],
    examGroupSubmission: (
        examId: number,
        groupId: number,
        examTitle: string,
        groupName: string,
        studentFullName: string): BreadcrumbItem[] => [
            ...examShowBreadcrumb(examTitle, examId),
            { label: trans('breadcrumbs.exam_groups'), href: route('exams.groups', { exam: examId }) },
            { label: groupName, href: route('exams.group.show', { exam: examId, group: groupId }) },
            { label: studentFullName }
        ],
    examGroupReview: (
        examId: number,
        groupId: number,
        studentId: number,
        examTitle: string,
        groupName: string,
        studentFullName: string): BreadcrumbItem[] => [
            ...examShowBreadcrumb(examTitle, examId),
            { label: trans('breadcrumbs.exam_groups'), href: route('exams.groups', { exam: examId }) },
            { label: groupName, href: route('exams.group.show', { exam: examId, group: groupId }) },
            { label: studentFullName, href: route('exams.submissions', { exam: examId, group: groupId, student: studentId }) },
            { label: trans('breadcrumbs.correction') }
        ],

    exams: (): BreadcrumbItem[] => examIndex(),

    studentExams: (): BreadcrumbItem[] => studentExamIndex(),

    studentGroupShow: (groupName: string): BreadcrumbItem[] => [
        ...studentExamIndex(),
        { label: groupName }
    ],

    studentExamShow: (groupName: string, groupId: number, examTitle: string): BreadcrumbItem[] => [
        ...studentGroupShowBreadcrumb(groupName, groupId),
        { label: examTitle }
    ],

    studentExamTake: (groupName: string, groupId: number, examTitle: string): BreadcrumbItem[] => [
        ...studentGroupShowBreadcrumb(groupName, groupId),
        { label: examTitle },
        { label: trans('breadcrumbs.take_exam') }
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
};

// Routes de navigation principales (utilisées par le Sidebar)
export const navRoutes = {
    dashboard: () => route('dashboard'),

    // Student MCD Routes
    studentAssessments: () => route('student.mcd.assessments.index'),
    studentEnrollment: () => route('student.mcd.enrollment.show'),

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

    // Legacy Routes (kept for backward compatibility)
    exams: () => route('exams.index'),
    users: () => route('users.index'),
    groups: () => route('groups.index'),
    levels: () => route('levels.index'),
    roles: () => route('roles.index'),

    // Profile & Auth
    profile: () => route('profile'),
    logout: () => route('logout'),
};
