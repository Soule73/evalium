import React from 'react';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Checkbox, Input, Select } from '@evalium/ui';
import { MarkdownEditor } from '@evalium/ui';
import { type ClassSubject, type AssessmentType, type DeliveryMode } from '@/types';

const DEFAULT_DELIVERY_MODES: Record<string, DeliveryMode> = {
    exam: 'supervised',
    quiz: 'supervised',
    homework: 'homework',
    practical: 'homework',
    project: 'homework',
};

interface AssessmentGeneralConfigProps {
    data: {
        title: string;
        description: string;
        duration: number;
        scheduled_date?: string;
        due_date?: string;
        delivery_mode: DeliveryMode;
        type: AssessmentType;
        class_subject_id: number | null;
        coefficient: number;
        is_published: boolean;
        shuffle_questions: boolean;
        show_results_immediately: boolean;
        show_correct_answers: boolean;
        allow_late_submission: boolean;
    };
    errors: {
        title?: string;
        description?: string;
        duration?: string;
        duration_minutes?: string;
        scheduled_date?: string;
        scheduled_at?: string;
        due_date?: string;
        delivery_mode?: string;
        type?: string;
        class_subject_id?: string;
        is_published?: string;
        coefficient?: string;
    };
    onFieldChange: (field: string, value: string | number | boolean) => void;
    classSubjects: ClassSubject[];
}

const AssessmentGeneralConfig: React.FC<AssessmentGeneralConfigProps> = ({
    data,
    errors,
    onFieldChange,
    classSubjects,
}) => {
    const { t } = useTranslations();
    const isSupervised = data.delivery_mode === 'supervised';

    const assessmentTypeOptions = [
        { value: 'homework', label: t('components.assessment_general_config.type_homework') },
        { value: 'exam', label: t('components.assessment_general_config.type_exam') },
        { value: 'practical', label: t('components.assessment_general_config.type_practical') },
        { value: 'quiz', label: t('components.assessment_general_config.type_quiz') },
        { value: 'project', label: t('components.assessment_general_config.type_project') },
    ];

    const deliveryModeOptions = [
        {
            value: 'supervised',
            label: t('components.assessment_general_config.delivery_mode_supervised'),
        },
        {
            value: 'homework',
            label: t('components.assessment_general_config.delivery_mode_homework'),
        },
    ];

    const classSubjectOptions = classSubjects.map((cs) => ({
        value: cs.id.toString(),
        label: `${cs.class?.name} - ${cs.subject?.name}`,
    }));

    const handleTypeChange = (value: string | number) => {
        const stringValue = String(value);
        onFieldChange('type', stringValue);
        const suggestedMode = DEFAULT_DELIVERY_MODES[stringValue] || 'homework';
        onFieldChange('delivery_mode', suggestedMode);
    };

    return (
        <div className="space-y-6">
            <h3 className="text-lg font-medium text-gray-900">
                {t('components.assessment_general_config.title')}
            </h3>

            <Checkbox
                label={t('components.assessment_general_config.published_label')}
                checked={data.is_published}
                onChange={(e) => onFieldChange('is_published', e.target.checked)}
            />

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div className="md:col-span-2 lg:col-span-1">
                    <Input
                        label={t('components.assessment_general_config.assessment_title_label')}
                        type="text"
                        value={data.title}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            onFieldChange('title', e.target.value)
                        }
                        error={errors.title}
                        required
                    />
                </div>

                <div>
                    <Select
                        label={t('components.assessment_general_config.type_label')}
                        value={data.type}
                        onChange={handleTypeChange}
                        error={errors.type}
                        options={assessmentTypeOptions}
                        required
                    />
                </div>

                <div>
                    <Select
                        label={t('components.assessment_general_config.delivery_mode_label')}
                        value={data.delivery_mode}
                        onChange={(value) => onFieldChange('delivery_mode', value)}
                        error={errors.delivery_mode}
                        options={deliveryModeOptions}
                        required
                    />
                </div>

                <div>
                    <Input
                        label={t('components.assessment_general_config.coefficient_label')}
                        type="number"
                        value={data.coefficient?.toString() || '1'}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                            onFieldChange('coefficient', parseFloat(e.target.value) || 1)
                        }
                        error={errors.coefficient}
                        min="0.01"
                        step="0.01"
                        required
                    />
                </div>

                {isSupervised && (
                    <div>
                        <Input
                            label={t('components.assessment_general_config.duration_label')}
                            type="number"
                            value={data.duration?.toString() || ''}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                                onFieldChange('duration', parseInt(e.target.value))
                            }
                            error={errors.duration || errors.duration_minutes}
                            min="1"
                            required
                        />
                    </div>
                )}

                <div>
                    <Select
                        label={t('components.assessment_general_config.class_subject_label')}
                        value={data.class_subject_id !== null ? String(data.class_subject_id) : ''}
                        onChange={(value) => {
                            const parsed = typeof value === 'number' ? value : parseInt(value, 10);
                            if (!isNaN(parsed)) {
                                onFieldChange('class_subject_id', parsed);
                            }
                        }}
                        error={errors.class_subject_id}
                        options={classSubjectOptions}
                        required
                        placeholder={t(
                            'components.assessment_general_config.class_subject_placeholder',
                        )}
                    />
                </div>

                {isSupervised && (
                    <div className="md:col-span-2 lg:col-span-1">
                        <Input
                            label={t('components.assessment_general_config.scheduled_date_label')}
                            type="datetime-local"
                            value={data.scheduled_date || ''}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                                onFieldChange('scheduled_date', e.target.value)
                            }
                            error={errors.scheduled_date || errors.scheduled_at}
                        />
                    </div>
                )}

                {!isSupervised && (
                    <div className="md:col-span-2 lg:col-span-1">
                        <Input
                            label={t('components.assessment_general_config.due_date_label')}
                            type="datetime-local"
                            value={data.due_date || ''}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) =>
                                onFieldChange('due_date', e.target.value)
                            }
                            error={errors.due_date}
                            required
                        />
                    </div>
                )}
            </div>

            <div>
                <MarkdownEditor
                    value={data.description}
                    onChange={(value) => onFieldChange('description', value)}
                    placeholder={t('components.assessment_general_config.description_placeholder')}
                    label={t('components.assessment_general_config.description_label')}
                    rows={4}
                    error={errors.description}
                    helpText={t('components.assessment_general_config.description_help')}
                />
            </div>

            <div className="border-t border-gray-200 pt-6">
                <h4 className="text-md font-medium text-gray-900 mb-4">
                    {t('components.assessment_general_config.options_title')}
                </h4>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <Checkbox
                        label={t('components.assessment_general_config.shuffle_questions_label')}
                        checked={data.shuffle_questions}
                        onChange={(e) => onFieldChange('shuffle_questions', e.target.checked)}
                    />
                    <Checkbox
                        label={t(
                            'components.assessment_general_config.show_results_immediately_label',
                        )}
                        checked={data.show_results_immediately}
                        onChange={(e) =>
                            onFieldChange('show_results_immediately', e.target.checked)
                        }
                    />
                    <Checkbox
                        label={t('components.assessment_general_config.show_correct_answers_label')}
                        checked={data.show_correct_answers}
                        onChange={(e) => onFieldChange('show_correct_answers', e.target.checked)}
                    />
                    <Checkbox
                        label={t(
                            'components.assessment_general_config.allow_late_submission_label',
                        )}
                        checked={data.allow_late_submission}
                        onChange={(e) => onFieldChange('allow_late_submission', e.target.checked)}
                    />
                </div>
            </div>
        </div>
    );
};

export { AssessmentGeneralConfig };
