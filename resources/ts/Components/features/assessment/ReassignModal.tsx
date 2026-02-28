import { useState, useCallback } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import axios from 'axios';
import { ConfirmationModal, Textarea } from '@/Components';
import { useTranslations } from '@/hooks/shared/useTranslations';
import type { Assessment, AssessmentAssignment, User, AssessmentRouteContext } from '@/types';

interface ReassignModalProps {
    isOpen: boolean;
    onClose: () => void;
    assessment: Assessment;
    assignment: AssessmentAssignment;
    student: User;
    routeContext: AssessmentRouteContext;
}

/**
 * Modal for reassigning an assessment to a student who submitted no answers.
 *
 * Requires a mandatory reason from the teacher. On success, refreshes the page
 * so the assignment returns to its initial state.
 */
export function ReassignModal({
    isOpen,
    onClose,
    assessment,
    assignment,
    student,
    routeContext,
}: ReassignModalProps) {
    const { t } = useTranslations();
    const [reason, setReason] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleClose = useCallback(() => {
        setReason('');
        setError(null);
        onClose();
    }, [onClose]);

    const handleConfirm = useCallback(async () => {
        if (!reason.trim()) return;
        setLoading(true);
        setError(null);

        try {
            await axios.post(
                route(routeContext.reassignRoute!, {
                    assessment: assessment.id,
                    assignment: assignment.id,
                }),
                { reason },
            );
            handleClose();
            router.visit(route(routeContext.showRoute!, { assessment: assessment.id }), {
                preserveState: false,
            });
        } catch (err: unknown) {
            setError(
                axios.isAxiosError(err) && err.response?.data?.message
                    ? err.response.data.message
                    : t('grading_pages.show.reassign_error'),
            );
        } finally {
            setLoading(false);
        }
    }, [reason, assessment, assignment, routeContext, handleClose, t]);

    return (
        <ConfirmationModal
            isOpen={isOpen}
            onClose={handleClose}
            onConfirm={handleConfirm}
            title={t('grading_pages.show.reassign_confirm_title')}
            message={t('grading_pages.show.reassign_confirm_message', { student: student.name })}
            confirmText={t('grading_pages.show.reassign_confirm')}
            cancelText={t('grading_pages.show.cancel')}
            type="warning"
            loading={loading}
        >
            <div className="mb-4 w-full space-y-2">
                <Textarea
                    value={reason}
                    onChange={(e) => setReason(e.target.value)}
                    placeholder={t('grading_pages.show.reassign_reason_placeholder')}
                    rows={3}
                    className="w-full"
                    required
                />
                {error && <p className="text-sm text-red-600">{error}</p>}
            </div>
        </ConfirmationModal>
    );
}
