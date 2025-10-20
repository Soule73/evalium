import { Choice } from "@/types";
import { CheckIcon } from "@heroicons/react/16/solid";
import MarkdownRenderer from "../form/MarkdownRenderer";
import { questionIndexLabel } from "./TakeQuestion";

interface QuestionResultReadOnlyTextProps {
    userText?: string;
    label?: string;
}

const QuestionResultReadOnlyText: React.FC<QuestionResultReadOnlyTextProps> = ({ userText, label = "Votre réponse" }) => {
    return (
        <div className="p-3 bg-gray-50 border border-gray-200 rounded-lg">
            <p className="text-sm text-gray-600 mb-1">{label}</p>
            <MarkdownRenderer>
                {userText || 'Aucune réponse fournie'}
            </MarkdownRenderer>
        </div>
    );
};

const getBooleanDisplay = (content: string) => {
    const normalized = content?.toString().toLowerCase() ?? '';
    return ['true', 'vrai'].includes(normalized);
};

const getChoiceStyles = (isSelected: boolean, isCorrect: boolean, shouldShowCorrect: boolean) => {
    if (!shouldShowCorrect) {
        return {
            bg: isSelected ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200',
            text: isSelected ? 'text-blue-800 font-medium' : 'text-gray-700',
            borderColor: isSelected ? 'border-blue-500 bg-blue-500' : 'border-gray-300'
        };
    }

    if (isSelected) {
        return {
            bg: isCorrect ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200',
            text: isCorrect ? 'text-green-800 font-medium' : 'text-red-800 font-medium',
            borderColor: isCorrect ? 'border-green-500 bg-green-500' : 'border-red-500 bg-red-500'
        };
    }

    return {
        bg: isCorrect ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200',
        text: isCorrect ? 'text-green-800 font-medium' : 'text-gray-700',
        borderColor: isCorrect ? 'border-green-500 bg-green-500' : 'border-gray-300'
    };
};

const getStatusLabel = (isSelected: boolean, isCorrect: boolean, shouldShowCorrect: boolean, isTeacherView: boolean) => {
    if (!shouldShowCorrect) {
        return isSelected ? (
            <span className="ml-2 text-xs text-blue-600 font-medium">
                {isTeacherView ? "Réponse de l'étudiant" : "Votre réponse"}
            </span>
        ) : null;
    }

    if (isSelected && !isCorrect) {
        return (
            <span className="ml-2 text-xs text-red-600 font-medium">
                {isTeacherView ? "Réponse de l'étudiant (incorrecte)" : "Votre réponse (incorrecte)"}
            </span>
        );
    }

    if (isSelected && isCorrect) {
        return (
            <span className="ml-2 text-xs text-green-600 font-medium">
                {isTeacherView ? "Réponse de l'étudiant (correcte)" : "Votre réponse (correcte)"}
            </span>
        );
    }

    if (!isSelected && isCorrect) {
        return (
            <span className="ml-2 text-xs text-green-600 font-medium">
                Bonne réponse
            </span>
        );
    }

    return null;
};

interface ChoiceItemProps {
    choice: Choice;
    index: number;
    type: 'one_choice' | 'multiple' | 'boolean';
    isSelected: boolean;
    shouldShowCorrect: boolean;
    isTeacherView: boolean;
}

const ChoiceItem: React.FC<ChoiceItemProps> = ({ choice, index, type, isSelected, shouldShowCorrect, isTeacherView }) => {
    const isCorrect = choice.is_correct;
    const styles = getChoiceStyles(isSelected, isCorrect, shouldShowCorrect);
    const border = type === 'multiple' ? 'rounded border-2' : 'rounded-full border-2';

    const indexLabel = type === "boolean" ? (
        (() => {
            const isTrue = getBooleanDisplay(choice.content || '');
            const badgeClass = (shouldShowCorrect && isCorrect)
                ? 'bg-green-100 text-green-800'
                : (isTrue ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800');
            return (
                <span className={`inline-flex items-center justify-center h-6 w-6 rounded-full ${badgeClass} text-xs font-medium mr-2`}>
                    {isTrue ? 'V' : 'F'}
                </span>
            );
        })()
    ) : questionIndexLabel(index, (shouldShowCorrect && isCorrect) ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800');

    return (
        <div className={`p-3 rounded-lg border ${styles.bg}`}>
            <div className="flex items-center">
                <div className={`w-4 h-4 mr-3 flex items-center justify-center ${border} ${styles.borderColor}`}>
                    {(isSelected || (shouldShowCorrect && isCorrect)) && (
                        <CheckIcon className="w-4 h-4 fill-white" />
                    )}
                </div>
                {indexLabel}
                <span className={styles.text}>
                    {type === 'boolean' ? (
                        getBooleanDisplay(choice.content || '') ? 'Vrai' : 'Faux'
                    ) : choice.content}
                </span>
                {getStatusLabel(isSelected, isCorrect, shouldShowCorrect, isTeacherView)}
            </div>
        </div>
    );
};

interface QuestionResultReadOnlyChoicesProps {
    choices: Choice[];
    userChoices: Choice[];
    type: 'one_choice' | 'multiple' | 'boolean';
    isTeacherView?: boolean;
    showCorrectAnswers?: boolean;
}

const QuestionResultReadOnlyChoices: React.FC<QuestionResultReadOnlyChoicesProps> = ({
    choices,
    userChoices,
    type,
    isTeacherView = false,
    showCorrectAnswers = true
}) => {
    const shouldShowCorrect = showCorrectAnswers || isTeacherView;

    return (
        <div className="space-y-2">
            {(choices ?? []).map((choice, idx) => {
                const isSelected = userChoices.some(uc => uc.id === choice.id);

                return (
                    <ChoiceItem
                        key={choice.id}
                        choice={choice}
                        index={idx}
                        type={type}
                        isSelected={isSelected}
                        shouldShowCorrect={shouldShowCorrect}
                        isTeacherView={isTeacherView}
                    />
                );
            })}
        </div>
    );
};

interface QuestionTeacherReadOnlyChoicesProps {
    choices: Choice[];
    type: 'one_choice' | 'multiple' | 'boolean';
}

const QuestionTeacherReadOnlyChoices: React.FC<QuestionTeacherReadOnlyChoicesProps> = ({ choices, type }) => {
    return (
        <div className="space-y-2">
            {(choices ?? []).map((choice, idx) => (
                <ChoiceItem
                    key={choice.id}
                    choice={choice}
                    index={idx}
                    type={type}
                    isSelected={false}
                    shouldShowCorrect={true}
                    isTeacherView={true}
                />
            ))}
        </div>
    );
};

export { QuestionResultReadOnlyText, QuestionTeacherReadOnlyChoices, QuestionResultReadOnlyChoices };