export const TYPE_COLORS: Record<string, string> = {
    multiple: 'bg-blue-100 text-blue-800',
    one_choice: 'bg-green-100 text-green-800',
    boolean: 'bg-yellow-100 text-yellow-800',
    text: 'bg-purple-100 text-purple-800',
};

/**
 * Gets the color class for a specific question type
 */
export const getTypeColor = (type: string): string => {
    return TYPE_COLORS[type] ?? 'bg-gray-100 text-gray-800';
};
