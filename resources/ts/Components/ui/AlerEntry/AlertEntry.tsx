import { CheckCircleIcon, XCircleIcon, ExclamationCircleIcon, InformationCircleIcon } from "@heroicons/react/16/solid";
import React from "react";

interface AlertEntry {
    title: string;
    children?: React.ReactNode;
    className?: string;
    type: 'success' | 'error' | 'warning' | 'info';
}

const AlertEntry: React.FC<AlertEntry> = ({ title, children, type, className }) => {
    const typeStyles = {
        success: "text-green-600 bg-green-100 border-green-200",
        error: "text-red-600 bg-red-100 border-red-200",
        warning: "text-yellow-600 bg-yellow-100 border-yellow-200",
        info: "text-blue-600 bg-blue-100 border-blue-200",
    }[type];

    const icon = {
        success: <CheckCircleIcon className="w-5 h-5" />,
        error: <XCircleIcon className="w-5 h-5" />,
        warning: <ExclamationCircleIcon className="w-5 h-5" />,
        info: <InformationCircleIcon className="w-5 h-5" />,
    }[type];

    return (
        <div className={`border-l-4 my-2 p-4 ${typeStyles} ${className || ''}`}>
            <div className={`flex items-center ${children ? 'mb-2' : ''} space-x-2`}>
                {icon}
                <h4 className="font-medium mb-1">{title}</h4>
            </div>
            {children && children}
        </div>
    );
}


export default AlertEntry;