import { route } from 'ziggy-js';
import { BreadcrumbItem } from '@/Components/Breadcrumb';

// Breadcrumb tableau de board
const dashboardBreadcrumb = (): BreadcrumbItem => ({
    label: 'Tableau de bord',
    href: route('dashboard')
});

// Breadcrumb index des utilisateurs
const userIndex = (): BreadcrumbItem[] => [
    dashboardBreadcrumb(),
    { label: 'Utilisateurs', href: route('users.index') },
];

// Breadcrumb index des groupes
const groupIndex = (): BreadcrumbItem[] => [
    dashboardBreadcrumb(),
    { label: 'Groupes', href: route('groups.index') },
];

// Breadcrumb index des niveaux
const levelIndex = (): BreadcrumbItem[] => [
    dashboardBreadcrumb(),
    { label: 'Niveaux', href: route('levels.index') },
];

// Breadcrumb index des rôles et permissions
const roleIndex = (): BreadcrumbItem[] => [
    dashboardBreadcrumb(),
    { label: 'Rôles & Permissions', href: route('roles.index') },
];

// Breadcrumb index des examens
const examIndex = (): BreadcrumbItem[] => [
    dashboardBreadcrumb(),
    { label: 'Examens', href: route('exams.index') },
];

// Breadcrumb index des examens pour étudiants
const studentExamIndex = (): BreadcrumbItem[] => [
    dashboardBreadcrumb(),
    { label: 'Mes Groupes', href: route('student.exams.index') },
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
        { label: 'Créer' }
    ],

    userEdit: (userName: string): BreadcrumbItem[] => [
        ...userIndex(),
        { label: userName }
    ],

    groups: (): BreadcrumbItem[] => groupIndex(),

    groupCreate: (): BreadcrumbItem[] => [
        ...groupIndex(),
        { label: 'Créer' }
    ],

    groupEdit: (groupName: string): BreadcrumbItem[] => [
        ...groupIndex(),
        { label: groupName }
    ],
    groupAssignStudents: (groupName: string, id: number): BreadcrumbItem[] => [
        ...groupIndex(),
        { label: groupName, href: route('groups.show', { group: id }) },
        { label: 'Assigner des étudiants' }
    ],

    groupShow: (groupName: string): BreadcrumbItem[] => [
        ...groupIndex(),
        { label: groupName }
    ],

    levels: (): BreadcrumbItem[] => levelIndex(),

    levelCreate: (): BreadcrumbItem[] => [
        ...levelIndex(),
        { label: 'Créer' }
    ],

    levelEdit: (levelName: string): BreadcrumbItem[] => [
        ...levelIndex(),
        { label: levelName }
    ],

    roles: (): BreadcrumbItem[] => roleIndex(),

    roleCreate: (): BreadcrumbItem[] => [
        ...roleIndex(),
        { label: 'Créer' }
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
        { label: 'Créer' }
    ],

    examEdit: (examTitle: string, examId: number): BreadcrumbItem[] => [
        ...examShowBreadcrumb(examTitle, examId),
        { label: 'Modifier' }
    ],

    examShow: (examTitle: string): BreadcrumbItem[] => [
        ...examIndex(),
        { label: examTitle }
    ],

    examAssign: (examTitle: string, examId: number): BreadcrumbItem[] => [
        ...examShowBreadcrumb(examTitle, examId),
        { label: 'Assigner' }
    ],

    examAssignments: (examTitle: string, examId: number): BreadcrumbItem[] => [
        ...examShowBreadcrumb(examTitle, examId),
        { label: 'Groupes' }
    ],

    examGroupShow: (examTitle: string, examId: number, groupName: string): BreadcrumbItem[] => [
        ...examShowBreadcrumb(examTitle, examId),
        { label: 'Groupes', href: route('exams.groups', { exam: examId }) },
        { label: groupName }
    ],
    examGroupSubmission: (
        examId: number,
        groupId: number,
        examTitle: string,
        groupName: string,
        studentFullName: string): BreadcrumbItem[] => [
            ...examShowBreadcrumb(examTitle, examId),
            { label: 'Groupes', href: route('exams.groups', { exam: examId }) },
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
            { label: 'Groupes', href: route('exams.groups', { exam: examId }) },
            { label: groupName, href: route('exams.group.show', { exam: examId, group: groupId }) },
            { label: studentFullName, href: route('exams.submissions', { exam: examId, group: groupId, student: studentId }) },
            { label: 'Correction' }
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
        { label: "Passer l'examen" }
    ],
};

// Routes de navigation principales (utilisées par le Sidebar)
export const navRoutes = {
    dashboard: () => route('dashboard'),
    studentExams: () => route('student.exams.index'),
    exams: () => route('exams.index'),
    users: () => route('users.index'),
    groups: () => route('groups.index'),
    levels: () => route('levels.index'),
    roles: () => route('roles.index'),
    profile: () => route('profile'),
    logout: () => route('logout'),
};
