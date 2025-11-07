import { AlertEntry, Button, Section, TextEntry } from '@/Components';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { LockClosedIcon } from '@heroicons/react/24/outline';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { trans } from '@/utils';

interface AlertSecurityViolationProps {
    exam: {
        id: number;
        title: string;
        description?: string;
    };
    reason: string;
}

export default function AlertSecurityViolation({ exam, reason }: AlertSecurityViolationProps) {

    return (
        <CanNotTakeExam
            title={trans('components.alert_security_violation.title')}
            subtitle={trans('components.alert_security_violation.subtitle')}
            icon={<LockClosedIcon className="h-16 w-16 text-red-500 mx-auto" />}
        >
            <TextEntry
                label={exam.title}
                value={exam.description ? (exam.description.length > 100 ? exam.description.substring(0, 100) + '...' : exam.description) : ''}
            />
            <AlertEntry type="error" title={trans('components.alert_security_violation.violation_detected', { reason })}>
                <div className="text-sm text-red-700 text-start">
                    <ul className="list-disc list-inside space-y-1">
                        <li>{trans('components.alert_security_violation.teacher_notified')}</li>
                        <li>{trans('components.alert_security_violation.answers_saved')}</li>
                        <li>{trans('components.alert_security_violation.will_be_contacted')}</li>
                    </ul>
                </div>
            </AlertEntry>
        </CanNotTakeExam>
    );
}



interface CanNotTakeExamProps {
    title: string;
    subtitle?: string;
    message?: string;
    icon?: React.ReactNode;
    children?: React.ReactNode;
}

function CanNotTakeExam({ title, subtitle, message, icon, children }: CanNotTakeExamProps) {
    return (
        <AuthenticatedLayout title={title}>

            <div className="w-full min-h-[80vh] flex justify-center items-center space-y-8">
                <Section title={title}
                    className='max-w-4xl! w-full md:min-w-md '
                    subtitle={subtitle ?? ''}
                    actions={
                        <Button
                            variant='outline'
                            color='secondary'
                            size='sm'
                            onClick={() => router.visit(route('student.exams.index'))}

                        >
                            {trans('components.alert_security_violation.back_to_exams')}
                        </Button>
                    }
                >
                    <div className="px-6 py-8 text-center">
                        {/* Icon de sécurité */}
                        <div className="mx-auto mb-4">
                            {icon && icon}
                        </div>
                        {message && <TextEntry
                            label={''}
                            value={message}
                        />}
                        {children}


                    </div>
                </Section>
            </div>
        </AuthenticatedLayout>
    );
}

export { CanNotTakeExam };