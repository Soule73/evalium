import React from 'react';
import Input from '@/Components/form/Input';
import Checkbox from '@/Components/form/Checkbox';
import MarkdownEditor from '@/Components/form/MarkdownEditor';

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
    onFieldChange: (field: string, value: any) => void;
}

const ExamGeneralConfig: React.FC<ExamGeneralConfigProps> = ({
    data,
    errors,
    onFieldChange
}) => {
    return (
        <div className="space-y-6">
            <h3 className="text-lg font-medium text-gray-900">
                Informations générales
            </h3>

            <Checkbox
                label="Examen actif"
                checked={data.is_active}
                onChange={(e) => onFieldChange('is_active', e.target.checked)}
            />

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div className="md:col-span-2 lg:col-span-1">
                    <Input
                        label="Titre de l'examen"
                        type="text"
                        value={data.title}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => onFieldChange('title', e.target.value)}
                        error={errors.title}
                        required
                    />
                </div>

                <div>
                    <Input
                        label="Durée (minutes)"
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
                        label="Date et heure de début"
                        type="datetime-local"
                        value={data.start_time || ''}
                        onChange={(e: React.ChangeEvent<HTMLInputElement>) => onFieldChange('start_time', e.target.value)}
                        error={errors.start_time}
                    />
                </div>

                <div className="md:col-span-2 lg:col-span-1">
                    <Input
                        label="Date et heure de fin"
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
                    placeholder="Description de l'examen..."
                    label="Description de l'examen"
                    rows={4}
                    error={errors.description}
                    helpText="Décrivez l'objectif et les modalités de cet examen. Vous pouvez utiliser le formatage Markdown."
                />
            </div>
        </div>
    );
};

export default ExamGeneralConfig;