import { route } from 'ziggy-js';
import { BreadcrumbItem } from '@/Components/Breadcrumb';

// Helper pour créer des breadcrumbs courants
export const breadcrumbs = {
    // Admin breadcrumbs
    adminDashboard: (): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') }
    ],

    adminUsers: (): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') },
        { label: 'Utilisateurs', href: route('admin.users.index') }
    ],

    adminUserCreate: (): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') },
        { label: 'Utilisateurs', href: route('admin.users.index') },
        { label: 'Créer' }
    ],

    adminUserEdit: (userName: string): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') },
        { label: 'Utilisateurs', href: route('admin.users.index') },
        { label: userName }
    ],

    adminGroups: (): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') },
        { label: 'Groupes', href: route('admin.groups.index') }
    ],

    adminGroupCreate: (): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') },
        { label: 'Groupes', href: route('admin.groups.index') },
        { label: 'Créer' }
    ],

    adminGroupEdit: (groupName: string): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') },
        { label: 'Groupes', href: route('admin.groups.index') },
        { label: groupName }
    ],
    adminGroupAssignStudents: (groupName: string, id: number): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') },
        { label: 'Groupes', href: route('admin.groups.index') },
        { label: groupName, href: route('admin.groups.show', { group: id }) },
        { label: 'Assigner des étudiants' }
    ],

    adminGroupShow: (groupName: string): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') },
        { label: 'Groupes', href: route('admin.groups.index') },
        { label: groupName }
    ],

    adminLevels: (): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') },
        { label: 'Niveaux', href: route('admin.levels.index') }
    ],

    adminLevelCreate: (): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') },
        { label: 'Niveaux', href: route('admin.levels.index') },
        { label: 'Créer' }
    ],

    adminLevelEdit: (levelName: string): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') },
        { label: 'Niveaux', href: route('admin.levels.index') },
        { label: levelName }
    ],

    adminRoles: (): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') },
        { label: 'Rôles & Permissions', href: route('admin.roles.index') }
    ],

    adminRoleCreate: (): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') },
        { label: 'Rôles & Permissions', href: route('admin.roles.index') },
        { label: 'Créer' }
    ],

    adminRoleEdit: (roleName: string): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') },
        { label: 'Rôles & Permissions', href: route('admin.roles.index') },
        { label: roleName }
    ],
    adminStudentShow: (user: { name: string }): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') },
        { label: 'Utilisateurs', href: route('admin.users.index') },
        { label: user.name }
    ],
    adminTeacherShow: (user: { name: string }): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') },
        { label: 'Utilisateurs', href: route('admin.users.index') },
        { label: user.name }
    ],

    adminGroupIndex: (): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('admin.dashboard') },
        { label: 'Groupes', href: route('admin.groups.index') }
    ],



    // Teacher breadcrumbs
    teacherDashboard: (): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('teacher.dashboard') }
    ],

    teacherExams: (): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('teacher.dashboard') },
        { label: 'Mes Examens', href: route('teacher.exams.index') }
    ],

    teacherExamCreate: (): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('teacher.dashboard') },
        { label: 'Mes Examens', href: route('teacher.exams.index') },
        { label: 'Créer' }
    ],

    teacherExamEdit: (examTitle: string): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('teacher.dashboard') },
        { label: 'Mes Examens', href: route('teacher.exams.index') },
        { label: examTitle }
    ],

    teacherExamShow: (examTitle: string): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('teacher.dashboard') },
        { label: 'Mes Examens', href: route('teacher.exams.index') },
        { label: examTitle }
    ],
    teacherExamAssign: (examTitle: string, examId: number): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('teacher.dashboard') },
        { label: 'Mes Examens', href: route('teacher.exams.index') },
        { label: examTitle, href: route('teacher.exams.show', { exam: examId }) },
        { label: 'Assigner' }
    ],
    teacherExamAssignments: (examTitle: string, examId: number): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('teacher.dashboard') },
        { label: 'Mes Examens', href: route('teacher.exams.index') },
        { label: examTitle, href: route('teacher.exams.show', { exam: examId }) },
        { label: 'Assignations' }
    ],

    teacherExamGroupDetails: (examTitle: string, examId: number, groupName: string): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('teacher.dashboard') },
        { label: 'Mes Examens', href: route('teacher.exams.index') },
        { label: examTitle, href: route('teacher.exams.show', { exam: examId }) },
        { label: 'Assignations', href: route('teacher.exams.assignments', { exam: examId }) },
        { label: groupName }
    ],

    // Student breadcrumbs
    studentDashboard: (): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('student.dashboard') }
    ],

    studentExams: (): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('student.dashboard') },
        { label: 'Mes Examens', href: route('student.exams.index') }
    ],

    studentExamShow: (examTitle: string): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('student.dashboard') },
        { label: 'Mes Examens', href: route('student.exams.index') },
        { label: examTitle }
    ],

    studentExamTake: (examTitle: string): BreadcrumbItem[] => [
        { label: 'Tableau de bord', href: route('student.dashboard') },
        { label: 'Mes Examens', href: route('student.exams.index') },
        { label: examTitle },
        { label: 'Passer l\'examen' }
    ],
};
