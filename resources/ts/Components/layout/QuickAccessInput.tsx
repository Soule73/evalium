import { type FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { Button, Input, Tooltip } from '@evalium/ui';
import { BoltIcon } from '@heroicons/react/24/outline';

interface QuickAccessInputProps {
    isCollapsed: boolean;
    t: (key: string) => string;
}

/**
 * Compact input in the student sidebar for quick navigation to an assessment by ID.
 * Designed to reduce stress during supervised exams by avoiding index page navigation.
 */
export const QuickAccessInput = ({ isCollapsed, t }: QuickAccessInputProps) => {
    const [value, setValue] = useState('');
    const [error, setError] = useState(false);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        const id = parseInt(value.trim(), 10);
        if (isNaN(id) || id <= 0) {
            setError(true);
            return;
        }

        setError(false);
        setValue('');
        router.visit(route('student.assessments.show', { assessment: id }));
    };

    if (isCollapsed) {
        return (
            <Tooltip content={t('sidebar.quick_access.label')} position="right">
                <form onSubmit={handleSubmit} className="px-2 py-1">
                    <div className="relative">
                        <Input
                            type="text"
                            inputMode="numeric"
                            value={value}
                            onChange={(e) => {
                                setValue(e.target.value);
                                setError(false);
                            }}
                            placeholder="#"
                            className={`w-full rounded-lg border px-2 py-2 text-center text-sm`}
                            aria-label={t('sidebar.quick_access.placeholder')}
                            data-e2e="quick-access-input"
                        />
                    </div>
                </form>
            </Tooltip>
        );
    }

    return (
        <form onSubmit={handleSubmit} className="px-3 py-2">
            <label className="mb-1 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400">
                <BoltIcon className="h-3.5 w-3.5" />
                {t('sidebar.quick_access.label')}
            </label>
            <div className="flex gap-1.5">
                <Input
                    type="text"
                    inputMode="numeric"
                    value={value}
                    onChange={(e) => {
                        setValue(e.target.value);
                        setError(false);
                    }}
                    placeholder={t('sidebar.quick_access.placeholder')}
                    className={`min-w-0 flex-1 rounded-lg border px-3 py-1.5 text-sm `}
                    aria-label={t('sidebar.quick_access.placeholder')}
                    data-e2e="quick-access-input"
                    error={error ? t('sidebar.quick_access.invalid_id') : ''}
                />
                <Button type="submit" size="xs" className=" px-3" data-e2e="quick-access-go">
                    {t('sidebar.quick_access.go')}
                </Button>
            </div>
        </form>
    );
};
