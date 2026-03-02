export type NotificationType =
    | 'assessment_published'
    | 'assessment_graded'
    | 'assessment_submitted'
    | 'assessment_starting_soon';

export interface NotificationData {
    type: NotificationType;
    assessment_id: number;
    assessment_title: string;
    subject?: string;
    scheduled_at?: string;
    delivery_mode?: string;
    assignment_id?: number;
    student_name?: string;
    submitted_at?: string;
    url: string;
}

export interface AppNotification {
    id: string;
    type: string;
    notifiable_type: string;
    notifiable_id: number;
    data: NotificationData;
    read_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface NotificationsSharedProp {
    unread_count: number;
}
