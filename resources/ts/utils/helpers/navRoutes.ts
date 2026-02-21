import { route } from 'ziggy-js';

export const navRoutes = {
    dashboard: () => route('dashboard'),

    // Student Routes
    studentAssessments: () => route('student.assessments.index'),
    studentEnrollment: () => route('student.enrollment.show'),

    // Teacher Routes
    teacherDashboard: () => route('teacher.dashboard'),
    teacherAssessments: () => route('teacher.assessments.index'),
    teacherClasses: () => route('teacher.classes.index'),

    // Admin Routes
    adminAcademicYears: () => route('admin.academic-years.archives'),
    adminSubjects: () => route('admin.subjects.index'),
    adminClasses: () => route('admin.classes.index'),
    adminEnrollments: () => route('admin.enrollments.index'),
    adminClassSubjects: () => route('admin.class-subjects.index'),
    adminAssessments: () => route('admin.assessments.index'),

    // System Routes
    users: () => route('admin.users.index'),
    teachers: () => route('admin.teachers.index'),
    levels: () => route('admin.levels.index'),
    roles: () => route('admin.roles.index'),

    // Profile & Auth
    profile: () => route('profile'),
    logout: () => route('logout'),
};
