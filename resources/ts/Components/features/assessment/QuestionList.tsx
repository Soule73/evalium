import { type Question } from '@/types';
import { Section } from '@/Components/ui';
import { QuestionCard } from './question/QuestionCard';

interface QuestionListProps {
    questions: Question[];
    /** When provided, wraps the list in a Section with dividers between items. */
    title?: string;
}

/**
 * Unified question list component used across Review, Grade, Admin, and Student pages.
 * Render configuration is read from the nearest QuestionProvider.
 *
 * - With `title`: wraps in a Section with dividers between items.
 * - Without `title`: renders a plain spaced list.
 */
const QuestionList: React.FC<QuestionListProps> = ({ questions, title }) => {
    if (!questions.length) return null;

    const withSection = Boolean(title);

    const content = (
        <div className="space-y-6">
            {questions.map((question, index) => (
                <div
                    key={question.id}
                    className={
                        withSection ? 'pb-6 border-b border-gray-200 last:border-0' : undefined
                    }
                >
                    <QuestionCard question={question} questionIndex={index} />
                </div>
            ))}
        </div>
    );

    if (withSection) {
        return <Section title={title!}>{content}</Section>;
    }

    return content;
};

export { QuestionList };
