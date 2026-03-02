import { CheckIcon } from '@heroicons/react/24/solid';

export interface TimelineStep {
    label: string;
}

export interface TimelineProps {
    steps: TimelineStep[];
    currentStep: number;
    className?: string;
}

/**
 * Horizontal step indicator for multi-step wizards.
 * Steps are 1-indexed.
 */
export default function Timeline({ steps, currentStep, className = '' }: TimelineProps) {
    return (
        <nav aria-label="Progress" className={className}>
            <ol className="flex items-center">
                {steps.map((step, index) => {
                    const stepNumber = index + 1;
                    const isCompleted = stepNumber < currentStep;
                    const isCurrent = stepNumber === currentStep;
                    const isLast = index === steps.length - 1;

                    return (
                        <li
                            key={step.label}
                            className={`relative flex items-center ${isLast ? '' : 'flex-1'}`}
                        >
                            <div className="flex flex-col items-center">
                                <div
                                    className={`flex h-9 w-9 items-center justify-center rounded-full border-2 transition-colors ${
                                        isCompleted
                                            ? 'border-indigo-600 bg-indigo-600'
                                            : isCurrent
                                              ? 'border-indigo-600 bg-white'
                                              : 'border-gray-300 bg-white'
                                    }`}
                                    aria-current={isCurrent ? 'step' : undefined}
                                >
                                    {isCompleted ? (
                                        <CheckIcon
                                            className="h-5 w-5 text-white"
                                            aria-hidden="true"
                                        />
                                    ) : (
                                        <span
                                            className={`text-sm font-semibold ${
                                                isCurrent ? 'text-indigo-600' : 'text-gray-400'
                                            }`}
                                        >
                                            {stepNumber}
                                        </span>
                                    )}
                                </div>
                                <span
                                    className={`mt-2 text-xs font-medium whitespace-nowrap ${
                                        isCompleted || isCurrent
                                            ? 'text-indigo-600'
                                            : 'text-gray-400'
                                    }`}
                                >
                                    {step.label}
                                </span>
                            </div>

                            {!isLast && (
                                <div
                                    className={`mx-3 h-0.5 flex-1 transition-colors ${
                                        isCompleted ? 'bg-indigo-600' : 'bg-gray-200'
                                    }`}
                                    aria-hidden="true"
                                />
                            )}
                        </li>
                    );
                })}
            </ol>
        </nav>
    );
}
