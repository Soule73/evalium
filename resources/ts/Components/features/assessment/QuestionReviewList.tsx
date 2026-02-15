import type { Question, QuestionResult } from '@/types';
import { QuestionRenderer } from '@/Components';
import { Section } from '@/Components';

interface QuestionReviewListProps {
  title: string;
  questions: Question[];
  getQuestionResult: (question: Question) => QuestionResult;
  scores: Record<number, number>;
  isTeacherView?: boolean;
  isEditMode?: boolean;
  renderScoreInput?: (question: Question) => React.ReactNode;
}

/**
 * Renders a list of questions with their review results.
 * Wraps QuestionRenderer with consistent spacing and dividers.
 * Used by Review, Grade, and Admin assignment detail pages.
 */
export const QuestionReviewList: React.FC<QuestionReviewListProps> = ({
  title,
  questions,
  getQuestionResult,
  scores,
  isTeacherView = true,
  isEditMode = false,
  renderScoreInput,
}) => {
  if (!questions.length) return null;

  return (
    <Section title={title}>
      <div className="space-y-6">
        {questions.map((question) => (
          <div key={question.id} className="pb-6 border-b border-gray-200 last:border-0">
            <QuestionRenderer
              questions={[question]}
              getQuestionResult={getQuestionResult}
              scores={scores}
              isTeacherView={isTeacherView}
              renderScoreInput={renderScoreInput}
              isEditMode={isEditMode}
            />
          </div>
        ))}
      </div>
    </Section>
  );
};
