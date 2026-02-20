import { AcademicCapIcon, BookOpenIcon, UserIcon } from '@heroicons/react/24/outline';
import { type Assessment } from '@/types';
import { useTranslations } from '@/hooks/shared/useTranslations';
import { Stat } from '@/Components/ui';

interface AssessmentContextInfoProps {
    assessment: Assessment;
    role: 'admin' | 'teacher';
    showLevel?: boolean;
    student?: { name: string };
}

interface InfoItem {
    icon: React.ComponentType<React.SVGProps<SVGSVGElement>>;
    label: string;
    value: string;
}

/**
 * Displays contextual information (teacher, class, subject) for an assessment.
 *
 * For teacher role: class + subject (+ optional level).
 * For admin role: teacher + class + subject (+ optional level).
 */
export function AssessmentContextInfo({
    assessment,
    role,
    showLevel = false,
    student,
}: AssessmentContextInfoProps) {
    const { t } = useTranslations();

    const classItem = assessment.class_subject?.class;
    const subject = assessment.class_subject?.subject;
    const teacher = assessment.class_subject?.teacher;

    const levelDescription = classItem?.level
        ? `${classItem.level.name}${classItem.level.description ? ` (${classItem.level.description})` : ''}`
        : null;

    const items: InfoItem[] = [];

    if (role === 'admin' && teacher) {
        items.push({
            icon: UserIcon,
            label: t('assessment_pages.common.teacher'),
            value: teacher.name,
        });
    }

    items.push({
        icon: AcademicCapIcon,
        label: t('assessment_pages.common.class'),
        value: classItem?.name ?? '-',
    });

    if (showLevel && levelDescription) {
        items.push({
            icon: AcademicCapIcon,
            label: t('assessment_pages.common.level'),
            value: levelDescription,
        });
    }

    items.push({
        icon: BookOpenIcon,
        label: t('assessment_pages.common.subject'),
        value: subject?.name ?? '-',
    });

    if (student) {
        items.push({
            icon: UserIcon,
            label: t('assessment_pages.show.student'),
            value: student.name,
        });
    }

    const colsClass = items.length === 2 ? 2 : items.length === 3 ? 3 : 4;

    return (
        <Stat.Group columns={colsClass}>
            {items.map((item) => (
                <Stat.Item
                    key={item.label}
                    icon={item.icon}
                    title={item.label}
                    value={item.value}
                />
            ))}
        </Stat.Group>
    );
}
