import { Question } from '@/types';

/**
 * Shuffles an array using Fisher-Yates algorithm.
 * Creates a new shuffled array without mutating the original.
 */
export function shuffleArray<T>(array: T[]): T[] {
  const shuffled = [...array];
  for (let i = shuffled.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
  }
  return shuffled;
}

/**
 * Shuffles questions and returns both the shuffled array and the order of IDs.
 * Useful for maintaining a consistent order during the assessment session.
 */
export function shuffleQuestions(questions: Question[]): {
  shuffledQuestions: Question[];
  questionIds: number[];
} {
  const shuffled = shuffleArray(questions);
  return {
    shuffledQuestions: shuffled,
    questionIds: shuffled.map((q) => q.id),
  };
}

/**
 * Reorders questions based on a saved order of IDs.
 * Useful for restoring a previously shuffled order.
 */
export function reorderQuestionsByIds(questions: Question[], orderedIds: number[]): Question[] {
  const questionMap = new Map(questions.map((q) => [q.id, q]));
  return orderedIds.map((id) => questionMap.get(id)).filter((q): q is Question => q !== undefined);
}
