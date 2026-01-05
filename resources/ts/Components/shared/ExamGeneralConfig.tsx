import React from 'react';
import { trans } from '@/utils';
import { Checkbox, Input } from '@examena/ui';
import { MarkdownEditor } from '@examena/ui';

interface ExamGeneralConfigProps {
    data: {
        title: string;
        description: string;
        duration: number;
        start_time?: string;
        end_time?: string;
        is_active: boolean;
    };
    errors: {
        title?: string;
        description?: string;
        duration?: string;
        start_time?: string;
        end_time?: string;
        is_active?: string;
    };
    onFieldChange: (field: string, value: string | number | boolean) => void;
}

const ExamGeneralConfig: React.FC<ExamGeneralConfigProps> = ({
    data,
    errors,
    onFieldChange
}) => {
    return (
        <div className="space-y-6">
            <h3 className="text-lg font-medium text-gray-900">
                {trans('components.exam_general_config.title')}
            </h3>

            <Checkbox
                label={trans('components.exam_general_config.active_label')}
                checked={data.is_active}
                onChange={(e) => onFieldChange('is_active', e.target.checked)}
            />

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div className="md:col-span-2 lg:col-span-1">
                    <Input
                        label={trans('components.exam_general_config.exam_title_label')}
                        type="text"
                        value={data.title}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => onFieldChange('title', e.target.value)}
                        error={errors.title}
                        required
                    />
                </div>

                <div>
                    <Input
                        label={trans('components.exam_general_config.duration_label')}
                        type="number"
                        value={data.duration?.toString() || ''}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => onFieldChange('duration', parseInt(e.target.value))}
                        error={errors.duration}
                        min="1"
                        required
                    />
                </div>

                <div>
                    <Input
                        label={trans('components.exam_general_config.start_time_label')}
                        type="datetime-local"
                        value={data.start_time || ''}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => onFieldChange('start_time', e.target.value)}
                        error={errors.start_time}
                    />
                </div>

                <div className="md:col-span-2 lg:col-span-1">
                    <Input
                        label={trans('components.exam_general_config.end_time_label')}
                        type="datetime-local"
                        value={data.end_time || ''}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => onFieldChange('end_time', e.target.value)}
                        error={errors.end_time}
                    />
                </div>
            </div>

            <div>
                <MarkdownEditor
                    value={data.description}
                    onChange={(value) => onFieldChange('description', value)}
                    placeholder={trans('components.exam_general_config.description_placeholder')}
                    label={trans('components.exam_general_config.description_label')}
                    rows={4}
                    error={errors.description}
                    helpText={trans('components.exam_general_config.description_help')}
                />
            </div>
        </div>
    );
};

export default ExamGeneralConfig;