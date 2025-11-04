import { formatDate, formatExamAssignmentStatus } from '@/utils/formatters';
import { ExamAssignment, Exam, Group } from '@/types';
import Badge from '@/Components/Badge';
import { Button } from '@/Components/Button';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { trans } from '@/utils/translations';

interface ExamAssignmentColumnsOptions {
    exam: Exam;
    group?: Group;
    // onRemove?: (assignment: ExamAssignment) => void;
    showActions?: boolean;
}

/**
 * Configuration des colonnes réutilisables pour afficher les assignations d'examens
 */
export const getExamAssignmentColumns = ({ exam, group, showActions = true }: ExamAssignmentColumnsOptions) => {
    const columns = [
        {
            key: 'student',
            label: trans('components.exam_assignment_columns.student_label'),
            render: (assignment: ExamAssignment) => (
                <div>
                    <div className="text-sm font-medium text-gray-900">
                        {assignment.student?.name || trans('components.exam_assignment_columns.name_unavailable')}
                    </div>
                    <div className="text-sm text-gray-500">
                        {assignment.student?.email || trans('components.exam_assignment_columns.email_unavailable')}
                    </div>
                </div>
            ),
        },
        {
            key: 'status',
            label: trans('components.exam_assignment_columns.status_label'),
            render: (assignment: ExamAssignment) => {
                const status = formatExamAssignmentStatus(assignment.status);
                return (
                    <Badge
                        type={status.color as 'success' | 'warning' | 'info' | 'gray'}
                        label={status.label}
                    />
                );
            },
        },
        {
            key: 'assigned_at',
            label: trans('components.exam_assignment_columns.assigned_on'),
            render: (assignment: ExamAssignment) => (
                <div className="text-sm text-gray-500">
                    {assignment.assigned_at ? formatDate(assignment.assigned_at, 'datetime') : '-'}
                </div>
            ),
        },
        {
            key: 'started_at',
            label: trans('components.exam_assignment_columns.started_on'),
            render: (assignment: ExamAssignment) => (
                <div className="text-sm text-gray-500">
                    {assignment.started_at ? formatDate(assignment.started_at, 'datetime') : '-'}
                </div>
            ),
        },
        {
            key: 'submitted_at',
            label: trans('components.exam_assignment_columns.completed_on'),
            render: (assignment: ExamAssignment) => (
                <div className="text-sm text-gray-500">
                    {assignment.submitted_at ? formatDate(assignment.submitted_at, 'datetime') : '-'}
                </div>
            ),
        },
        {
            key: 'score',
            label: trans('components.exam_assignment_columns.score_label'),
            render: (assignment: ExamAssignment) => assignment?.score ?? '-',
        },
    ];

    if (showActions) {
        columns.push({
            key: 'actions',
            label: trans('components.exam_assignment_columns.actions_label'),
            render: (assignment: ExamAssignment) => (
                <div className="flex space-x-2">
                    {(assignment.status === 'submitted' || assignment.status === 'graded') ? (
                        <Button
                            onClick={() => router.visit(route('exams.submissions', { exam: exam.id, group: group?.id, student: assignment.student_id }))}
                            color="success"
                            size="sm"
                            variant="outline"
                            className='text-xs'
                        >
                            {trans('components.exam_assignment_columns.view_result')}
                        </Button>
                    ) : (
                        <span className="text-xs text-gray-400">-</span>
                    )}
                </div>
            ),
        } as any);
    }

    return columns;
};

/**
 * Configuration par défaut pour les filtres des assignations
 */
export const getExamAssignmentFilters = () => [
    {
        key: 'status',
        label: trans('components.exam_assignment_columns.status_label'),
        type: 'select' as const,
        options: [
            { value: '', label: trans('components.exam_assignment_columns.all_statuses') },
            { value: null, label: trans('components.exam_assignment_columns.not_started') },
            { value: 'submitted', label: trans('components.exam_assignment_columns.submitted') },
            { value: 'graded', label: trans('components.exam_assignment_columns.graded') },
        ],
    },
];
