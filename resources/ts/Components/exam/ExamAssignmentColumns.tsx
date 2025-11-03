import { formatDate, formatExamAssignmentStatus } from '@/utils/formatters';
import { ExamAssignment, Exam, Group } from '@/types';
import Badge from '@/Components/Badge';
import { Button } from '@/Components/Button';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';

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
            label: 'Étudiant',
            render: (assignment: ExamAssignment) => (
                <div>
                    <div className="text-sm font-medium text-gray-900">
                        {assignment.student?.name || 'Nom non disponible'}
                    </div>
                    <div className="text-sm text-gray-500">
                        {assignment.student?.email || 'Email non disponible'}
                    </div>
                </div>
            ),
        },
        {
            key: 'status',
            label: 'Statut',
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
            label: 'Assigné le',
            render: (assignment: ExamAssignment) => (
                <div className="text-sm text-gray-500">
                    {assignment.assigned_at ? formatDate(assignment.assigned_at, 'datetime') : '-'}
                </div>
            ),
        },
        {
            key: 'started_at',
            label: 'Commencé le',
            render: (assignment: ExamAssignment) => (
                <div className="text-sm text-gray-500">
                    {assignment.started_at ? formatDate(assignment.started_at, 'datetime') : '-'}
                </div>
            ),
        },
        {
            key: 'submitted_at',
            label: 'Terminé le',
            render: (assignment: ExamAssignment) => (
                <div className="text-sm text-gray-500">
                    {assignment.submitted_at ? formatDate(assignment.submitted_at, 'datetime') : '-'}
                </div>
            ),
        },
        {
            key: 'score',
            label: 'Note',
            render: (assignment: ExamAssignment) => assignment?.score ?? '-',
        },
    ];

    if (showActions) {
        columns.push({
            key: 'actions',
            label: 'Actions',
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
                            Voir résultat
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
export const examAssignmentFilters = [
    {
        key: 'status',
        label: 'Statut',
        type: 'select' as const,
        options: [
            { value: '', label: 'Tous les statuts' },
            { value: null, label: 'Non commencé' },
            { value: 'submitted', label: 'Soumis' },
            { value: 'graded', label: 'Noté' },
        ],
    },
];
