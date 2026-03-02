export interface SubjectGrade {
    id: number;
    class_subject_id: number;
    subject_name: string;
    teacher_name: string;
    coefficient: number;
    average: number | null;
    assessments_count: number;
    completed_count: number;
}

export interface OverallStats {
    student_id: number;
    student_name: string;
    class_id: number;
    class_name: string;
    annual_average: number | null;
    total_coefficient: number;
    total_assessments: number;
    completed_assessments: number;
}
