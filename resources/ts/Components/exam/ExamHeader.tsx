import { Exam } from '@/types';
import MarkdownRenderer from '@/Components/form/MarkdownRenderer';
import { formatDuration } from '@/utils/formatters';
import { ClockIcon, QuestionMarkCircleIcon, CalendarIcon } from '@heroicons/react/24/outline';
import { formatDate } from '@/utils/formatters';

interface ExamHeaderProps {
    exam: Exam;
    showDescription?: boolean;
    showMetadata?: boolean;
    compact?: boolean;
}

/**
 * Composant réutilisable pour afficher l'en-tête d'un examen
 * Utilisé dans ExamShow, ExamAssignments, ExamGroupDetails, etc.
 */
export default function ExamHeader({
    exam,
    showDescription = true,
    showMetadata = false,
    compact = false
}: ExamHeaderProps) {
    return (
        <div className="space-y-3">
            <div>
                <h2 className={`${compact ? 'text-xl' : 'text-2xl'} font-bold text-gray-900`}>
                    {exam.title}
                </h2>
                {showDescription && exam.description && (
                    <div className="mt-2 text-gray-600">
                        <MarkdownRenderer>{exam.description}</MarkdownRenderer>
                    </div>
                )}
            </div>

            {showMetadata && (
                <div className="flex flex-wrap gap-4 text-sm text-gray-500">
                    {exam.duration && (
                        <div className="flex items-center gap-2">
                            <ClockIcon className="w-4 h-4" />
                            <span>{formatDuration(exam.duration)}</span>
                        </div>
                    )}
                    {exam.questions && exam.questions.length > 0 && (
                        <div className="flex items-center gap-2">
                            <QuestionMarkCircleIcon className="w-4 h-4" />
                            <span>{exam.questions.length} question{exam.questions.length > 1 ? 's' : ''}</span>
                        </div>
                    )}
                    {exam.created_at && (
                        <div className="flex items-center gap-2">
                            <CalendarIcon className="w-4 h-4" />
                            <span>Créé le {formatDate(exam.created_at)}</span>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
