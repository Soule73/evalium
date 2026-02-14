import { AlertEntry, Button, Section, TextEntry } from '@/Components';
import AuthenticatedLayout from '@/Components/layout/AuthenticatedLayout';
import { LockClosedIcon } from '@heroicons/react/24/outline';
import { router } from '@inertiajs/react';
import { route } from 'ziggy-js';
import { useTranslations } from '@/hooks/shared/useTranslations';

interface AlertSecurityViolationProps {
    assessment: {
        id: number;
        title: string;
        description?: string;
    };
    reason: string;
}

function AlertSecurityViolation({ assessment, reason }: AlertSecurityViolationProps) {
    const { t } = useTranslations();

    return (
        <CanNotTakeAssessment
            title={t('components.alert_security_violation.title')}
            subtitle={t('components.alert_security_violation.subtitle')}
            icon={<LockClosedIcon className="h-16 w-16 text-red-500 mx-auto" />}
        >
            <TextEntry
                label={assessment.title}
                value={assessment.description ? (assessment.description.length > 100 ? assessment.description.substring(0, 100) + '...' : assessment.description) : ''}
            />
            <AlertEntry type="error" title={t('components.alert_security_violation.violation_detected', { reason })}>
                <div className="text-sm text-red-700 text-start">
                    <ul className="list-disc list-inside space-y-1">
                        <li>{t('components.alert_security_violation.teacher_notified')}</li>
                        <li>{t('components.alert_security_violation.answers_saved')}</li>
                        <li>{t('components.alert_security_violation.will_be_contacted')}</li>
                    </ul>
                </div>
            </AlertEntry>
        </CanNotTakeAssessment>
    );
}



interface CanNotTakeAssessmentProps {
    title: string;
    subtitle?: string;
    message?: string;
    icon?: React.ReactNode;
    children?: React.ReactNode;
}

function CanNotTakeAssessment({ title, subtitle, message, icon, children }: CanNotTakeAssessmentProps) {
    const { t } = useTranslations();

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
                            onClick={() => router.visit(route('student.assessments.index'))}

                        >
                            {t('components.alert_security_violation.back_to_assessments')}
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

export { CanNotTakeAssessment, AlertSecurityViolation };