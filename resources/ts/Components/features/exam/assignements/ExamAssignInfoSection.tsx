import { Exam } from '@/types';
import { trans } from '@/utils';
import { Button, MarkdownRenderer, Section } from '@/Components';

interface ExamAssignInfoSectionProps {
    exam: Exam;
    onCancel: () => void;
}

export function ExamAssignInfoSection({ exam, onCancel }: ExamAssignInfoSectionProps) {
    return (
        <Section
            title={trans('exam_pages.assign.exam_info')}
            subtitle={trans('exam_pages.assign.exam_info_subtitle')}
            actions={
                <Button
                    type="button"
                    onClick={onCancel}
                    color="secondary"
                    variant="outline"
                >
                    {trans('exam_pages.assign.cancel')}
                </Button>
            }
        >
            <div className="space-y-2">
                <h2 className="text-xl font-semibold text-gray-900">{exam.title}</h2>
                {exam.description && (
                    <MarkdownRenderer>{exam.description}</MarkdownRenderer>
                )}
                <p className="text-sm text-gray-500">
                    {trans('exam_pages.assign.duration_label', { duration: exam.duration })}
                </p>
            </div>
        </Section>
    );
}
