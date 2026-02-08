import { Button } from '@examena/ui';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/react/24/outline';
import { trans } from '@/utils';

interface QuestionNavigationProps {
  currentIndex: number;
  totalQuestions: number;
  isFirstQuestion: boolean;
  isLastQuestion: boolean;
  onPrevious: () => void;
  onNext: () => void;
  onGoToQuestion: (index: number) => void;
  answeredQuestions?: Set<number>;
  questionIds?: number[];
}

export function QuestionNavigation({
  currentIndex,
  totalQuestions,
  isFirstQuestion,
  isLastQuestion,
  onPrevious,
  onNext,
  onGoToQuestion,
  answeredQuestions = new Set(),
  questionIds = [],
}: QuestionNavigationProps) {
  return (
    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
      <div className="flex items-center justify-between mb-4">
        <Button
          size="sm"
          color="secondary"
          onClick={onPrevious}
          disabled={isFirstQuestion}
          className="flex items-center gap-1"
        >
          <ChevronLeftIcon className="h-4 w-4" />
          {trans('student_assessment_pages.take.previous_question')}
        </Button>

        <span className="text-sm font-medium text-gray-700">
          {trans('student_assessment_pages.take.question_progress', {
            current: currentIndex + 1,
            total: totalQuestions,
          })}
        </span>

        <Button
          size="sm"
          color="secondary"
          onClick={onNext}
          disabled={isLastQuestion}
          className="flex items-center gap-1"
        >
          {trans('student_assessment_pages.take.next_question')}
          <ChevronRightIcon className="h-4 w-4" />
        </Button>
      </div>

      {totalQuestions <= 20 && (
        <div className="flex flex-wrap gap-2 justify-center">
          {Array.from({ length: totalQuestions }, (_, i) => {
            const questionId = questionIds[i];
            const isAnswered = questionId !== undefined && answeredQuestions.has(questionId);
            const isCurrent = i === currentIndex;

            return (
              <button
                key={i}
                onClick={() => onGoToQuestion(i)}
                className={`
                  w-8 h-8 rounded-full text-sm font-medium transition-colors
                  ${isCurrent
                    ? 'bg-primary-600 text-white'
                    : isAnswered
                      ? 'bg-green-100 text-green-700 hover:bg-green-200'
                      : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                  }
                `}
              >
                {i + 1}
              </button>
            );
          })}
        </div>
      )}
    </div>
  );
}
