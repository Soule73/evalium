export type AssignmentStatus = 'submitted' | 'graded';

export type QuestionType = 'multiple' | 'text' | 'one_choice' | 'boolean';

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    active?: boolean;
    is_active?: boolean;
    email_verified_at?: string;
    created_at: string;
    updated_at: string;
    deleted_at?: string | null;
    roles?: Role[];
    current_group?: Group;
    groups?: GroupWithPivot[];
}

export interface GroupWithPivot extends Group {
    pivot?: {
        enrolled_at: string;
        left_at?: string | null;
        is_active: boolean;
    };
}

export interface Role {
    id: number;
    name: string;
    guard_name: string;
    created_at: string;
    updated_at: string;
}

export interface Level {
    id: number;
    name: string;
    code: string;
    description?: string;
    order: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    groups_count?: number;
}

export interface Group {
    id: number;
    display_name: string;
    description?: string;
    level_id: number | null;
    level?: Level;
    start_date: string;
    end_date: string;
    max_students: number;
    is_active: boolean;
    academic_year?: string;
    created_at: string;
    updated_at: string;
    active_students?: User[];
    active_students_count?: number;
}

export interface Exam {
    id: number;
    title: string;
    description?: string;
    duration: number;
    is_active: boolean;
    start_time?: string;
    end_time?: string;
    teacher_id: number;
    created_at: string;
    updated_at: string;
    creator?: User;
    questions?: Question[];
    questions_count?: number;
}



export interface Question {
    id: number;
    exam_id: number;
    type: QuestionType;
    content: string;
    points: number;
    order_index: number;
    created_at: string;
    updated_at: string;
    choices?: Choice[];
    correct_answer?: string;
}

export interface Choice {
    id: number;
    question_id: number;
    content: string;
    is_correct: boolean;
    order_index: number;
    created_at: string;
    updated_at: string;
}

export interface Answer {
    id: number;
    assignment_id: number;
    question_id: number;
    choice_id?: number;
    answer_text?: string;
    score?: number;
    feedback?: string;
    created_at: string;
    updated_at: string;

    choice?: Choice;
    choices?: Array<{
        choice_id: number;
        choice: Choice;
    }>;
    selectedChoice?: Choice;
}

export interface BackendAnswerData {
    type: 'single' | 'multiple';
    choice_id?: number;
    answer_text?: string;
    choices?: Array<{
        choice_id: number;
        choice: Choice;
    }>;
    choice?: Choice;
}

export type FlashMessageObject = { id: string; message: string } | null;

export interface FlashMessages {
    success?: FlashMessageObject;
    error?: FlashMessageObject;
    warning?: FlashMessageObject;
    info?: FlashMessageObject;
}

export interface ExamAssignment {
    id: number;
    student_id: number;
    exam_id: number;
    assigned_at: string;
    started_at?: string;
    submitted_at?: string;
    score?: number;
    auto_score?: number;
    status: AssignmentStatus;
    teacher_notes?: string;
    security_violation?: string;
    forced_submission: boolean;
    created_at: string;
    updated_at: string;
    exam?: Exam;
    student?: User;
    answers?: Answer[];
}

export type PageProps<T = Record<string, unknown>> = {
    auth: {
        user: User;
        permissions: string[];
        roles: string[];
    };
    permissions: {
        // Navigation permissions
        canManageLevels: boolean;
        canManageRoles: boolean;
        canManageUsers: boolean;
        canManageGroups: boolean;
        canManageExams: boolean;

        // Feature permissions
        canViewReports: boolean;
        canExportReports: boolean;
        canCreateExams: boolean;
        canPublishExams: boolean;
        canAssignExams: boolean;
        canCorrectExams: boolean;
        canGradeAnswers: boolean;

        // User management
        canCreateUsers: boolean;
        canUpdateUsers: boolean;
        canDeleteUsers: boolean;
        canManageStudents: boolean;
        canManageTeachers: boolean;

        // Group management
        canCreateGroups: boolean;
        canUpdateGroups: boolean;
        canDeleteGroups: boolean;
        canManageGroupStudents: boolean;

        // Level management
        canCreateLevels: boolean;
        canUpdateLevels: boolean;
        canDeleteLevels: boolean;

        // Role management
        canCreateRoles: boolean;
        canUpdateRoles: boolean;
        canDeleteRoles: boolean;
        canAssignPermissions: boolean;
    };
    flash: FlashMessages;
    locale: string;
} & T;


// Types pour les formulaires de création/édition
export interface QuestionFormData {
    id?: number;
    content: string;
    type: QuestionType;
    points: number;
    order_index: number;
    choices: ChoiceFormData[];
}

export interface ChoiceFormData {
    id?: number;
    content: string;
    is_correct: boolean;
    order_index: number;
}

export interface ExamFormData {
    title: string;
    description: string;
    duration: number;
    is_active: boolean;
    questions: QuestionFormData[];
    deletedQuestionIds?: number[];
    deletedChoiceIds?: number[];
}