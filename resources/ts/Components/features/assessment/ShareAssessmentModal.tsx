import Modal from '@evalium/ui/Modal/Modal';
import { type Assessment } from '@evalium/utils/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { useFormatters } from '@/hooks/shared/useFormatters';
import {
    ClockIcon,
    QuestionMarkCircleIcon,
    StarIcon,
    TagIcon,
    SignalIcon,
} from '@heroicons/react/24/outline';
import { Button } from '@evalium/ui';
import { MarkdownRenderer } from '@evalium/ui';

interface ShareAssessmentModalProps {
    isOpen: boolean;
    onClose: () => void;
    assessment: Assessment;
}

/**
 * Modal for teachers to display assessment details with a prominent ID,
 * enabling students to use the quick access sidebar input.
 */
export function ShareAssessmentModal({ isOpen, onClose, assessment }: ShareAssessmentModalProps) {
    const { t } = useTranslations();
    const { formatDuration } = useFormatters();

    const questionsCount = (assessment.questions ?? []).length;
    const totalPoints = (assessment.questions ?? []).reduce((sum, q) => sum + q.points, 0);

    const typeLabel = t(`components.assessment_general_config.type_${assessment.type}`);
    const modeLabel =
        assessment.delivery_mode === 'homework'
            ? t('components.assessment_general_config.delivery_mode_homework')
            : t('components.assessment_general_config.delivery_mode_supervised');

    const details = [
        {
            icon: TagIcon,
            label: t('assessment_pages.common.share_modal_type'),
            value: typeLabel,
        },
        {
            icon: SignalIcon,
            label: t('assessment_pages.common.share_modal_mode'),
            value: modeLabel,
        },
        {
            icon: ClockIcon,
            label: t('assessment_pages.common.share_modal_duration'),
            value: assessment.duration_minutes ? formatDuration(assessment.duration_minutes) : '-',
        },
        {
            icon: QuestionMarkCircleIcon,
            label: t('assessment_pages.common.share_modal_questions'),
            value: String(questionsCount),
        },
        {
            icon: StarIcon,
            label: t('assessment_pages.common.share_modal_points'),
            value: String(totalPoints),
        },
    ];

    return (
        <Modal
            isOpen={isOpen}
            onClose={onClose}
            title={t('assessment_pages.common.share_modal_title')}
            size="lg"
        >
            <div className="space-y-6">
                <div className="text-center">
                    <p className="text-sm text-gray-500 mb-2">
                        {t('assessment_pages.common.share_modal_id_label')}
                    </p>
                    <div className="inline-flex items-center justify-center bg-indigo-50 border-2 border-indigo-200 rounded-2xl px-10 py-5">
                        <span className="text-5xl font-extrabold text-indigo-700 tracking-widest font-mono">
                            {assessment.id}
                        </span>
                    </div>
                    <p className="mt-3 text-sm text-gray-500 max-w-md mx-auto">
                        {t('assessment_pages.common.share_modal_instruction')}
                    </p>
                </div>

                <div className="border-t border-gray-100 pt-4">
                    <h4 className="text-lg font-semibold text-gray-900 mb-1">{assessment.title}</h4>
                    {assessment.description && (
                        <div className="text-sm text-gray-600">
                            <MarkdownRenderer>{assessment.description}</MarkdownRenderer>
                        </div>
                    )}
                </div>

                <div className="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    {details.map((detail) => (
                        <div
                            key={detail.label}
                            className="flex items-center gap-2 bg-gray-50 rounded-lg px-3 py-2"
                        >
                            <detail.icon className="h-5 w-5 text-gray-400 shrink-0" />
                            <div className="min-w-0">
                                <p className="text-xs text-gray-500">{detail.label}</p>
                                <p className="text-sm font-medium text-gray-900 truncate">
                                    {detail.value}
                                </p>
                            </div>
                        </div>
                    ))}
                </div>

                <div className="flex justify-end pt-2">
                    <Button onClick={onClose} color="secondary" variant="outline">
                        {t('assessment_pages.common.share_modal_close')}
                    </Button>
                </div>
            </div>
        </Modal>
    );
}
