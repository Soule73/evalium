export type QuestionRenderMode = 'take' | 'review' | 'grade' | 'results' | 'preview';

export type QuestionViewerRole = 'student' | 'teacher' | 'admin';

export type AnswerValue = string | number | number[];

export interface QuestionRenderConfig {
    mode: QuestionRenderMode;
    role: QuestionViewerRole;
    isInteractive: boolean;
    showCorrectAnswers: boolean;
    showScoreInput: boolean;
    suppressNoAnswerWarning: boolean;
    labelVariant: 'student' | 'teacher';
    canEditScores: boolean;
}

/**
 * Derives a QuestionRenderConfig from mode and role, applying all implicit rules.
 * Override showCorrectAnswers for 'results' mode where the server determines visibility.
 */
export function buildRenderConfig(
    mode: QuestionRenderMode,
    role: QuestionViewerRole,
    options?: { canEditScores?: boolean; showCorrectAnswers?: boolean },
): QuestionRenderConfig {
    const defaultShowCorrectAnswers = mode === 'review' || mode === 'grade';

    return {
        mode,
        role,
        isInteractive: mode === 'take',
        showCorrectAnswers: options?.showCorrectAnswers ?? defaultShowCorrectAnswers,
        showScoreInput: mode === 'grade',
        suppressNoAnswerWarning: mode === 'preview',
        labelVariant: role === 'student' ? 'student' : 'teacher',
        canEditScores: mode === 'grade' && (options?.canEditScores ?? true),
    };
}
