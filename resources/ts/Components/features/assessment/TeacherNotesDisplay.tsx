import { Section } from '@/Components';

interface TeacherNotesDisplayProps {
    notes: string | null | undefined;
    title: string;
}

/**
 * Displays teacher notes in a styled section.
 * Only renders when notes are present.
 */
export const TeacherNotesDisplay: React.FC<TeacherNotesDisplayProps> = ({ notes, title }) => {
    if (!notes) return null;

    return (
        <Section title={title}>
            <div className="p-4 bg-gray-50 rounded-lg">
                <p className="text-gray-700 whitespace-pre-wrap">{notes}</p>
            </div>
        </Section>
    );
};
