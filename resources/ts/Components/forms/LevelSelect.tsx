import { trans } from "@/utils";
import { Select } from "../ui";

interface LevelSelectProps {
    value: string;
    onChange: (e: React.ChangeEvent<HTMLSelectElement>) => void;
    levels: Record<number, string>;
    error?: string;
    placeholder?: string;
    label?: string;
    required?: boolean;
}

export default function LevelSelect({
    value,
    onChange,
    levels,
    error,
    placeholder = "Sélectionner un niveau",
    label = "Niveau académique",
    required = false
}: LevelSelectProps) {
    const levelOptions = [
        { label: placeholder, value: '' },
        ...Object.entries(levels).map(([id, name]) => ({
            label: name,
            value: id
        }))
    ];

    const searchPlaceholder = trans('components.select.search_placeholder');
    const noOptionFound = trans('components.select.no_option_found');


    return (
        <div>
            {label && (
                <label className="block text-sm font-medium text-gray-700 mb-2">
                    {label} {required && '*'}
                </label>
            )}
            <Select
                noOptionFound={noOptionFound}
                searchPlaceholder={searchPlaceholder}
                value={value}
                onChange={v => onChange({
                    target: { value: v }
                } as React.ChangeEvent<HTMLSelectElement>)}
                options={levelOptions}
                error={error}
                placeholder={placeholder}
            />
        </div>
    );
}