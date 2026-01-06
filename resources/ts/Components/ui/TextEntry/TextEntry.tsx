import React from "react";

interface TextEntry {
    label: string | React.ReactNode;
    value: string | React.ReactNode;
    className?: string;
    valueClass?: string;
    labelClass?: string;
}


const TextEntry: React.FC<TextEntry> = ({ label, value, valueClass, labelClass, className }) => {
    return (
        <div className={`flex flex-col space-y-1 ${className || ''}`}>

            {typeof label === "string" ? <span className={`text-sm font-bold text-gray-900 ${labelClass || ''}`}>{label}</span> : label}
            {typeof value === "string" ? <span className={`text-sm text-gray-600 ${valueClass || ''}`}>{value}</span> : value}
        </div>
    );
};

export default TextEntry;