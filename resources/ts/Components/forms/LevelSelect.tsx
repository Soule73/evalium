import { trans } from "@/utils";
import { Select } from "../ui";

interface LevelSelectProps {
    value: string;
    onChange: (e: React.ChangeEvent<HTMLSelectElement>) => void;
    levels: Record<number, string>;
    error?: string;
    required?: boolean;
}

export default function LevelSelect({
    value,
    onChange,
    levels,
    error,
    required = false
}: LevelSelectProps) {
    const label = trans('components.select.select_level');
    const searchPlaceholder = trans('components.select.level_placeholder');
    const noOptionFound = trans('components.select.no_option_found');


    const levelOptions = [
        { label: searchPlaceholder, value: '' },
        ...Object.entries(levels).map(([id, name]) => ({
            label: name,
            value: id
        }))
    ];


    return (
        <Select
            noOptionFound={noOptionFound}
            searchPlaceholder={searchPlaceholder}
            value={value}
            onChange={v => onChange({
                target: { value: v }
            } as React.ChangeEvent<HTMLSelectElement>)}
            options={levelOptions}
            error={error}
            required={required}
            placeholder={searchPlaceholder}
            label={label}
        />
    );
}