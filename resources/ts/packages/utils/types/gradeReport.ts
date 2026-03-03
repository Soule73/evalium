import type { Enrollment } from './enrollment';
import type { Semester } from './semester';
import type { User } from './shared';

export type GradeReportStatus = 'draft' | 'validated' | 'published';

export interface GradeReportSubjectData {
    class_subject_id: number;
    subject_name: string;
    coefficient: number;
    grade: number | null;
    assessments_count: number;
    class_average?: number | null;
    min?: number | null;
    max?: number | null;
}

export interface GradeReportData {
    header: {
        school_name: string;
        logo_path: string | null;
        academic_year: string;
        period: string;
        student_name: string;
        class_name: string;
        level_name: string;
    };
    subjects: GradeReportSubjectData[];
    footer: {
        average: number | null;
        rank: number | null;
        class_size: number;
        total_coefficient: number;
    };
}

export interface GradeReportSubjectRemark {
    class_subject_id: number;
    subject_name: string;
    remark: string;
}

export interface GradeReportRemarks {
    subjects: GradeReportSubjectRemark[];
}

export interface GradeReport {
    id: number;
    enrollment_id: number;
    semester_id: number | null;
    academic_year_id: number;
    data: GradeReportData;
    remarks: GradeReportRemarks;
    general_remark: string | null;
    rank: number | null;
    average: number | null;
    status: GradeReportStatus;
    validated_by: number | null;
    validated_at: string | null;
    file_path: string | null;
    created_at: string;
    updated_at: string;

    enrollment?: Enrollment;
    semester?: Semester;
    validator?: User;
}
